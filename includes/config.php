<?php
// =============================================
// includes/config.php
// Konfigurasi koneksi database
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Ganti sesuai user MySQL kamu
define('DB_PASS', '');            // Ganti sesuai password MySQL kamu
define('DB_NAME', 'surat_jalan_beryu');

define('BASE_URL', 'http://localhost:8080/surat_jalan');
// define('COMPANY_NAME', 'Beryu Solution inexpensive');
// define('COMPANY_ADDR', 'Tasikmalaya');
// define('COMPANY_TELP', '082123454683');
define('APP_NAME', 'Surat Jalan');
define('COMPANY_NAME', 'Beryu Solution inexpensive');
define('COMPANY_ADDR', 'Kp.Cicurug Rt/Rw 018/004 Kab. Tasikmalaya Jawa Barat');
define('COMPANY_TELP', '082123454683');
define('PENGEMUDI_DEFAULT', 'NURUL');
define('KENDARAAN_DEFAULT', 'Z 9312 HQ');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;color:#c00;">
                <h2>❌ Koneksi Database Gagal</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Pastikan XAMPP MySQL sudah berjalan dan database sudah dibuat.</p>
                <p>Jalankan file <strong>database.sql</strong> di phpMyAdmin.</p>
            </div>');
        }
    }
    return $pdo;
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function generateNoDO() {
    $db = getDB();
    $year = date('Y');
    $stmt = $db->query("SELECT COUNT(*) as total FROM surat_jalan WHERE YEAR(tanggal) = $year");
    $row = $stmt->fetch();
    $next = str_pad($row['total'] + 1, 3, '0', STR_PAD_LEFT);
    return $next;
}
?>
