# 📦 Surat Jalan — Beryu Solution
Aplikasi web surat jalan berbasis PHP + MySQL untuk XAMPP

---

## 🚀 Cara Instalasi

### 1. Letakkan folder di htdocs
Salin folder `surat_jalan` ke:
```
C:\xampp\htdocs\surat_jalan\
```

### 2. Jalankan XAMPP
- Start **Apache** dan **MySQL** di XAMPP Control Panel

### 3. Buat Database
- Buka **phpMyAdmin** → http://localhost/phpmyadmin
- Klik **Import** → pilih file `database.sql` → klik **Go**
- Atau copy-paste isi `database.sql` ke tab SQL lalu Execute

### 4. Sesuaikan Konfigurasi (opsional)
Edit file `includes/config.php`:
```php
define('DB_USER', 'root');   // username MySQL kamu
define('DB_PASS', '');       // password MySQL kamu (default XAMPP kosong)
```

### 5. Install Library PDF (mPDF)
Buka Command Prompt / Terminal:
```bash
cd C:\xampp\htdocs\surat_jalan
composer require mpdf/mpdf
```
> Jika belum punya Composer: https://getcomposer.org/Composer-Setup.exe

### 6. Buka Aplikasi
```
http://localhost/surat_jalan/
```

---

## 📁 Struktur File
```
surat_jalan/
├── index.php          ← Halaman utama + form input
├── preview.php        ← Preview 3 rangkap (HTML)
├── cetak_pdf.php      ← Generate & download PDF
├── database.sql       ← Script database MySQL
├── composer.json      ← Konfigurasi library
├── vendor/            ← Library mPDF (setelah composer install)
├── includes/
│   └── config.php     ← Konfigurasi database & konstanta
└── assets/
    └── img/
        └── logo.png   ← Logo perusahaan
```

---

## ✨ Fitur Aplikasi
- ✅ Form input surat jalan lengkap
- ✅ Tambah/hapus baris barang dinamis
- ✅ Kalkulasi harga otomatis (per baris & total)
- ✅ Simpan ke database MySQL
- ✅ Edit & hapus surat jalan
- ✅ Preview 3 rangkap (Asli, Arsip, Pengemudi)
- ✅ Download PDF 3 lembar sekaligus
- ✅ Logo perusahaan di PDF
- ✅ Format Rupiah otomatis

---

## ⚙️ Konfigurasi Lanjutan
Edit `includes/config.php` untuk mengubah:
- Nama perusahaan, alamat, telepon
- Nama pengemudi & kendaraan default
- Koneksi database
