<?php
/**
 * Tambah Produk Baru dengan Junction Table Suppliers (Slide 8, 11, 21)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_role('admin'); // Slide 24: Admin only
$pageTitle = 'Tambah Produk Baru';

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$suppliers  = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

$errors = [];
$sku = '';
$name = '';
$categoryId = '';
$price = '';
$stock = '0';
$minimumStock = '5';
$selectedSuppliers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '0';
    $minimumStock = $_POST['minimum_stock'] ?? '5';
    $selectedSuppliers = $_POST['suppliers'] ?? [];

    // Validasi
    if ($err = validate_string($sku, 'SKU', 3, 50)) $errors[] = $err;
    if ($err = validate_string($name, 'Nama Produk', 3, 150)) $errors[] = $err;
    if (!$categoryId) $errors[] = 'Kategori produk wajib dipilih.';
    if ($err = validate_non_negative_number($price, 'Harga')) $errors[] = $err;
    if ($err = validate_positive_int($stock, 'Stok Awal', 0)) $errors[] = $err;
    if ($err = validate_positive_int($minimumStock, 'Minimum Stok', 0)) $errors[] = $err;
    if ($err = validate_unique_sku($pdo, $sku)) $errors[] = $err;

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Insert ke tabel products
            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, sku, name, price, stock, minimum_stock)
                VALUES (:category_id, :sku, :name, :price, :stock, :minimum_stock)
            ");
            $stmt->execute([
                'category_id'   => $categoryId,
                'sku'           => $sku,
                'name'          => $name,
                'price'         => (float) $price,
                'stock'         => (int) $stock,
                'minimum_stock' => (int) $minimumStock,
            ]);
            $productId = (int) $pdo->lastInsertId();

            // 2. Insert ke junction table product_suppliers (Many-to-Many)
            if (!empty($selectedSuppliers) && is_array($selectedSuppliers)) {
                $supStmt = $pdo->prepare("
                    INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
                    VALUES (:product_id, :supplier_id, :supplier_price)
                ");
                foreach ($selectedSuppliers as $supId) {
                    $supId = (int) $supId;
                    if ($supId > 0) {
                        $supStmt->execute([
                            'product_id'     => $productId,
                            'supplier_id'    => $supId,
                            'supplier_price' => (float) $price * 0.85, // perkiraan harga beli
                        ]);
                    }
                }
            }

            // 3. Jika ada stok awal > 0, catat histori stock_movements (IN)
            if ((int)$stock > 0) {
                $mvStmt = $pdo->prepare("
                    INSERT INTO stock_movements (product_id, user_id, type, quantity, notes)
                    VALUES (:product_id, :user_id, 'IN', :quantity, 'Stok awal pendaftaran produk')
                ");
                $mvStmt->execute([
                    'product_id' => $productId,
                    'user_id'    => current_user()['id'],
                    'quantity'   => (int) $stock,
                ]);
            }

            $pdo->commit();
            set_flash('success', "Produk '{$name}' berhasil ditambahkan beserta relasi database.");
            header('Location: index.php');
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan produk: ' . $e->getMessage();
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
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Tambah Produk Baru</h1>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="mb-5 bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl">
        <h4 class="text-xs font-bold text-rose-800 uppercase tracking-wider mb-1">Kesalahan Validasi:</h4>
        <ul class="list-disc list-inside text-xs text-rose-700 space-y-0.5">
            <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="create.php" class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200/80 shadow-sm space-y-5">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kode SKU <span class="text-rose-500">*</span></label>
                <input type="text" name="sku" value="<?= e($sku) ?>" placeholder="Contoh: PRD-ACC-001" required 
                       class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono">
                <p class="text-[11px] text-slate-400 mt-1">Harus unik di database (Slide 33).</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori (Relasi One-to-Many) <span class="text-rose-500">*</span></label>
                <select name="category_id" required class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int)$categoryId === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Produk <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="<?= e($name) ?>" placeholder="Contoh: Mechanical Keyboard Wireless 75%" required 
                   class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Harga Jual (Rp) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="price" value="<?= e((string)$price) ?>" placeholder="350000" required 
                       class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Stok Awal</label>
                <input type="number" min="0" name="stock" value="<?= e((string)$stock) ?>" required 
                       class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Ambang Stok Minimum</label>
                <input type="number" min="0" name="minimum_stock" value="<?= e((string)$minimumStock) ?>" required 
                       class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>

        <!-- Relasi Many-to-Many: Suppliers Junction Table (Slide 8) -->
        <div class="pt-3 border-t border-slate-100">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                Pilih Supplier Pemasok (Relasi Many-to-Many)
            </label>
            <p class="text-[11px] text-slate-400 mb-2">Satu produk dapat disuplai oleh lebih dari satu supplier melalui junction table <code class="text-indigo-600 font-mono">product_suppliers</code>.</p>
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
                Simpan Produk &amp; Buat Relasi
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
