<?php
/**
 * Hapus Produk dengan Proteksi Integritas Referensial (Slide 9 & 33)
 * Aturan Bisnis: "Produk yang memiliki histori transaksi tidak boleh dihapus sembarangan."
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('error', 'ID Produk tidak valid.');
    header('Location: index.php');
    exit;
}

// 1. Cek apakah ada histori transaksi di stock_movements (Slide 9 & 33)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE product_id = :id");
$stmt->execute(['id' => $id]);
$movementCount = (int) $stmt->fetchColumn();

if ($movementCount > 0) {
    set_flash('error', "Gagal menghapus! Produk ini memiliki {$movementCount} data histori mutasi transaksi stok (Foreign Key RESTRICT). Aturan bisnis melarang penghapusan data master yang memiliki jejak audit transaksi.");
    header('Location: index.php');
    exit;
}

// 2. Jika aman (tidak ada pergerakan stok), lakukan penghapusan
try {
    $stmtDel = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmtDel->execute(['id' => $id]);
    set_flash('success', 'Produk berhasil dihapus dari sistem.');
} catch (PDOException $e) {
    set_flash('error', 'Database Foreign Key Constraint Error: ' . $e->getMessage());
}

header('Location: index.php');
exit;
