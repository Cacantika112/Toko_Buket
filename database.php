<?php
/**
 * Konfigurasi Database dengan PDO (Slide 18 & 27)
 * Studi Kasus: Toko Buket Pastel (Buket Bunga, Snack, Uang, Boneka)
 */

declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'toko_buket_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Native prepared statements aktif
];

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Fallback otomatis ke SQLite file lokal
    $sqlitePath = __DIR__ . '/../database/toko_buket.sqlite';
    $dsn = "sqlite:" . $sqlitePath;
    $pdo = new PDO($dsn, null, null, $options);
    
    $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='products'")->fetch();
    if (!$tableCheck) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'staff',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS user_profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                full_name TEXT NOT NULL,
                phone TEXT,
                address TEXT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                icon TEXT DEFAULT '🌸',
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS suppliers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                contact_person TEXT,
                phone TEXT,
                email TEXT,
                address TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                sku TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                price REAL NOT NULL DEFAULT 0,
                stock INTEGER NOT NULL DEFAULT 0,
                minimum_stock INTEGER NOT NULL DEFAULT 5,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
            );
            CREATE TABLE IF NOT EXISTS product_suppliers (
                product_id INTEGER NOT NULL,
                supplier_id INTEGER NOT NULL,
                supplier_price REAL,
                PRIMARY KEY (product_id, supplier_id),
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS stock_movements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('IN', 'OUT')),
                quantity INTEGER NOT NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
            );

            -- Seeding User Toko Buket
            INSERT OR IGNORE INTO users (id, username, password_hash, role) VALUES 
            (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
            (2, 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

            INSERT OR IGNORE INTO user_profiles (user_id, full_name, phone, address) VALUES
            (1, 'Cantika (Owner PastelBloom)', '081298765432', 'Studio PastelBloom Jakarta'),
            (2, 'Nadia (Florist Designer)', '087812345678', 'Workshop Rangkaian Buket');

            -- 4 Kategori Wajib: Bunga, Snack, Uang, Boneka
            INSERT OR IGNORE INTO categories (id, name, icon, description) VALUES
            (1, 'Buket Bunga', '🌸', 'Rangkaian mawar, tulip, hydrangea, baby breath artificial & segar'),
            (2, 'Buket Snack', '🍫', 'Rangkaian cokelat SilverQueen, Ferrero, Pocky, dan aneka snack manis'),
            (3, 'Buket Uang', '💸', 'Buket uang asli pecahan 50rb & 100rb, surprise box tarik tunai'),
            (4, 'Buket Boneka', '🧸', 'Buket boneka wisuda teddy bear toga, kelinci pastel, dan karakter');

            -- Suppliers
            INSERT OR IGNORE INTO suppliers (id, name, contact_person, phone, email, address) VALUES
            (1, 'FreshFlora Rawa Belong', 'Haji Ridwan', '021-5321890', 'order@freshflora.id', 'Jakarta Barat'),
            (2, 'Grosir Snack Sejahtera Mandiri', 'Ibu Meilani', '021-6789123', 'sales@snack.com', 'Jakarta Utara'),
            (3, 'Grosir Boneka Ceria Nusantara', 'Agus Santoso', '022-7819001', 'info@bonekaceria.com', 'Bandung'),
            (4, 'Toko Florist Wrap & Ribbon Pastel', 'Devi Anggraini', '0811889922', 'wrap@pastel.id', 'Jakarta Selatan');

            -- Produk Toko Buket
            INSERT OR IGNORE INTO products (id, category_id, sku, name, price, stock, minimum_stock, description) VALUES
            (1, 1, 'BKT-FLW-001', 'Buket Mawar Pastel Pink Velvet 10 Tangkai', 185000, 15, 5, 'Rangkaian mawar baby pink dipadu baby breath dan wrapping dusty pink.'),
            (2, 1, 'BKT-FLW-002', 'Buket Korean Style Tulip Peach & Lilac Hydrangea', 245000, 8, 3, 'Kombinasi bunga tulip peach premium dengan sentuhan hydrangea ungu lembut.'),
            (3, 1, 'BKT-FLW-003', 'Buket Baby Breath Rainbow Pastel Cloud', 135000, 3, 5, 'Bunga baby breath kering bernuansa pastel pelangi awet tahunan.'),
            (4, 2, 'BKT-SNK-004', 'Buket Cokelat SilverQueen & Strawberry Pocky Pink', 145000, 20, 6, 'Perpaduan 3 batang SilverQueen chunky dan 2 pack Pocky Strawberry cantik.'),
            (5, 2, 'BKT-SNK-005', 'Buket Ferrero Rocher Luxury Golden Rose (12 Pcs)', 285000, 6, 4, '12 butir Ferrero Rocher dibungkus jaring emas dan wrapping pastel mint.'),
            (6, 3, 'BKT-MNY-006', 'Buket Uang Asli 10 Lembar Pecahan 50 Ribu', 590000, 10, 3, 'Total uang Rp 500.000 dengan jasa wrapping premium Rp 90.000.'),
            (7, 3, 'BKT-MNY-007', 'Buket Uang Tarik Surprise Box 20 Lembar Pecahan 100 Ribu', 2150000, 4, 2, 'Total uang Rp 2.000.000 model tarik kejutan ulang tahun & anniversary.'),
            (8, 4, 'BKT-DOL-008', 'Buket Boneka Teddy Bear Wisuda Toga Sarjana Pastel', 165000, 18, 5, 'Boneka teddy bear lengkap dengan topi toga, pin nama, dan buket mini mawar.'),
            (9, 4, 'BKT-DOL-009', 'Buket Boneka Kelinci Soft Lilac Cute Edition', 140000, 2, 5, 'Boneka kelinci telinga panjang dengan rajut lilac lembut.');

            -- Junction Relations
            INSERT OR IGNORE INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES
            (1, 1, 110000), (1, 4, 25000), (2, 1, 150000), (3, 1, 80000), (4, 2, 95000),
            (4, 4, 20000), (5, 2, 190000), (6, 4, 40000), (7, 4, 60000), (8, 3, 90000),
            (8, 4, 25000), (9, 3, 75000);

            -- Initial Movements
            INSERT OR IGNORE INTO stock_movements (product_id, user_id, type, quantity, notes) VALUES
            (1, 1, 'IN', 20, 'Produksi batch awal buket mawar pastel'),
            (1, 2, 'OUT', 5, 'Pesanan wisuda Universitas Indonesia'),
            (4, 1, 'IN', 25, 'Produksi buket snack Pocky SilverQueen'),
            (4, 2, 'OUT', 5, 'Kado ulang tahun surprise sahabat'),
            (8, 1, 'IN', 25, 'Restock boneka wisuda batch sidang kelulusan'),
            (8, 2, 'OUT', 7, 'Pesanan wisudawan fakultas kedokteran'),
            (9, 1, 'IN', 5, 'Sample buket kelinci lilac'),
            (9, 2, 'OUT', 3, 'Hadiah kelulusan');
        ");
    }
}

return $pdo;
