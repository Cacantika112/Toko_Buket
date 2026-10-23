<?php
/**
 * Transaksi Buket Masuk (+IN) — Pastel Theme (Slide 25 & 27)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_login();
$pageTitle = 'Catat Buket Masuk (+IN) — PastelBloom';

$products = $pdo->query("SELECT id, sku, name, stock FROM products ORDER BY name ASC")->fetchAll();
$selectedProductId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT) ?: 0;

$errors = [];
$quantity = '';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedProductId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = $_POST['quantity'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!$selectedProductId) {
        $errors[] = 'Silakan pilih buket yang akan ditambah stoknya.';
    }
    if ($err = validate_positive_int($quantity, 'Jumlah Buket Masuk', 1)) {
        $errors[] = $err;
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $checkStmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = :id");
            $checkStmt->execute(['id' => $selectedProductId]);
            $prod = $checkStmt->fetch();
            if (!$prod) throw new Exception("Buket tidak ditemukan.");

            $qtyInt = (int) $quantity;

            // Tambah stok buket
            $updateStmt = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :id");
            $updateStmt->execute(['qty' => $qtyInt, 'id' => $selectedProductId]);

            // Histori mutasi audit
            $histStmt = $pdo->prepare("
                INSERT INTO stock_movements (product_id, user_id, type, quantity, notes)
                VALUES (:product_id, :user_id, 'IN', :quantity, :notes)
            ");
            $histStmt->execute([
                'product_id' => $selectedProductId,
                'user_id'    => current_user()['id'],
                'quantity'   => $qtyInt,
                'notes'      => !empty($notes) ? $notes : 'Produksi rangkaian buket masuk',
            ]);

            $pdo->commit();

            $stokBaru = $prod['stock'] + $qtyInt;
            set_flash('success', "Sukses! Stok buket '{$prod['name']}' berhasil ditambah {$qtyInt} unit. Total stok saat ini: {$stokBaru} unit. 🌸");
            header('Location: movements.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Transaksi Gagal: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../views/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="movements.php" class="text-xs font-bold text-pink-600 hover:text-pink-800 flex items-center mb-1">
                &larr; Histori Mutasi Toko Buket
            </a>
            <h1 class="text-2xl font-bold font-display text-slate-800 tracking-tight">Catat Buket Masuk (+IN)</h1>
            <p class="text-xs text-slate-500 mt-1">Penerimaan stok rangkaian buket baru atau restock bahan florist (Slide 25)</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="mb-5 bg-rose-50 border-l-4 border-rose-400 p-4 rounded-r-2xl">
        <h4 class="text-xs font-bold text-rose-800 uppercase tracking-wider mb-1">Peringatan:</h4>
        <ul class="list-disc list-inside text-xs text-rose-700 space-y-0.5">
            <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="in.php" class="bg-white rounded-3xl p-6 md:p-8 border border-pink-100 shadow-sm space-y-5">
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Produk Buket <span class="text-rose-500">*</span></label>
            <select name="product_id" id="product_id" required onchange="updateStockPreview()" class="w-full px-4 py-2.5 bg-pink-50/30 rounded-2xl border border-pink-200 text-xs focus:ring-2 focus:ring-pink-400 focus:outline-none font-medium">
                <option value="">-- Pilih Buket --</option>
                <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>" <?= (int)$selectedProductId === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= e($p['sku']) ?> &mdash; <?= e($p['name']) ?> (Stok saat ini: <?= $p['stock'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl flex items-center justify-between text-xs">
            <div>
                <span class="text-emerald-800 font-bold block">Formula Konsistensi Stok (Slide 4):</span>
                <span class="text-emerald-700 font-mono text-[11px]">stok_akhir = stok_sekarang + jumlah_masuk</span>
            </div>
            <div class="text-right">
                <span class="text-slate-400 block text-[10px]">Perkiraan Stok Baru</span>
                <span id="stok_preview" class="text-emerald-700 font-black text-base">-</span>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jumlah Buket Masuk <span class="text-rose-500">*</span></label>
            <input type="number" min="1" name="quantity" id="quantity" value="<?= e((string)$quantity) ?>" placeholder="Contoh: 5" required oninput="updateStockPreview()"
                   class="w-full px-4 py-2.5 bg-pink-50/30 rounded-2xl border border-pink-200 text-sm focus:ring-2 focus:ring-emerald-400 focus:outline-none font-bold">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan / Keterangan Masuk</label>
            <textarea name="notes" rows="2" placeholder="Selesai dirangkai florist, restock supplier FreshFlora..." 
                      class="w-full px-4 py-2.5 bg-pink-50/30 rounded-2xl border border-pink-200 text-xs focus:ring-2 focus:ring-pink-400 focus:outline-none"><?= e($notes) ?></textarea>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-pink-50">
            <a href="../dashboard.php" class="px-5 py-2.5 rounded-2xl border border-pink-200 text-slate-600 font-bold text-xs hover:bg-pink-50 transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-2xl shadow-md shadow-emerald-200 transition flex items-center">
                <span class="mr-1.5 text-base">+</span> Simpan Buket Masuk
            </button>
        </div>
    </form>
</div>

<script>
function updateStockPreview() {
    const sel = document.getElementById('product_id');
    const opt = sel.options[sel.selectedIndex];
    const qtyInput = document.getElementById('quantity');
    const preview = document.getElementById('stok_preview');

    if (opt && opt.dataset.stock !== undefined) {
        const curStock = parseInt(opt.dataset.stock, 10);
        const qty = parseInt(qtyInput.value, 10) || 0;
        preview.textContent = (curStock + qty) + ' unit';
    } else {
        preview.textContent = '-';
    }
}
updateStockPreview();
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
