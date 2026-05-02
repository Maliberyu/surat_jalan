<?php
// =============================================
// index.php — Halaman utama & form input
// =============================================
require_once 'includes/config.php';
$db = getDB();

$msg = '';
$edit_data = null;
$edit_details = [];

// Load data untuk edit
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM surat_jalan WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
    if ($edit_data) {
        $stmt2 = $db->prepare("SELECT * FROM surat_jalan_detail WHERE surat_jalan_id = ? ORDER BY urutan");
        $stmt2->execute([$edit_data['id']]);
        $edit_details = $stmt2->fetchAll();
    }
}

// Handle DELETE
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM surat_jalan WHERE id = ?")->execute([$_GET['delete']]);
    header('Location: index.php?msg=deleted');
    exit;
}

// Handle SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['id'] ?? null;
    $no_do    = trim($_POST['no_do']);
    $tanggal  = $_POST['tanggal'];
    $penerima = trim($_POST['penerima']);
    $telp     = trim($_POST['telp_penerima']);
    $pengemudi= trim($_POST['pengemudi']) ?: PENGEMUDI_DEFAULT;
    $kendaraan= trim($_POST['no_kendaraan']) ?: KENDARAAN_DEFAULT;
    $resi     = trim($_POST['no_resi']);
    $berat    = trim($_POST['jumlah_berat']);
    $catatan  = trim($_POST['catatan']);

    $namaArr  = $_POST['nama_barang'] ?? [];
    $skuArr   = $_POST['kode_sku'] ?? [];
    $qtyArr   = $_POST['kuantitas'] ?? [];
    $satArr   = $_POST['satuan'] ?? [];
    $hrgArr   = $_POST['harga_satuan'] ?? [];

    $total = 0;
    foreach ($qtyArr as $k => $q) {
        $total += floatval($q) * floatval(str_replace(['Rp ','.',','], ['','',''], $hrgArr[$k] ?? 0));
    }

    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE surat_jalan SET no_do=?,tanggal=?,penerima=?,telp_penerima=?,pengemudi=?,no_kendaraan=?,no_resi=?,jumlah_berat=?,catatan=?,total_harga=? WHERE id=?");
            $stmt->execute([$no_do,$tanggal,$penerima,$telp,$pengemudi,$kendaraan,$resi,$berat,$catatan,$total,$id]);
            $sj_id = $id;
            $db->prepare("DELETE FROM surat_jalan_detail WHERE surat_jalan_id = ?")->execute([$sj_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO surat_jalan (no_do,tanggal,penerima,telp_penerima,pengemudi,no_kendaraan,no_resi,jumlah_berat,catatan,total_harga) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$no_do,$tanggal,$penerima,$telp,$pengemudi,$kendaraan,$resi,$berat,$catatan,$total]);
            $sj_id = $db->lastInsertId();
        }

        foreach ($namaArr as $k => $nama) {
            if (trim($nama) === '') continue;
            $hrg = floatval(str_replace(['.',','], ['',''], preg_replace('/[^0-9,]/','',$hrgArr[$k] ?? 0)));
            $qty = floatval($qtyArr[$k] ?? 0);
            $sub = $qty * $hrg;
            $stmt2 = $db->prepare("INSERT INTO surat_jalan_detail (surat_jalan_id,urutan,nama_barang,kode_sku,kuantitas,satuan,harga_satuan,subtotal) VALUES (?,?,?,?,?,?,?,?)");
            $stmt2->execute([$sj_id, $k+1, trim($nama), trim($skuArr[$k]??''), $qty, trim($satArr[$k]??'pcs'), $hrg, $sub]);
        }

        $db->prepare("UPDATE surat_jalan SET total_harga = (SELECT IFNULL(SUM(subtotal),0) FROM surat_jalan_detail WHERE surat_jalan_id=?) WHERE id=?")->execute([$sj_id,$sj_id]);

        header("Location: index.php?msg=saved&last=" . $sj_id);
        exit;
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'saved')   $msg = '<div class="alert alert-success">Surat jalan berhasil disimpan.</div>';
    if ($_GET['msg'] === 'deleted') $msg = '<div class="alert alert-warning">Surat jalan berhasil dihapus.</div>';
}

$list = $db->query("SELECT * FROM surat_jalan ORDER BY tanggal DESC, id DESC")->fetchAll();
$next_no = generateNoDO();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Surat Jalan — Beryu Solution</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #0f172a;
  --accent:  #e11d48;
  --accent2: #0ea5e9;
  --bg:      #f1f5f9;
  --white:   #ffffff;
  --border:  #e2e8f0;
  --text:    #1e293b;
  --muted:   #64748b;
  --success: #16a34a;
  --warn:    #d97706;
  --radius:  8px;
  --shadow:  0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.5}

/* TOPBAR */
.topbar{background:var(--primary);color:#fff;padding:0 20px;height:56px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;box-shadow:0 2px 8px rgba(0,0,0,.3)}
.topbar-brand{display:flex;align-items:center;gap:12px}
.topbar-brand img{height:30px;filter:invert(1) brightness(2)}
.topbar-brand h1{font-size:15px;font-weight:700;letter-spacing:.3px}
.topbar-brand span{font-size:11px;opacity:.5;margin-left:4px;font-family:'JetBrains Mono',monospace}
.topbar-actions{display:flex;gap:8px}

/* LAYOUT */
.container{max-width:1400px;margin:0 auto;padding:20px 16px}
.grid-2{display:grid;grid-template-columns:1fr;gap:20px}
@media(min-width: 992px){
  .grid-2{grid-template-columns:480px 1fr;gap:24px}
  .container{padding:24px 20px}
}

/* CARD */
.card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;display:flex;flex-direction:column}
.card-header{background:var(--primary);color:#fff;padding:14px 20px;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;display:flex;align-items:center;gap:8px}
.card-header.red{background:var(--accent)}
.card-header.blue{background:var(--accent2)}
.card-body{padding:20px;flex:1}

/* ALERT */
.alert{padding:12px 16px;border-radius:var(--radius);font-size:13px;margin-bottom:20px;border-left:4px solid}
.alert-success{background:#dcfce7;color:#166534;border-color:var(--success)}
.alert-danger{background:#fee2e2;color:#991b1b;border-color:#ef4444}
.alert-warning{background:#fef9c3;color:#854d0e;border-color:var(--warn)}

/* FORM */
.form-row{margin-bottom:16px}
.form-row label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:6px}
.form-row input,.form-row select,.form-row textarea{width:100%;border:1.5px solid var(--border);border-radius:var(--radius);padding:10px 12px;font-size:14px;font-family:'Plus Jakarta Sans',sans-serif;color:var(--text);background:#f8fafc;transition:border-color .15s,background .15s}
.form-row input:focus,.form-row select:focus,.form-row textarea:focus{outline:none;border-color:var(--accent2);background:#fff;box-shadow:0 0 0 3px rgba(14,165,233,.1)}
.form-row textarea{resize:vertical;min-height:60px}
.form-grid-2{display:grid;grid-template-columns:1fr;gap:16px}
.form-grid-3{display:grid;grid-template-columns:1fr;gap:12px}
@media(min-width: 640px){
  .form-grid-2{grid-template-columns:1fr 1fr}
  .form-grid-3{grid-template-columns:1fr 1fr 1fr}
}

/* ITEMS TABLE */
.items-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.items-table{width:100%;border-collapse:collapse;font-size:13px;min-width:600px}
.items-table th{background:#f8fafc;padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);border-bottom:2px solid var(--border);white-space:nowrap}
.items-table th.r,.items-table td.r{text-align:right}
.items-table td{padding:8px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
.items-table tr:last-child td{border-bottom:none}
.items-table input{border:1.5px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;width:100%;background:#f8fafc;font-family:'Plus Jakarta Sans',sans-serif}
.items-table input:focus{outline:none;border-color:var(--accent2);background:#fff}
.items-table input.mono{font-family:'JetBrains Mono',monospace;font-size:12px}
.items-table input.r{text-align:right}
.col-no{width:40px;text-align:center;color:var(--muted);font-family:'JetBrains Mono',monospace;font-size:12px}
.col-nama{min-width:180px}
.col-sku{width:110px}
.col-qty{width:80px}
.col-sat{width:80px}
.col-hrg{width:120px}
.col-sub{width:130px;font-family:'JetBrains Mono',monospace;color:var(--primary);font-weight:600}
.col-act{width:40px}
.btn-del-row{background:none;border:1px solid #fecaca;color:#ef4444;cursor:pointer;border-radius:6px;width:32px;height:32px;font-size:18px;line-height:1;transition:all .15s;display:flex;align-items:center;justify-content:center}
.btn-del-row:hover{background:#fee2e2;color:var(--accent);border-color:var(--accent)}
.add-row-wrap{padding:12px 0 4px}

/* TOTAL ROW */
.total-row{display:flex;justify-content:flex-end;align-items:center;gap:12px;padding:16px 0 0;border-top:2px solid var(--border);margin-top:8px}
.total-row .label{font-size:13px;color:var(--muted);font-weight:600}
.total-row .amount{font-size:20px;font-weight:800;font-family:'JetBrains Mono',monospace;color:var(--primary)}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 20px;border:none;border-radius:var(--radius);font-size:14px;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;text-decoration:none;transition:all .15s;letter-spacing:.2px;min-height:40px}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:#1e3a5f}
.btn-danger{background:var(--accent);color:#fff}
.btn-danger:hover{background:#be123c}
.btn-sky{background:var(--accent2);color:#fff}
.btn-sky:hover{background:#0284c7}
.btn-ghost{background:transparent;border:1.5px solid var(--border);color:var(--muted)}
.btn-ghost:hover{background:var(--bg);color:var(--text)}
.btn-sm{padding:8px 14px;font-size:13px;min-height:36px}
.btn-icon{padding:8px;width:40px}
.form-actions{display:flex;gap:12px;padding-top:20px;border-top:1px solid var(--border);margin-top:8px;flex-wrap:wrap}

/* TABLE LIST */
.tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.tbl{width:100%;border-collapse:collapse;min-width:700px}
.tbl thead tr{background:#f8fafc}
.tbl th{padding:12px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);border-bottom:2px solid var(--border);white-space:nowrap}
.tbl td{padding:12px 16px;border-bottom:1px solid var(--border);font-size:14px;vertical-align:middle}
.tbl tr:hover td{background:#f8fafc}
.badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.badge-draft{background:#fef3c7;color:#d97706}
.badge-terkirim{background:#dbeafe;color:#1d4ed8}
.badge-selesai{background:#dcfce7;color:#15803d}
.no-data{text-align:center;padding:48px 20px;color:var(--muted);font-size:14px}

/* HIGHLIGHT saved */
.row-highlight td{background:#f0fdf4!important}

/* ACTION BUTTONS WRAP */
.action-buttons{display:flex;gap:6px;justify-content:flex-start;flex-wrap:wrap}
@media(min-width: 480px){
  .action-buttons{justify-content:center}
}

/* UTILITY */
.text-right{text-align:right}
.text-center{text-align:center}
.text-muted{color:var(--muted)}
.font-mono{font-family:'JetBrains Mono',monospace}
.font-semibold{font-weight:600}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">
    <img src="assets/img/logo.png" alt="Logo">
    <h1>Beryu Solution <span>— Surat Jalan</span></h1>
  </div>
  <div class="topbar-actions">
    <a href="index.php" class="btn btn-ghost btn-sm" style="color:#fff;border-color:#ffffff44">Home</a>
  </div>
</div>

<div class="container">
  <?= $msg ?>

  <div class="grid-2">
    <!-- FORM INPUT -->
    <div>
      <div class="card">
        <div class="card-header <?= $edit_data ? 'blue' : 'red' ?>">
          <?= $edit_data ? 'Edit Surat Jalan' : 'Buat Surat Jalan Baru' ?>
        </div>
        <div class="card-body">
          <form method="POST" id="formSJ">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">

            <div class="form-grid-2">
              <div class="form-row">
                <label>No. DO <span style="color:var(--accent)">*</span></label>
                <input type="text" name="no_do" required
                  value="<?= htmlspecialchars($edit_data['no_do'] ?? $next_no) ?>"
                  placeholder="001">
              </div>
              <div class="form-row">
                <label>Tanggal <span style="color:var(--accent)">*</span></label>
                <input type="date" name="tanggal" required
                  value="<?= $edit_data['tanggal'] ?? date('Y-m-d') ?>">
              </div>
            </div>

            <div class="form-row">
              <label>Nama Penerima <span style="color:var(--accent)">*</span></label>
              <input type="text" name="penerima" required
                value="<?= htmlspecialchars($edit_data['penerima'] ?? '') ?>"
                placeholder="Nama penerima / tujuan">
            </div>

            <div class="form-row">
              <label>No. Telepon Penerima</label>
              <input type="text" name="telp_penerima"
                value="<?= htmlspecialchars($edit_data['telp_penerima'] ?? '') ?>"
                placeholder="628xxxxxxxxx">
            </div>

            <div class="form-grid-2">
              <div class="form-row">
                <label>Nama Pengemudi</label>
                <input type="text" name="pengemudi"
                  value="<?= htmlspecialchars($edit_data['pengemudi'] ?? PENGEMUDI_DEFAULT) ?>">
              </div>
              <div class="form-row">
                <label>No. Kendaraan</label>
                <input type="text" name="no_kendaraan"
                  value="<?= htmlspecialchars($edit_data['no_kendaraan'] ?? KENDARAAN_DEFAULT) ?>">
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-row">
                <label>No. Resi</label>
                <input type="text" name="no_resi"
                  value="<?= htmlspecialchars($edit_data['no_resi'] ?? '') ?>"
                  placeholder="opsional">
              </div>
              <div class="form-row">
                <label>Jumlah Berat</label>
                <input type="text" name="jumlah_berat"
                  value="<?= htmlspecialchars($edit_data['jumlah_berat'] ?? '') ?>"
                  placeholder="contoh: 10 kg">
              </div>
            </div>

            <div class="form-row">
              <label>Catatan (opsional)</label>
              <textarea name="catatan" placeholder="Catatan tambahan..."><?= htmlspecialchars($edit_data['catatan'] ?? '') ?></textarea>
            </div>

            <!-- ITEMS -->
            <div style="margin-top:8px">
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);margin-bottom:10px">Daftar Barang</div>
              <div class="items-wrap">
                <table class="items-table" id="itemsTable">
                  <thead>
                    <tr>
                      <th class="col-no">#</th>
                      <th class="col-nama">Nama Barang</th>
                      <th class="col-sku">SKU</th>
                      <th class="col-qty r">Qty</th>
                      <th class="col-sat">Satuan</th>
                      <th class="col-hrg r">Harga</th>
                      <th class="col-sub r">Subtotal</th>
                      <th class="col-act"></th>
                    </tr>
                  </thead>
                  <tbody id="itemsBody">
                    <?php
                    $init_items = $edit_details ?: [['nama_barang'=>'','kode_sku'=>'','kuantitas'=>'','satuan'=>'pcs','harga_satuan'=>'']];
                    foreach ($init_items as $k => $it): ?>
                    <tr>
                      <td class="col-no"><?= $k+1 ?></td>
                      <td><input type="text" name="nama_barang[]" class="col-nama" placeholder="Nama barang" value="<?= htmlspecialchars($it['nama_barang']) ?>"></td>
                      <td><input type="text" name="kode_sku[]" class="mono" placeholder="SKU" value="<?= htmlspecialchars($it['kode_sku']) ?>"></td>
                      <td><input type="number" name="kuantitas[]" class="r" placeholder="0" min="0" step="0.01" value="<?= $it['kuantitas'] ?>" oninput="calcRow(this)"></td>
                      <td><input type="text" name="satuan[]" placeholder="pcs" value="<?= htmlspecialchars($it['satuan']) ?>"></td>
                      <td><input type="text" name="harga_satuan[]" class="r" placeholder="0" value="<?= $it['harga_satuan'] ? number_format($it['harga_satuan'],0,',','.') : '' ?>" oninput="calcRow(this)"></td>
                      <td class="col-sub r subtotal"><?= $it['harga_satuan'] ? 'Rp '.number_format($it['kuantitas']*$it['harga_satuan'],0,',','.') : '-' ?></td>
                      <td><button type="button" class="btn-del-row" onclick="delRow(this)" title="Hapus baris">&times;</button></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="add-row-wrap">
                <button type="button" class="btn btn-ghost btn-sm" onclick="addRow()">+ Tambah Barang</button>
              </div>
              <div class="total-row">
                <span class="label">Total:</span>
                <span class="amount" id="grandTotal">Rp 0</span>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">Simpan Surat Jalan</button>
              <?php if ($edit_data): ?>
                <a href="index.php" class="btn btn-ghost">Batal</a>
              <?php else: ?>
                <button type="reset" class="btn btn-ghost" onclick="resetForm()">Reset</button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- DAFTAR -->
    <div>
      <div class="card">
        <div class="card-header">Daftar Surat Jalan</div>
        <div class="card-body" style="padding:0">
          <?php if (empty($list)): ?>
            <div class="no-data">Belum ada surat jalan. Buat yang pertama.</div>
          <?php else: ?>
          <div class="tbl-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>No. DO</th>
                <th>Tanggal</th>
                <th>Penerima</th>
                <th class="text-right">Total</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($list as $row): ?>
              <tr class="<?= (isset($_GET['last']) && $_GET['last'] == $row['id']) ? 'row-highlight' : '' ?>">
                <td><span class="font-mono" style="font-size:12px;font-weight:600;color:var(--accent)">DO/2026/<?= htmlspecialchars($row['no_do']) ?></span></td>
                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                <td>
                  <div class="font-semibold"><?= htmlspecialchars($row['penerima']) ?></div>
                  <?php if ($row['telp_penerima']): ?><div class="text-muted" style="font-size:12px"><?= htmlspecialchars($row['telp_penerima']) ?></div><?php endif; ?>
                </td>
                <td class="text-right font-mono font-semibold" style="font-size:13px"><?= formatRupiah($row['total_harga']) ?></td>
                <td><span class="badge badge-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                <td class="text-center">
                  <div class="action-buttons">
                    <a href="cetak_pdf.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-danger btn-sm btn-icon" title="Cetak PDF">PDF</a>
                    <a href="preview.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sky btn-sm btn-icon" title="Preview">View</a>
                    <a href="index.php?edit=<?= $row['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">Edit</a>
                    <a href="index.php?delete=<?= $row['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Hapus" onclick="return confirm('Hapus surat jalan ini?')" style="color:var(--accent);border-color:#fecaca">Del</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let rowCount = <?= count($init_items) ?>;

function addRow() {
  rowCount++;
  const tbody = document.getElementById('itemsBody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td class="col-no">${rowCount}</td>
    <td><input type="text" name="nama_barang[]" class="col-nama" placeholder="Nama barang"></td>
    <td><input type="text" name="kode_sku[]" class="mono" placeholder="SKU"></td>
    <td><input type="number" name="kuantitas[]" class="r" placeholder="0" min="0" step="0.01" oninput="calcRow(this)"></td>
    <td><input type="text" name="satuan[]" placeholder="pcs" value="pcs"></td>
    <td><input type="text" name="harga_satuan[]" class="r" placeholder="0" oninput="calcRow(this)"></td>
    <td class="col-sub r subtotal">-</td>
    <td><button type="button" class="btn-del-row" onclick="delRow(this)" title="Hapus baris">&times;</button></td>
  `;
  tbody.appendChild(tr);
  tr.querySelector('input').focus();
  updateNomor();
}

function delRow(btn) {
  const rows = document.querySelectorAll('#itemsBody tr');
  if (rows.length <= 1) { alert('Minimal 1 baris barang.'); return; }
  btn.closest('tr').remove();
  updateNomor();
  calcTotal();
}

function updateNomor() {
  document.querySelectorAll('#itemsBody tr').forEach((tr, i) => {
    tr.querySelector('.col-no').textContent = i + 1;
  });
}

function parseNum(val) {
  return parseFloat(String(val).replace(/[^0-9]/g, '')) || 0;
}

function formatRp(n) {
  return 'Rp ' + n.toLocaleString('id-ID');
}

function calcRow(el) {
  const tr = el.closest('tr');
  const qty = parseFloat(tr.querySelector('input[name="kuantitas[]"]').value) || 0;
  const hrg = parseNum(tr.querySelector('input[name="harga_satuan[]"]').value);
  const sub = qty * hrg;
  tr.querySelector('.subtotal').textContent = sub > 0 ? formatRp(sub) : '-';
  calcTotal();
}

function calcTotal() {
  let total = 0;
  document.querySelectorAll('#itemsBody tr').forEach(tr => {
    const qty = parseFloat(tr.querySelector('input[name="kuantitas[]"]').value) || 0;
    const hrg = parseNum(tr.querySelector('input[name="harga_satuan[]"]').value);
    total += qty * hrg;
  });
  document.getElementById('grandTotal').textContent = formatRp(total);
}

function resetForm() {
  document.getElementById('formSJ').reset();
  document.getElementById('itemsBody').innerHTML = `
    <tr>
      <td class="col-no">1</td>
      <td><input type="text" name="nama_barang[]" placeholder="Nama barang"></td>
      <td><input type="text" name="kode_sku[]" class="mono" placeholder="SKU"></td>
      <td><input type="number" name="kuantitas[]" class="r" placeholder="0" min="0" oninput="calcRow(this)"></td>
      <td><input type="text" name="satuan[]" placeholder="pcs" value="pcs"></td>
      <td><input type="text" name="harga_satuan[]" class="r" placeholder="0" oninput="calcRow(this)"></td>
      <td class="col-sub r subtotal">-</td>
      <td><button type="button" class="btn-del-row" onclick="delRow(this)" title="Hapus baris">&times;</button></td>
    </tr>`;
  rowCount = 1;
  document.getElementById('grandTotal').textContent = 'Rp 0';
}

// Initial calc
calcTotal();

// Auto-format harga input
document.addEventListener('input', function(e) {
  if (e.target.name === 'harga_satuan[]') {
    let val = e.target.value.replace(/[^0-9]/g, '');
    if (val) {
      e.target.value = parseInt(val).toLocaleString('id-ID');
    }
  }
});
</script>
</body>
</html>