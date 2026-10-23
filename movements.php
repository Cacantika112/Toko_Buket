<?php
/**
 * Histori Mutasi Stok (Audit Log Transaksi) (Slide 10, 25, 26)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

require_login();
$pageTitle = 'Histori Mutasi Stok';

$typeFilter = $_GET['type'] ?? '';
$productIdFilter = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT) ?: null;

$where = [];
$params = [];

if (in_array($typeFilter, ['IN', 'OUT'], true)) {
    $where[] = "sm.type = :type";
    $params['type'] = $typeFilter;
}

if ($productIdFilter) {
    $where[] = "sm.product_id = :product_id";
    $params['product_id'] = $productIdFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements sm {$whereSql}");
$countStmt->execute($params);
$totalData = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalData / $perPage);

$sql = "
    SELECT sm.id, sm.type, sm.quantity, sm.notes, sm.created_at,
           p.name AS product_name, p.sku,
           u.username, up.full_name AS user_fullname
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN users u ON u.id = sm.user_id
    LEFT JOIN user_profiles up ON up.user_id = u.id
    {$whereSql}
    ORDER BY sm.created_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(":{$k}", $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$movements = $stmt->fetchAll();

$allProducts = $pdo->query("SELECT id, name, sku FROM products ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../../views/header.php';
?>

<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Histori Mutasi Stok</h1>
            <p class="text-xs text-slate-500 mt-1">Audit log seluruh transaksi barang masuk dan keluar dengan relasi user &amp; produk</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="in.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-xl font-bold text-xs shadow-sm transition flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + Barang Masuk
            </a>
            <a href="out.php" class="bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-2 rounded-xl font-bold text-xs shadow-sm transition flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                - Barang Keluar
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
        <form method="GET" action="movements.php" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tipe Transaksi</label>
                <select name="type" class="w-full px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Semua Tipe (IN &amp; OUT)</option>
                    <option value="IN" <?= $typeFilter === 'IN' ? 'selected' : '' ?>>Barang Masuk (IN)</option>
                    <option value="OUT" <?= $typeFilter === 'OUT' ? 'selected' : '' ?>>Barang Keluar (OUT)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Filter Produk</label>
                <select name="product_id" class="w-full px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Semua Produk</option>
                    <?php foreach ($allProducts as $ap): ?>
                    <option value="<?= $ap['id'] ?>" <?= $productIdFilter === (int)$ap['id'] ? 'selected' : '' ?>>
                        <?= e($ap['sku']) ?> - <?= e($ap['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-3 rounded-xl text-xs transition">
                    Filter Log
                </button>
                <a href="movements.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold py-2 px-3 rounded-xl text-xs transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-3 px-4">ID / Waktu</th>
                        <th class="py-3 px-4">Tipe Mutasi</th>
                        <th class="py-3 px-4">Produk</th>
                        <th class="py-3 px-4 text-right">Jumlah</th>
                        <th class="py-3 px-4">Eksekutor (User)</th>
                        <th class="py-3 px-4">Catatan Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <?php if (empty($movements)): ?>
                    <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada catatan mutasi stok sesuai filter.</td></tr>
                    <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                            <span class="text-slate-400">#<?= $m['id'] ?></span><br>
                            <?= format_date($m['created_at']) ?>
                        </td>
                        <td class="py-3.5 px-4">
                            <?php if ($m['type'] === 'IN'): ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> +IN MASUK
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span> -OUT KELUAR
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-800 text-sm"><?= e($m['product_name']) ?></div>
                            <span class="font-mono text-[11px] text-slate-400"><?= e($m['sku']) ?></span>
                        </td>
                        <td class="py-3.5 px-4 text-right font-black text-sm <?= $m['type'] === 'IN' ? 'text-emerald-600' : 'text-rose-600' ?>">
                            <?= $m['type'] === 'IN' ? '+' : '-' ?><?= number_format($m['quantity']) ?>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-semibold text-slate-700"><?= e($m['user_fullname'] ?? $m['username']) ?></div>
                            <span class="text-[10px] text-slate-400 font-mono">@<?= e($m['username']) ?></span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 italic max-w-sm">
                            <?= e($m['notes'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-5 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
            <div>Halaman <span class="font-bold text-slate-700"><?= $page ?></span> dari <span class="font-bold text-slate-700"><?= $totalPages ?></span></div>
            <div class="flex space-x-1">
                <?php $qp = $_GET; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): 
                    $qp['page'] = $i;
                ?>
                <a href="?<?= http_build_query($qp) ?>" class="px-3 py-1.5 rounded-lg border <?= $i === $page ? 'border-indigo-600 bg-indigo-600 text-white font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
