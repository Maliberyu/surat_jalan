-- =============================================
-- Database: surat_jalan_beryu
-- Run this SQL di phpMyAdmin
-- =============================================

CREATE DATABASE IF NOT EXISTS surat_jalan_beryu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE surat_jalan_beryu;

-- Tabel surat jalan
CREATE TABLE IF NOT EXISTS surat_jalan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_do VARCHAR(50) NOT NULL UNIQUE,
    tanggal DATE NOT NULL,
    penerima VARCHAR(150) NOT NULL,
    telp_penerima VARCHAR(30),
    pengemudi VARCHAR(100) DEFAULT 'NURUL',
    no_kendaraan VARCHAR(30) DEFAULT 'Z 9312 HQ',
    no_resi VARCHAR(100),
    jumlah_berat VARCHAR(50),
    metode_pengiriman VARCHAR(50) DEFAULT 'Direct',
    catatan TEXT,
    total_harga DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft','terkirim','selesai') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel detail barang
CREATE TABLE IF NOT EXISTS surat_jalan_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    surat_jalan_id INT NOT NULL,
    urutan INT DEFAULT 1,
    nama_barang VARCHAR(200) NOT NULL,
    kode_sku VARCHAR(100),
    kuantitas DECIMAL(10,2) DEFAULT 0,
    satuan VARCHAR(30) DEFAULT 'pcs',
    harga_satuan DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (surat_jalan_id) REFERENCES surat_jalan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Data contoh
INSERT INTO surat_jalan (no_do, tanggal, penerima, telp_penerima, pengemudi, no_kendaraan, total_harga, status) VALUES
('001', CURDATE(), 'Pak Dadi Babinsa', '6285295085399', 'NURUL', 'Z 9312 HQ', 250000, 'draft');

INSERT INTO surat_jalan_detail (surat_jalan_id, urutan, nama_barang, kode_sku, kuantitas, satuan, harga_satuan, subtotal) VALUES
(1, 1, 'Contoh Barang A', 'SKU-001', 5, 'pcs', 50000, 250000);
