<?php
/**
 * Header View Template — Tema Pastel Studio Buket (Slide 34)
 */
declare(strict_types=1);

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

$currentUser = current_user();
$pageTitle = $pageTitle ?? 'PastelBloom Studio — Toko Buket Bunga, Snack, Uang & Boneka';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Pertemuan 4 Mini Project</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Quicksand:wght@500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        display: ['Quicksand', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        pastel: {
                            pink: '#fdf2f8',
                            pinkDark: '#f472b6',
                            rose: '#ffe4e6',
                            roseDark: '#fb7185',
                            peach: '#fff7ed',
                            peachDark: '#fb923c',
                            lavender: '#f5f3ff',
                            lavenderDark: '#a78bfa',
                            mint: '#f0fdf4',
                            mintDark: '#34d399',
                            cream: '#fffdfa',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fffafb; }
        .font-display { font-family: 'Quicksand', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .pastel-shadow { box-shadow: 0 10px 30px -5px rgba(244, 114, 182, 0.15); }
    </style>
</head>
<body class="text-slate-800 min-h-screen flex flex-col antialiased selection:bg-pink-300 selection:text-pink-950">

    <!-- Pastel Top Navigation Bar -->
    <header class="bg-white/80 backdrop-blur-md border-b border-pink-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo Toko Buket -->
                <div class="flex items-center space-x-3">
                    <a href="../dashboard.php" class="flex items-center space-x-2.5 text-slate-800 font-extrabold text-xl tracking-tight">
                        <span class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-pink-400 via-rose-300 to-purple-300 flex items-center justify-center text-white text-xl shadow-md shadow-pink-200">
                            🌸
                        </span>
                        <div class="flex flex-col">
                            <span class="font-display font-bold text-lg text-slate-800 leading-tight">Pastel<span class="text-pink-500">Bloom</span></span>
                            <span class="text-[10px] text-pink-400 font-semibold tracking-wider uppercase">Bouquet &amp; Gift Studio</span>
                        </div>
                    </a>
                    <span class="text-[11px] bg-pink-50 text-pink-700 px-2.5 py-0.5 rounded-full border border-pink-200 hidden lg:inline font-medium">
                        Bunga 🌸 &bull; Snack 🍫 &bull; Uang 💸 &bull; Boneka 🧸
                    </span>
                </div>

                <!-- Navigation Links -->
                <?php if ($currentUser): ?>
                <nav class="hidden md:flex items-center space-x-1.5 text-xs font-semibold">
                    <a href="../dashboard.php" class="px-3.5 py-2 rounded-xl transition <?= str_contains($_SERVER['PHP_SELF'], 'dashboard.php') ? 'bg-pink-100 text-pink-700 font-bold' : 'text-slate-600 hover:bg-pink-50 hover:text-pink-600' ?>">Dashboard</a>
                    <a href="../products/index.php" class="px-3.5 py-2 rounded-xl transition <?= str_contains($_SERVER['PHP_SELF'], 'products') ? 'bg-pink-100 text-pink-700 font-bold' : 'text-slate-600 hover:bg-pink-50 hover:text-pink-600' ?>">Katalog Buket</a>
                    
                    <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="../categories/index.php" class="px-3.5 py-2 rounded-xl transition <?= str_contains($_SERVER['PHP_SELF'], 'categories') ? 'bg-pink-100 text-pink-700 font-bold' : 'text-slate-600 hover:bg-pink-50 hover:text-pink-600' ?>">Kategori Buket</a>
                    <a href="../suppliers/index.php" class="px-3.5 py-2 rounded-xl transition <?= str_contains($_SERVER['PHP_SELF'], 'suppliers') ? 'bg-pink-100 text-pink-700 font-bold' : 'text-slate-600 hover:bg-pink-50 hover:text-pink-600' ?>">Supplier Bahan</a>
                    <?php endif; ?>

                    <div class="relative group">
                        <button class="px-3.5 py-2 rounded-xl text-slate-600 hover:bg-pink-50 hover:text-pink-600 flex items-center space-x-1 transition">
                            <span>Mutasi Stok</span>
                            <svg class="w-3.5 h-3.5 ml-1 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="absolute left-0 mt-1 w-48 bg-white text-slate-800 rounded-2xl shadow-xl border border-pink-100 py-1.5 hidden group-hover:block transition z-50">
                            <a href="../stock/in.php" class="block px-4 py-2 text-xs hover:bg-emerald-50 text-emerald-700 font-medium flex items-center">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span> + Buket Masuk (IN)
                            </a>
                            <a href="../stock/out.php" class="block px-4 py-2 text-xs hover:bg-rose-50 text-rose-700 font-medium flex items-center">
                                <span class="w-2 h-2 rounded-full bg-rose-400 mr-2"></span> - Buket Keluar (OUT)
                            </a>
                            <div class="border-t border-pink-50 my-1"></div>
                            <a href="../stock/movements.php" class="block px-4 py-2 text-xs hover:bg-slate-50 text-slate-600">Histori Pergerakan</a>
                        </div>
                    </div>

                    <a href="../reports/index.php" class="px-3.5 py-2 rounded-xl transition <?= str_contains($_SERVER['PHP_SELF'], 'reports') ? 'bg-pink-100 text-pink-700 font-bold' : 'text-slate-600 hover:bg-pink-50 hover:text-pink-600' ?>">Laporan Penjualan</a>
                </nav>

                <!-- User Profile & Logout -->
                <div class="flex items-center space-x-3">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-bold text-slate-700"><?= e($currentUser['full_name']) ?></div>
                        <span class="inline-block px-2 py-0.5 text-[10px] rounded-full font-bold <?= $currentUser['role'] === 'admin' ? 'bg-purple-100 text-purple-700 border border-purple-200' : 'bg-pink-100 text-pink-700 border border-pink-200' ?>">
                            <?= $currentUser['role'] === 'admin' ? '👑 OWNER / ADMIN' : '✂️ FLORIST STAFF' ?>
                        </span>
                    </div>
                    <a href="../logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-3 py-1.5 rounded-xl text-xs font-bold transition border border-rose-200 flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Logout
                    </a>
                </div>
                <?php else: ?>
                <div class="flex items-center space-x-2">
                    <a href="login.php" class="text-xs bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-xl font-bold shadow-md shadow-pink-200 transition">Login Florist</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Flash Messages Pastel -->
        <?php if ($successMsg = get_flash('success')): ?>
        <div class="mb-5 bg-emerald-50/90 border-l-4 border-emerald-400 p-4 rounded-r-2xl shadow-sm flex items-center justify-between text-xs font-medium text-emerald-800">
            <div class="flex items-center">
                <span class="text-base mr-2.5">✨</span>
                <p><?= e($successMsg) ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
        <?php endif; ?>

        <?php if ($errorMsg = get_flash('error')): ?>
        <div class="mb-5 bg-rose-50/90 border-l-4 border-rose-400 p-4 rounded-r-2xl shadow-sm flex items-center justify-between text-xs font-medium text-rose-800">
            <div class="flex items-center">
                <span class="text-base mr-2.5">⚠️</span>
                <p><?= e($errorMsg) ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
        </div>
        <?php endif; ?>
