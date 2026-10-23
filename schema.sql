-- ========================================================
-- Mini Project 3: Inventory Management System
-- Pertemuan 4: Relational Database + Advanced PHP
-- Studi Kasus: "PastelBloom Studio" — Toko Buket Bunga, Snack, Uang & Boneka
-- Skema Database Lengkap & Data Awal (Seed Data)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `toko_buket_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `toko_buket_db`;

-- Drop tabel berurutan sesuai relasi integritas
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `product_suppliers`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `users`;

-- 1. Tabel Users (Otentikasi & Role)
CREATE TABLE `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel User Profiles (Relasi 1:1 dengan users)
CREATE TABLE `user_profiles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Categories (Tabel Induk 1:N ke products)
CREATE TABLE `categories` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(20) DEFAULT '🌸',
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Suppliers (Relasi Many-to-Many dengan products)
CREATE TABLE `suppliers` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `contact_person` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Products (Buket Bunga, Snack, Uang, Boneka)
CREATE TABLE `products` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `category_id` INT NOT NULL,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `minimum_stock` INT NOT NULL DEFAULT 5,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Junction: product_suppliers (Relasi Many-to-Many)
CREATE TABLE `product_suppliers` (
    `product_id` INT NOT NULL,
    `supplier_id` INT NOT NULL,
    `supplier_price` DECIMAL(12,2) DEFAULT NULL,
    PRIMARY KEY (`product_id`, `supplier_id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel Stock Movements (Audit Mutasi Barang Masuk/Keluar)
CREATE TABLE `stock_movements` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `type` ENUM('IN', 'OUT') NOT NULL,
    `quantity` INT NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- DATA AWAL TOKO BUKET (SEED DATA)
-- Kredensial default:
-- admin / admin123  (Owner Toko Buket)
-- staff / staff123  (Florist & Kasir)
-- ========================================================

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

INSERT INTO `user_profiles` (`user_id`, `full_name`, `phone`, `address`) VALUES
(1, 'Cantika Owner PastelBloom', '081298765432', 'Studio PastelBloom Lantai 1, Jakarta Selatan'),
(2, 'Nadia Lead Florist', '087812345678', 'Workshop Rangkaian Buket & Packaging');

-- 4 Kategori Wajib Sesuai Permintaan User: Bunga, Snack, Uang, Boneka (+ Bahan Baku)
INSERT INTO `categories` (`id`, `name`, `icon`, `description`) VALUES
(1, 'Buket Bunga', '🌸', 'Rangkaian mawar, tulip, hydrangea, baby breath artificial & segar'),
(2, 'Buket Snack', '🍫', 'Rangkaian cokelat SilverQueen, Ferrero, Pocky, dan aneka snack manis'),
(3, 'Buket Uang', '💸', 'Buket uang asli pecahan 50rb & 100rb, surprise box tarik tunai'),
(4, 'Buket Boneka', '🧸', 'Buket boneka wisuda teddy bear toga, kelinci pastel, dan graduation edition');

-- Data Supplier Mitra Toko Buket
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'FreshFlora Rawa Belong', 'Haji Ridwan', '021-5321890', 'order@freshflora.id', 'Pasar Bunga Rawa Belong Los A-12, Jakarta Barat'),
(2, 'Grosir Snack Sejahtera Mandiri', 'Ibu Meilani', '021-6789123', 'sales@snacksejahtera.com', 'Kawasan Pergudangan Pluit Blok C4, Jakarta Utara'),
(3, 'Grosir Boneka Ceria Nusantara', 'Agus Santoso', '022-7819001', 'info@bonekaceria.co.id', 'Sentra Industri Boneka Cibaduyut, Bandung'),
(4, 'Toko Florist Wrap & Ribbon Pastel', 'Devi Anggraini', '0811889922', 'wrapping@pastelsupplies.id', 'Jl. Gandaria Tengah No. 18, Jakarta Selatan');

-- Data Produk Buket Sesuai 4 Kategori
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `price`, `stock`, `minimum_stock`, `description`) VALUES
-- Buket Bunga
(1, 1, 'BKT-FLW-001', 'Buket Mawar Pastel Pink Velvet 10 Tangkai', 185000.00, 15, 5, 'Rangkaian mawar baby pink dipadu baby breath dan kertas wrapping dusty pink.'),
(2, 1, 'BKT-FLW-002', 'Buket Korean Style Tulip Peach & Lilac Hydrangea', 245000.00, 8, 3, 'Kombinasi bunga tulip peach premium dengan sentuhan hydrangea ungu lembut.'),
(3, 1, 'BKT-FLW-003', 'Buket Baby Breath Rainbow Pastel Cloud', 135000.00, 3, 5, 'Bunga baby breath kering bernuansa pastel pelangi awet hingga tahunan.'),

-- Buket Snack
(4, 2, 'BKT-SNK-004', 'Buket Cokelat SilverQueen & Strawberry Pocky Pink', 145000.00, 20, 6, 'Perpaduan 3 batang SilverQueen chunky dan 2 pack Pocky Strawberry cantik.'),
(5, 2, 'BKT-SNK-005', 'Buket Ferrero Rocher Luxury Golden Rose (12 Pcs)', 285000.00, 6, 4, '12 butir Ferrero Rocher dibungkus jaring emas dan kertas celophane pastel mint.'),

-- Buket Uang
(6, 3, 'BKT-MNY-006', 'Buket Uang Asli 10 Lembar Pecahan 50 Ribu', 590000.00, 10, 3, 'Total uang Rp 500.000 dengan jasa wrapping premium Rp 90.000 (aman terplastik).'),
(7, 3, 'BKT-MNY-007', 'Buket Uang Tarik Surprise Box 20 Lembar Pecahan 100 Ribu', 2150000.00, 4, 2, 'Total uang Rp 2.000.000 model tarik kejutan untuk ulang tahun & anniversary.'),

-- Buket Boneka
(8, 4, 'BKT-DOL-008', 'Buket Boneka Teddy Bear Wisuda Toga Sarjana Pastel', 165000.00, 18, 5, 'Boneka teddy bear lengkap dengan topi toga, pin nama, dan buket mini mawar.'),
(9, 4, 'BKT-DOL-009', 'Buket Boneka Kelinci Soft Lilac Cute Edition', 140000.00, 2, 5, 'Boneka kelinci telinga panjang dengan baju rajut lilac lembut (Stok Menipis!).');

-- Junction Table: product_suppliers (Relasi Many-to-Many)
INSERT INTO `product_suppliers` (`product_id`, `supplier_id`, `supplier_price`) VALUES
(1, 1, 110000.00),
(1, 4, 25000.00),
(2, 1, 150000.00),
(3, 1, 80000.00),
(4, 2, 95000.00),
(4, 4, 20000.00),
(5, 2, 190000.00),
(6, 4, 40000.00),
(7, 4, 60000.00),
(8, 3, 90000.00),
(8, 4, 25000.00),
(9, 3, 75000.00);

-- Data Transaksi Mutasi Awal (Barang Masuk & Keluar)
INSERT INTO `stock_movements` (`product_id`, `user_id`, `type`, `quantity`, `notes`, `created_at`) VALUES
(1, 1, 'IN', 20, 'Produksi batch awal buket mawar pastel', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 2, 'OUT', 5, 'Pesanan wisuda Universitas Indonesia', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 1, 'IN', 12, 'Restock bunga tulip dari FreshFlora', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(2, 2, 'OUT', 4, 'Pesanan kado anniversary pelanggan', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 1, 'IN', 25, 'Produksi buket snack Pocky SilverQueen', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 2, 'OUT', 5, 'Kado ulang tahun surprise sahabat', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 1, 'IN', 15, 'Penyusunan pesanan buket uang 50K', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(6, 2, 'OUT', 5, 'Pengiriman kurir pesanan seserahan lamaran', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(8, 1, 'IN', 25, 'Restock boneka wisuda batch sidang kelulusan', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 2, 'OUT', 7, 'Pesanan wisudawan fakultas kedokteran', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(9, 1, 'IN', 5, 'Sample buket boneka kelinci soft lilac', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(9, 2, 'OUT', 3, 'Hadiah kelulusan sekolah dasar', DATE_SUB(NOW(), INTERVAL 1 DAY));
