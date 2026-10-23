<?php
/**
 * Dashboard Pastel Toko Buket (Slide 29: Dashboard yang Diharapkan)
 * Menampilkan statistik agregat: Total Buket, Nilai Stok, Stok Menipis, Buket Masuk, Buket Terjual/Keluar
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

require_login();
$pageTitle = 'Dashboard Florist — PastelBloom Studio';

// 1. Total Produk Buket (COUNT)
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = (int) $stmt->fetch()['total'];

// 2. Nilai Inventory (SUM stock * price)
$stmt = $pdo->query("SELECT COALESCE(SUM(stock * price), 0) AS total_value FROM products");
$totalInventoryValue = (float) $stmt->fetch()['total_value'];

// 3. Produk Stok Rendah (COUNT where stock <= minimum_stock)
$stmt = $pdo->query("SELECT COUNT(*) AS low_count FROM products WHERE stock <= minimum_stock");
$lowStockCount = (int) $stmt->fetch()['low_count'];

// 4. Total Buket Masuk & Keluar (Aggregate dari stock_movements)
$stmt = $pdo->query("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END), 0) AS total_in,
        COALESCE(SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END), 0) AS total_out
    FROM stock_movements
");
$movementStats = $stmt->fetch();
$totalIn = (int) $movementStats['total_in'];
$totalOut = (int) $movementStats['total_out'];

// Ambil produk stok rendah
$lowStockProducts = $pdo->query("
    SELECT p.id, p.sku, p.name, p.stock, p.minimum_stock, c.name AS category_name, c.icon AS category_icon
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.stock <= p.minimum_stock
    ORDER BY p.stock ASC
    LIMIT 5
")->fetchAll();

// Ambil transaksi histori mutasi terbaru
$recentMovements = $pdo->query("
    SELECT sm.id, sm.type, sm.quantity, sm.notes, sm.created_at,
           p.name AS product_name, p.sku,
           u.username
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN users u ON u.id = sm.user_id
    ORDER BY sm.created_at DESC
    LIMIT 6
")->fetchAll();

// Hitung rekap per 4 Kategori Wajib
$catSummary = $pdo->query("
    SELECT c.name, c.icon, COUNT(p.id) AS count, COALESCE(SUM(p.stock), 0) as stock
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.icon
")->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

<div class="space-y-6">

    <!-- Pastel Greeting Hero Banner -->
    <div class="bg-gradient-to-r from-pink-300 via-rose-200 to-purple-200 rounded-3xl p-6 md:p-8 text-slate-800 shadow-sm border border-pink-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 bg-white/70 backdrop-blur-sm text-pink-700 text-xs px-3 py-1 rounded-full mb-3 border border-pink-200 font-semibold">
                <span>🌸</span>
                <span>Studio Toko Buket Aktif &bull; Database Relasional</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold font-display tracking-tight text-slate-800">
                Halo, <?= e($currentUser['full_name']) ?>! ✨
            </h1>
            <p class="text-slate-600 text-xs md:text-sm mt-1 max-w-xl">
                Selamat datang di sistem manajemen stok <strong>PastelBloom Studio</strong>. Pantau ketersediaan Buket Bunga 🌸, Buket Snack 🍫, Buket Uang 💸, dan Buket Boneka 🧸 dengan business logic &amp; transaksi terintegrasi.
            </p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="stock/in.php" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2.5 rounded-2xl text-xs shadow-md shadow-emerald-200 transition flex items-center">
                <span class="mr-1.5">+</span> Catat Buket Masuk (IN)
            </a>
            <a href="stock/out.php" class="bg-pink-500 hover:bg-pink-600 text-white font-bold px-4 py-2.5 rounded-2xl text-xs shadow-md shadow-pink-200 transition flex items-center">
                <span class="mr-1.5">-</span> Catat Buket Keluar (OUT)
            </a>
        </div>
    </div>

    <!-- 4 Kategori Highlights -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php foreach ($catSummary as $cs): ?>
        <div class="bg-white rounded-2xl p-3.5 border border-pink-100 shadow-sm flex items-center space-x-3">
            <span class="text-2xl p-2 rounded-xl bg-pink-50"><?= $cs['icon'] ?? '🌸' ?></span>
            <div>
                <div class="text-xs font-bold text-slate-700"><?= e($cs['name']) ?></div>
                <div class="text-[11px] text-pink-500 font-semibold"><?= $cs['stock'] ?> unit tersedia</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 5 Key Aggregates Cards (Slide 29) Pastel Styled -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- Widget 1: Total Koleksi Buket -->
        <div class="bg-white rounded-2xl p-5 border border-pink-100 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Desain Buket</span>
                <span class="p-2 bg-pink-50 text-pink-500 rounded-xl text-xs">🌸</span>
            </div>
            <div class="text-2xl font-black text-slate-800"><?= number_format($totalProducts) ?> Desain</div>
            <p class="text-[10px] text-slate-400 mt-1 font-mono">COUNT(products.id)</p>
        </div>

        <!-- Widget 2: Nilai Total Inventory -->
        <div class="bg-white rounded-2xl p-5 border border-pink-100 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nilai Aset Stok</span>
                <span class="p-2 bg-purple-50 text-purple-500 rounded-xl text-xs">💎</span>
            </div>
            <div class="text-lg font-black text-purple-700 truncate" title="<?= format_rupiah($totalInventoryValue) ?>">
                <?= format_rupiah($totalInventoryValue) ?>
            </div>
            <p class="text-[10px] text-slate-400 mt-1 font-mono">SUM(stock * price)</p>
        </div>

        <!-- Widget 3: Stok Menipis -->
        <div class="bg-white rounded-2xl p-5 border <?= $lowStockCount > 0 ? 'border-rose-300 bg-rose-50/40' : 'border-pink-100' ?> shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Buket Stok Menipis</span>
                <span class="p-2 bg-rose-50 text-rose-500 rounded-xl text-xs">⚠️</span>
            </div>
            <div class="text-2xl font-black <?= $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-800' ?>">
                <?= number_format($lowStockCount) ?> Produk
            </div>
            <p class="text-[10px] text-slate-400 mt-1 font-mono">stock &le; minimum_stock</p>
        </div>

        <!-- Widget 4: Buket Masuk / Diproduksi -->
        <div class="bg-white rounded-2xl p-5 border border-pink-100 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Buket Masuk (+IN)</span>
                <span class="p-2 bg-emerald-50 text-emerald-500 rounded-xl text-xs">📦</span>
            </div>
            <div class="text-2xl font-black text-emerald-600">+<?= number_format($totalIn) ?></div>
            <p class="text-[10px] text-slate-400 mt-1 font-mono">SUM(qty) WHERE IN</p>
        </div>

        <!-- Widget 5: Buket Terjual / Keluar -->
        <div class="bg-white rounded-2xl p-5 border border-pink-100 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Buket Keluar (-OUT)</span>
                <span class="p-2 bg-rose-50 text-rose-500 rounded-xl text-xs">🎀</span>
            </div>
            <div class="text-2xl font-black text-rose-600">-<?= number_format($totalOut) ?></div>
            <p class="text-[10px] text-slate-400 mt-1 font-mono">SUM(qty) WHERE OUT</p>
        </div>
    </div>

    <!-- 2 Column Section: Stok Menipis & Audit Log Transaksi -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Kolom Kiri: Peringatan Stok Menipis -->
        <div class="bg-white rounded-3xl p-6 border border-pink-100 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-400 mr-2"></span>
                        Perlu Restock Bahan / Rangkaian
                    </h2>
                    <span class="text-[11px] font-semibold bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">
                        <?= count($lowStockProducts) ?> Buket
                    </span>
                </div>

                <?php if (empty($lowStockProducts)): ?>
                <div class="py-8 text-center text-slate-400 text-xs">
                    Semua stok buket dalam batas aman! 🌸
                </div>
                <?php else: ?>
                <div class="space-y-2.5">
                    <?php foreach ($lowStockProducts as $lp): ?>
                    <div class="p-3 bg-rose-50/50 border border-rose-100 rounded-2xl flex items-center justify-between">
                        <div>
                            <div class="font-bold text-xs text-slate-800"><?= e($lp['name']) ?></div>
                            <div class="text-[10px] text-slate-500 font-mono"><?= e($lp['sku']) ?> &bull; <?= e($lp['category_name']) ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-black text-rose-600"><?= $lp['stock'] ?> unit</div>
                            <div class="text-[10px] text-slate-400 font-mono">Min: <?= $lp['minimum_stock'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 pt-3 border-t border-pink-50">
                <a href="stock/in.php" class="text-xs font-bold text-pink-600 hover:text-pink-800 flex items-center justify-center">
                    + Restock Bahan &amp; Rangkaian Buket Sekarang &rarr;
                </a>
            </div>
        </div>

        <!-- Kolom Kanan: Histori Transaksi Mutasi (Slide 10 & 28) -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-pink-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-pink-500 mr-2"></span>
                    Histori Mutasi Stok Buket (Audit Log)
                </h2>
                <a href="stock/movements.php" class="text-xs font-bold text-pink-600 hover:text-pink-800">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-pink-100 text-slate-400 uppercase tracking-wider font-semibold text-[10px]">
                            <th class="py-2.5">Waktu</th>
                            <th class="py-2.5">Tipe</th>
                            <th class="py-2.5">Buket</th>
                            <th class="py-2.5 text-right">Jumlah</th>
                            <th class="py-2.5">Florist</th>
                            <th class="py-2.5">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-pink-50 text-slate-600">
                        <?php if (empty($recentMovements)): ?>
                        <tr><td colspan="6" class="py-4 text-center text-slate-400">Belum ada transaksi stok.</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentMovements as $m): ?>
                        <tr class="hover:bg-pink-50/40 transition">
                            <td class="py-2.5 font-mono text-[10px] text-slate-400"><?= format_date($m['created_at']) ?></td>
                            <td class="py-2.5">
                                <?php if ($m['type'] === 'IN'): ?>
                                <span class="bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full text-[9px]">+IN MASUK</span>
                                <?php else: ?>
                                <span class="bg-rose-100 text-rose-800 font-bold px-2 py-0.5 rounded-full text-[9px]">-OUT KELUAR</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 font-semibold text-slate-800">
                                <div><?= e($m['product_name']) ?></div>
                                <span class="text-[9px] text-slate-400 font-mono"><?= e($m['sku']) ?></span>
                            </td>
                            <td class="py-2.5 text-right font-black <?= $m['type'] === 'IN' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                <?= $m['type'] === 'IN' ? '+' : '-' ?><?= number_format($m['quantity']) ?>
                            </td>
                            <td class="py-2.5 text-slate-500 font-mono text-[11px]">@<?= e($m['username']) ?></td>
                            <td class="py-2.5 text-slate-400 italic max-w-xs truncate text-[11px]"><?= e($m['notes'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Formula Business Rules Note (Slide 4) -->
    <div class="bg-gradient-to-r from-pink-50 via-purple-50 to-amber-50 text-slate-700 rounded-3xl p-5 flex flex-col md:flex-row items-center justify-between gap-4 border border-pink-200">
        <div class="flex items-center space-x-3">
            <span class="w-10 h-10 rounded-2xl bg-pink-100 text-pink-600 flex items-center justify-center shrink-0 font-bold text-lg">🌸</span>
            <div>
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Prinsip Konsistensi Stok Buket (Slide 4):</h4>
                <p class="text-xs text-pink-700 font-mono mt-0.5 font-semibold">stok akhir = stok awal + total buket masuk &minus; total buket keluar</p>
            </div>
        </div>
        <div class="text-[11px] text-slate-500 bg-white px-4 py-2 rounded-2xl border border-pink-100 shadow-sm">
            Dilindungi transaksi database <code class="text-pink-600 font-bold">PDO::beginTransaction()</code> &amp; validasi ketat anti stok negatif
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../views/footer.php'; ?>
