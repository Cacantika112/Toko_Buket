<?php
declare(strict_types=1);
require_once __DIR__ . '/../helpers/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
