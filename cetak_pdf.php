<?php
require_once 'includes/config.php';
$db = getDB();

// =======================
// AMBIL DATA
// =======================
$id = intval($_GET['id'] ?? 0);
if (!$id) die('ID tidak valid');

$stmt = $db->prepare("SELECT * FROM surat_jalan WHERE id=?");
$stmt->execute([$id]);
$sj = $stmt->fetch();

if (!$sj) die('Data tidak ditemukan');

$stmt = $db->prepare("SELECT * FROM surat_jalan_detail WHERE surat_jalan_id=? ORDER BY urutan");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

// =======================
// LOGO (FINAL - TANPA BASE64)
// =======================
$logo_url = BASE_URL . '/assets/img/default-logo.png';

if (!empty($sj['logo'])) {
    $path = __DIR__ . '/' . $sj['logo'];
    if (file_exists($path)) {
        $logo_url = BASE_URL . '/' . $sj['logo'];
    }
}

// =======================
// LOAD MPDF
// =======================
require_once __DIR__ . '/vendor/autoload.php';

// pastikan folder tmp ada
if (!is_dir(__DIR__ . '/tmp')) {
    mkdir(__DIR__ . '/tmp', 0777, true);
}

$mpdf = new \Mpdf\Mpdf([
    'margin_top'    => 8,
    'margin_bottom' => 8,
    'margin_left'   => 10,
    'margin_right'  => 10,
    'format'        => 'A4',
    'default_font'  => 'dejavusans',
    'tempDir'       => __DIR__ . '/tmp',
]);

// =======================
// FORMAT DATA
// =======================
$tanggal   = date('d F Y', strtotime($sj['tanggal']));
$no_do     = htmlspecialchars($sj['no_do']);
$penerima  = htmlspecialchars($sj['penerima']);
$telp      = htmlspecialchars($sj['telp_penerima'] ?? '');
$pengemudi = htmlspecialchars($sj['pengemudi']);
$kendaraan = htmlspecialchars($sj['no_kendaraan']);
$metode    = htmlspecialchars($sj['metode_pengiriman']);

// =======================
// TABLE ITEM
// =======================
$rows = '';
$total = 0;

foreach ($details as $i => $d) {
    $subtotal = $d['kuantitas'] * $d['harga_satuan'];
    $total += $subtotal;

    $rows .= '
    <tr>
        <td>' . ($i+1) . '</td>
        <td>' . htmlspecialchars($d['nama_barang']) . '</td>
        <td>' . htmlspecialchars($d['kode_sku']) . '</td>
        <td align="right">' . number_format($d['kuantitas']) . '</td>
        <td>' . htmlspecialchars($d['satuan']) . '</td>
        <td align="right">Rp ' . number_format($d['harga_satuan'],0,',','.') . '</td>
        <td align="right"><b>Rp ' . number_format($subtotal,0,',','.') . '</b></td>
    </tr>';
}

// =======================
// HTML
// =======================
$html = '
<style>
body { font-family: dejavusans; font-size: 10pt; }
.header { border-bottom:2px solid #000; padding-bottom:10px; }
.title { font-size:20pt; font-weight:bold; text-align:right; }
.info { margin-top:10px; }
.box { border:1px solid #ccc; padding:10px; width:48%; display:inline-block; vertical-align:top; }
.table { width:100%; border-collapse:collapse; margin-top:10px; }
.table th { background:#000; color:#fff; padding:6px; }
.table td { padding:6px; border-bottom:1px solid #ddd; }
.footer { margin-top:20px; }
</style>

<div class="header">
    <table width="100%">
        <tr>
            <td width="60%">
                <img src="' . $logo_url . '" height="60"><br>
                <b>' . COMPANY_NAME . '</b><br>
                ' . COMPANY_ADDR . '<br>
                Telp ' . COMPANY_TELP . '
            </td>
            <td width="40%" align="right">
                <div class="title">Surat Jalan</div>
                DO/2026/' . $no_do . '
            </td>
        </tr>
    </table>
</div>

<div class="info">
    <div class="box">
        <b>Ditujukan Untuk</b><br>
        ' . $penerima . '<br>
        Telp: ' . $telp . '
    </div>

    <div class="box">
        <b>Detail Pengiriman</b><br>
        Tanggal: ' . $tanggal . '<br>
        Metode: ' . $metode . '<br>
        Pengemudi: ' . $pengemudi . '<br>
        Kendaraan: ' . $kendaraan . '
    </div>
</div>

<table class="table">
<tr>
    <th>#</th>
    <th>Nama Barang</th>
    <th>SKU</th>
    <th>Qty</th>
    <th>Satuan</th>
    <th>Harga</th>
    <th>Subtotal</th>
</tr>
' . $rows . '
<tr>
    <td colspan="6" align="right"><b>Total</b></td>
    <td align="right"><b>Rp ' . number_format($total,0,',','.') . '</b></td>
</tr>
</table>

<div class="footer">
    <table width="100%">
        <tr>
            <td align="center">
                Diterima oleh,<br><br><br>
                ( ' . $penerima . ' )
            </td>
            <td align="center">
                Dikirim oleh,<br><br><br>
                ( ' . $pengemudi . ' )
            </td>
        </tr>
    </table>
</div>
';

// =======================
// OUTPUT
// =======================
$mpdf->WriteHTML($html);
$mpdf->Output('SuratJalan_'.$no_do.'.pdf', 'I');
exit;