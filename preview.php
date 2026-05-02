<?php
// preview.php — Preview Surat Jalan (3 rangkap, tampilan HTML)
require_once 'includes/config.php';
$db = getDB();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$sj = $db->prepare("SELECT * FROM surat_jalan WHERE id = ?");
$sj->execute([$id]);
$sj = $sj->fetch();
if (!$sj) { die('Surat jalan tidak ditemukan.'); }

$details = $db->prepare("SELECT * FROM surat_jalan_detail WHERE surat_jalan_id = ? ORDER BY urutan");
$details->execute([$id]);
$details = $details->fetchAll();

$copies = [
    ['label' => 'Lembar 1 — Asli (Penerima)',    'color' => '#0f172a'],
    ['label' => 'Lembar 2 — Arsip (Pengirim)',    'color' => '#1e40af'],
    ['label' => 'Lembar 3 — Pengemudi',           'color' => '#7f1d1d'],
];

// Logo base64
$logo_path = __DIR__ . '/assets/img/logo.png';
$logo_b64 = base64_encode(file_get_contents($logo_path));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Preview — DO/2026/<?= htmlspecialchars($sj['no_do']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#e8eaf0;padding:24px;color:#1e293b}
.toolbar{background:#0f172a;color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:10}
.toolbar h2{font-size:14px;font-weight:700}
.toolbar-btns{display:flex;gap:8px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none}
.btn-red{background:#e11d48;color:#fff}
.btn-ghost{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2)}
.btn-ghost:hover{background:rgba(255,255,255,.2)}

/* DOCUMENT */
.doc-wrap{margin-bottom:24px}
.copy-label-bar{padding:9px 16px;color:#fff;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;border-radius:6px 6px 0 0}
.doc{background:#fff;border-radius:0 0 6px 6px;padding:28px 32px;box-shadow:0 2px 12px rgba(0,0,0,.12);max-width:900px;margin:0 auto}
.doc-hdr{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16px;border-bottom:2.5px solid #0f172a;margin-bottom:18px}
.doc-logo-side{display:flex;align-items:center;gap:12px}
.doc-logo-side img{height:64px;width:auto}
.company-name{font-size:15px;font-weight:800;color:#0f172a;letter-spacing:.2px}
.company-sub{font-size:11px;color:#64748b;line-height:1.6;margin-top:2px}
.doc-title-side{text-align:right}
.doc-title{font-size:24px;font-weight:800;color:#0f172a;letter-spacing:-0.5px}
.doc-no{font-family:'JetBrains Mono',monospace;font-size:12px;color:#e11d48;margin-top:3px}

/* INFO GRID */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.info-box{border:1.5px solid #e2e8f0;border-radius:6px;padding:12px 14px}
.info-box-ttl{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#94a3b8;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #f1f5f9}
.info-line{font-size:12px;line-height:2;color:#334155}
.info-line strong{color:#0f172a;font-weight:700}

/* TABLE */
table.items{width:100%;border-collapse:collapse;margin-bottom:16px;font-size:12px}
table.items thead tr{background:#0f172a}
table.items thead th{padding:9px 12px;color:#fff;text-align:left;font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase}
table.items thead th.r{text-align:right}
table.items tbody tr:nth-child(even){background:#f8fafc}
table.items tbody td{padding:9px 12px;border-bottom:1px solid #f1f5f9;color:#334155}
table.items tbody td.r{text-align:right;font-family:'JetBrains Mono',monospace;font-size:11px}
table.items tbody td.muted{color:#94a3b8;font-family:'JetBrains Mono',monospace;font-size:11px}
table.items tfoot tr{border-top:2px solid #0f172a}
table.items tfoot td{padding:10px 12px;font-weight:700}
table.items tfoot td.r{text-align:right;font-family:'JetBrains Mono',monospace;font-size:15px;color:#0f172a}

/* SIGN */
.sign-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:20px;padding-top:14px;border-top:1px solid #e2e8f0}
.sign-box{text-align:center}
.sign-ttl{font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:#94a3b8;margin-bottom:48px}
.sign-line{border-top:1.5px solid #1e293b;padding-top:6px;font-size:11px;color:#64748b}

/* CATATAN */
.catatan-box{background:#fefce8;border:1px solid #fef08a;border-radius:6px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#713f12}

@media print{
  body{background:#fff;padding:0}
  .toolbar{display:none!important}
  .doc-wrap{page-break-inside:avoid;margin-bottom:0}
  .doc{box-shadow:none;border-radius:0}
  .copy-label-bar{-webkit-print-color-adjust:exact;print-color-adjust:exact;border-radius:0}
  table.items thead tr{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
</head>
<body>

<div class="toolbar">
  <h2>📄 Preview — DO/2026/<?= htmlspecialchars($sj['no_do']) ?> | <?= htmlspecialchars($sj['penerima']) ?></h2>
  <div class="toolbar-btns">
    <a href="cetak_pdf.php?id=<?= $sj['id'] ?>" class="btn btn-red" target="_blank">🖨️ Download PDF</a>
    <a href="index.php" class="btn btn-ghost">← Kembali</a>
  </div>
</div>

<?php foreach ($copies as $ci => $copy): ?>
<div class="doc-wrap">
  <div class="copy-label-bar" style="background:<?= $copy['color'] ?>; max-width:900px; margin:0 auto;">
    <?= $copy['label'] ?>
  </div>
  <div class="doc">
    <!-- HEADER -->
    <div class="doc-hdr">
      <div class="doc-logo-side">
        <img src="data:image/png;base64,<?= $logo_b64 ?>" alt="Logo Beryu">
        <div>
          <div class="company-name"><?= COMPANY_NAME ?></div>
          <div class="company-sub"><?= COMPANY_ADDR ?><br>Telp <?= COMPANY_TELP ?></div>
        </div>
      </div>
      <div class="doc-title-side">
        <div class="doc-title">Surat Jalan</div>
        <div class="doc-no">DO/2026/<?= htmlspecialchars($sj['no_do']) ?></div>
      </div>
    </div>

    <!-- INFO -->
    <div class="info-grid">
      <div class="info-box">
        <div class="info-box-ttl">Ditujukan Untuk</div>
        <div class="info-line"><strong><?= htmlspecialchars($sj['penerima']) ?></strong></div>
        <?php if ($sj['telp_penerima']): ?>
        <div class="info-line">Telp <strong><?= htmlspecialchars($sj['telp_penerima']) ?></strong></div>
        <?php endif; ?>
      </div>
      <div class="info-box">
        <div class="info-box-ttl">Detail Pengiriman</div>
        <div class="info-line">Tanggal : <strong><?= date('d F Y', strtotime($sj['tanggal'])) ?></strong></div>
        <div class="info-line">Metode  : <strong><?= htmlspecialchars($sj['metode_pengiriman']) ?></strong></div>
        <div class="info-line">No. Resi : <strong><?= $sj['no_resi'] ?: '……………………' ?></strong></div>
        <div class="info-line">Pengemudi : <strong><?= htmlspecialchars($sj['pengemudi']) ?></strong></div>
        <div class="info-line">Kendaraan : <strong><?= htmlspecialchars($sj['no_kendaraan']) ?></strong></div>
        <div class="info-line">Jml Berat : <strong><?= $sj['jumlah_berat'] ?: '……………………' ?></strong></div>
      </div>
    </div>

    <!-- CATATAN -->
    <?php if ($sj['catatan']): ?>
    <div class="catatan-box">📝 <?= nl2br(htmlspecialchars($sj['catatan'])) ?></div>
    <?php endif; ?>

    <!-- ITEMS -->
    <table class="items">
      <thead>
        <tr>
          <th style="width:36px">#</th>
          <th>Nama Produk / Jenis Barang</th>
          <th>Kode (SKU)</th>
          <th class="r">Kuantitas</th>
          <th class="r">Satuan</th>
          <th class="r">Harga Satuan</th>
          <th class="r">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($details)): ?>
        <tr><td colspan="7" style="text-align:center;padding:20px;color:#94a3b8;font-style:italic">— Tidak ada barang —</td></tr>
        <?php else: ?>
        <?php foreach ($details as $i => $d): ?>
        <tr>
          <td style="text-align:center;color:#94a3b8;font-family:'JetBrains Mono',monospace"><?= $i+1 ?></td>
          <td style="font-weight:600"><?= htmlspecialchars($d['nama_barang']) ?></td>
          <td class="muted"><?= htmlspecialchars($d['kode_sku'] ?: '-') ?></td>
          <td class="r"><?= number_format($d['kuantitas'],0,',','.') ?></td>
          <td class="r"><?= htmlspecialchars($d['satuan']) ?></td>
          <td class="r"><?= formatRupiah($d['harga_satuan']) ?></td>
          <td class="r" style="font-weight:700"><?= formatRupiah($d['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if ($sj['total_harga'] > 0): ?>
      <tfoot>
        <tr>
          <td colspan="6" style="text-align:right;font-size:12px;color:#64748b">Total Keseluruhan</td>
          <td class="r"><?= formatRupiah($sj['total_harga']) ?></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>

    <!-- SIGNATURES -->
    <div class="sign-grid">
      <div class="sign-box">
        <div class="sign-ttl">Diterima oleh,</div>
        <div class="sign-line">( <?= htmlspecialchars($sj['penerima']) ?> )</div>
      </div>
      <div class="sign-box">
        <div class="sign-ttl">Dikirim oleh,</div>
        <div class="sign-line">( <?= htmlspecialchars($sj['pengemudi']) ?> )</div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

</body>
</html>
