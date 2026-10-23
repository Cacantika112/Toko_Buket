<?php
/**
 * Edit Produk dan Relasi Supplier (Slide 8, 11, 21)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin');
$pageTitle = 'Edit Produk';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('error', 'ID Produk tidak valid.');
    header('Location: index.php');
    exit;
}

// Ambil data produk
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();
if (!$product) {
    set_flash('error', 'Produk tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$suppliers  = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

// Ambil supplier yang sudah terhubung
$stmtSup = $pdo->prepare("SELECT supplier_id FROM product_suppliers WHERE product_id = :id");
$stmtSup->execute(['id' => $id]);
$currentSuppliers = $stmtSup->fetchAll(PDO::FETCH_COLUMN);

$errors = [];
$sku = $product['sku'];
$name = $product['name'];
$categoryId = $product['category_id'];
$price = $product['price'];
$minimumStock = $product['minimum_stock'];
$selectedSuppliers = $currentSuppliers;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $price = $_POST['price'] ?? '';
    $minimumStock = $_POST['minimum_stock'] ?? '5';
    $selectedSuppliers = $_POST['suppliers'] ?? [];

    if ($err = validate_string($sku, 'SKU', 3, 50)) $errors[] = $err;
    if ($err = validate_string($name, 'Nama Produk', 3, 150)) $errors[] = $err;
    if (!$categoryId) $errors[] = 'Kategori produk wajib dipilih.';
    if ($err = validate_non_negative_number($price, 'Harga')) $errors[] = $err;
    if ($err = validate_positive_int($minimumStock, 'Minimum Stok', 0)) $errors[] = $err;
    if ($err = validate_unique_sku($pdo, $sku, $id)) $errors[] = $err;

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update produk (Stok tidak diubah di sini langsung karena harus lewat transaksi mutasi IN/OUT)
            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET category_id = :category_id, sku = :sku, name = :name, price = :price, minimum_stock = :minimum_stock
                WHERE id = :id
            ");
            $updateStmt->execute([
                'category_id'   => $categoryId,
                'sku'           => $sku,
                'name'          => $name,
                'price'         => (float) $price,
                'minimum_stock' => (int) $minimumStock,
                'id'            => $id,
            ]);

            // Sync junction table suppliers
            $pdo->prepare("DELETE FROM product_suppliers WHERE product_id = :id")->execute(['id' => $id]);
            if (!empty($selectedSuppliers) && is_array($selectedSuppliers)) {
                $insSup = $pdo->prepare("INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES (:product_id, :supplier_id, :price)");
                foreach ($selectedSuppliers as $sId) {
                    $insSup->execute([
                        'product_id'  => $id,
                        'supplier_id' => (int) $sId,
                        'price'       => (float) $price * 0.85,
                    ]);
                }
            }

            $pdo->commit();
            set_flash('success', "Produk '{$name}' berhasil diperbarui.");
            header('Location: index.php');
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal memperbarui produk: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../views/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="index.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center mb-1">
                &larr; Kembali ke Daftar Produk
            </a>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Edit Data Produk</h1>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="mb-5 bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl">
        <ul class="list-disc list-inside text-xs text-rose-700 space-y-0.5">
            <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= $id ?>" class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200/80 shadow-sm space-y-5">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kode SKU</label>
                <input type="text" name="sku" value="<?= e($sku) ?>" required 
                       class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori (Relasi One-to-Many)</label>
                <select name="category_id" required class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int)$categoryId === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Produk</label>
            <input type="text" name="name" value="<?= e($name) ?>" required 
                   class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Harga Jual (Rp)</label>
                <input type="number" step="0.01" min="0" name="price" value="<?= e((string)$price) ?>" required 
                       class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Stok Saat Ini (Terkunci)</label>
                <input type="text" value="<?= $product['stock'] ?> unit" disabled 
                       class="w-full px-4 py-2.5 bg-slate-100 rounded-xl border border-slate-200 text-sm text-slate-500 font-bold cursor-not-allowed">
                <p class="text-[10px] text-slate-400 mt-0.5">Ubah stok melalui menu Transaksi.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Ambang Stok Minimum</label>
                <input type="number" min="0" name="minimum_stock" value="<?= e((string)$minimumStock) ?>" required 
                       class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>

        <!-- Suppliers Junction Checkboxes -->
        <div class="pt-3 border-t border-slate-100">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Pemasok / Supplier Produk (Many-to-Many Junction)
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                <?php foreach ($suppliers as $s): ?>
                <label class="flex items-center space-x-2.5 text-xs text-slate-700 p-2 hover:bg-white rounded-xl cursor-pointer transition">
                    <input type="checkbox" name="suppliers[]" value="<?= $s['id'] ?>" 
                           <?= in_array($s['id'], $selectedSuppliers) ? 'checked' : '' ?>
                           class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <span class="font-medium"><?= e($s['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <a href="index.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs hover:bg-slate-50 transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition">
                Simpan Perubahan
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
