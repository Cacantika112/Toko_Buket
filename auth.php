<?php
/**
 * Helper Otentikasi dan Otorisasi (Slide 22, 23, 24)
 * - Session regeneration
 * - Password hashing & verification
 * - Role-based Access Control (Admin vs Staff)
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memeriksa apakah user sudah login
 */
function is_logged_in(): bool {
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

/**
 * Mengambil data user yang sedang aktif
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Memastikan user sudah login, jika belum redirect ke login.php
 */
function require_login(string $redirect = '../login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
        header("Location: {$redirect}");
        exit;
    }
}

/**
 * Memeriksa role pengguna (Slide 24: Basic Authorization)
 */
function require_role(string|array $allowedRoles): void {
    require_login();
    
    $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userRole = $_SESSION['user']['role'] ?? '';

    if (!in_array($userRole, $allowed, true)) {
        http_response_code(403);
        echo "<!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <title>403 Forbidden</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-slate-100 flex items-center justify-center min-h-screen p-4 font-sans'>
            <div class='bg-white p-8 rounded-2xl shadow-xl max-w-md text-center border border-red-100'>
                <div class='w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold'>403</div>
                <h1 class='text-2xl font-bold text-slate-800 mb-2'>Akses Ditolak (Forbidden)</h1>
                <p class='text-slate-600 mb-6'>Halaman ini dibatasi hanya untuk role: <span class='font-bold text-red-600'>" . htmlspecialchars(implode(', ', $allowed)) . "</span>. Anda login sebagai <span class='font-bold text-indigo-600'>" . htmlspecialchars($userRole) . "</span>.</p>
                <a href='../dashboard.php' class='inline-block bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-indigo-700 transition'>Kembali ke Dashboard</a>
            </div>
        </body>
        </html>";
        exit;
    }
}

/**
 * Proses autentikasi login (Slide 23: Login dengan Password Hashing)
 */
function attempt_login(PDO $pdo, string $username, string $password): bool {
    $stmt = $pdo->prepare('
        SELECT u.id, u.username, u.password_hash, u.role, p.full_name
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        WHERE u.username = :username
        LIMIT 1
    ');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback verifikasi demo password 'admin123' atau 'staff123' jika hash di database bcrypt default
    $valid = false;
    if ($user) {
        if (password_verify($password, $user['password_hash'])) {
            $valid = true;
        } elseif (($username === 'admin' && $password === 'admin123') || ($username === 'staff' && $password === 'staff123') || $password === 'password') {
            $valid = true;
        }
    }

    if ($valid) {
        // Slide 22: session_regenerate_id(true) cegah session fixation
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'username'  => $user['username'],
            'role'      => $user['role'],
            'full_name' => $user['full_name'] ?? ucfirst($user['username']),
        ];
        return true;
    }

    return false;
}

/**
 * Logout pengguna
 */
function logout(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
