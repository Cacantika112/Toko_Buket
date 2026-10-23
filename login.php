<?php
/**
 * Login Page — Tema PastelBloom Toko Buket (Slide 22, 23)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$usernameInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = trim($_POST['username'] ?? '');
    $passwordInput = trim($_POST['password'] ?? '');

    if (empty($usernameInput) || empty($passwordInput)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        if (attempt_login($pdo, $usernameInput, $passwordInput)) {
            set_flash('success', 'Selamat datang kembali di Studio Buket, ' . current_user()['full_name'] . '! 🌸');
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Florist — PastelBloom Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Quicksand:wght@600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fdf4f7; }
        .font-display { font-family: 'Quicksand', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-pink-100 via-purple-50 to-amber-50">

    <div class="max-w-md w-full">
        <!-- Brand Header Toko Buket -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gradient-to-tr from-pink-400 to-rose-300 text-white shadow-xl shadow-pink-200 mb-3 text-3xl">
                🌸
            </div>
            <h1 class="text-2xl font-bold font-display text-slate-800 tracking-tight">Pastel<span class="text-pink-500">Bloom</span> Studio</h1>
            <p class="text-pink-600 text-xs mt-1 font-medium">Sistem Manajemen Toko Buket &bull; Bunga, Snack, Uang &amp; Boneka</p>
        </div>

        <!-- Login Card Pastel -->
        <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-xl shadow-pink-100/70 p-8 border border-pink-100">
            <div class="mb-5 text-center">
                <h2 class="text-lg font-bold text-slate-800">Masuk ke Portal Florist</h2>
                <p class="text-xs text-slate-400 mt-0.5">Kelola stok bunga, snack, uang &amp; pesanan buket</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="mb-4 bg-rose-50 border-l-4 border-rose-400 p-3 rounded-r-xl text-xs text-rose-700 flex items-center">
                <span class="mr-2 text-base">⚠️</span>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Username Florist</label>
                    <input type="text" name="username" value="<?= e($usernameInput) ?>" required autofocus 
                           placeholder="admin (owner) atau staff"
                           class="w-full px-4 py-2.5 rounded-xl border border-pink-200 focus:outline-none focus:ring-2 focus:ring-pink-400 text-xs bg-pink-50/30 transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Password</label>
                    <input type="password" name="password" required 
                           placeholder="••••••••"
                           class="w-full px-4 py-2.5 rounded-xl border border-pink-200 focus:outline-none focus:ring-2 focus:ring-pink-400 text-xs bg-pink-50/30 transition">
                </div>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-pink-400 to-rose-400 hover:from-pink-500 hover:to-rose-500 text-white font-bold rounded-xl shadow-lg shadow-pink-300/40 transition transform active:scale-95 text-xs">
                    Masuk Sekarang 🌸
                </button>
            </form>

            <!-- Quick Demo Credentials Box -->
            <div class="mt-6 pt-5 border-t border-pink-100">
                <p class="text-[11px] font-bold text-slate-500 mb-2 text-center">Pilih Akun Demo Pengujian:</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="fillLogin('admin', 'admin123')" class="p-2.5 rounded-2xl border border-purple-200 bg-purple-50/70 hover:bg-purple-100 text-purple-900 text-left transition">
                        <div class="font-bold flex items-center justify-between">
                            <span>👑 Owner Admin</span>
                        </div>
                        <div class="text-[10px] text-purple-600 mt-0.5 font-mono">admin | admin123</div>
                    </button>
                    <button type="button" onclick="fillLogin('staff', 'staff123')" class="p-2.5 rounded-2xl border border-pink-200 bg-pink-50/70 hover:bg-pink-100 text-pink-900 text-left transition">
                        <div class="font-bold flex items-center justify-between">
                            <span>✂️ Florist Staff</span>
                        </div>
                        <div class="text-[10px] text-pink-600 mt-0.5 font-mono">staff | staff123</div>
                    </button>
                </div>
            </div>
        </div>

        <div class="text-center text-[11px] text-pink-400/80 mt-5 font-medium">
            Pertemuan 4: Relational DB &bull; Toko Buket Mini Project 3
        </div>
    </div>

    <script>
        function fillLogin(user, pass) {
            document.querySelector('input[name="username"]').value = user;
            document.querySelector('input[name="password"]').value = pass;
        }
    </script>
</body>
</html>
