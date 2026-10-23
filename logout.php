<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/validation.php';

logout();
session_start();
$_SESSION['flash_success'] = 'Anda telah berhasil keluar dari sistem.';
header('Location: login.php');
exit;
