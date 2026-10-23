<?php
/**
 * Helper Validasi Input & Aturan Bisnis (Slide 16, 21, 33)
 * Syntactic & Semantic Validation, Sorting Allowlist, CSRF Token
 */

declare(strict_types=1);

/**
 * Validasi Sorting dari Allowlist (Slide 16: Kolom sorting harus dipilih dari allowlist)
 */
function validate_sort(string $sortBy, array $allowedColumns, string $default = 'id'): string {
    return in_array($sortBy, $allowedColumns, true) ? $sortBy : $default;
}

/**
 * Validasi Arah Sorting (ASC / DESC)
 */
function validate_order(string $order): string {
    $order = strtoupper(trim($order));
    return in_array($order, ['ASC', 'DESC'], true) ? $order : 'ASC';
}

/**
 * Validasi Nilai Integer Positif (contoh: quantity, stock, id)
 */
function validate_positive_int(mixed $value, string $fieldLabel, int $min = 1): ?string {
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    if ($filtered === false || $filtered < $min) {
        return "{$fieldLabel} harus berupa angka bulat minimal {$min}.";
    }
    return null;
}

/**
 * Validasi Angka Desimal Non-Negatif (contoh: harga barang)
 */
function validate_non_negative_number(mixed $value, string $fieldLabel): ?string {
    if (!is_numeric($value) || (float)$value < 0) {
        return "{$fieldLabel} harus berupa angka dan tidak boleh bernilai negatif.";
    }
    return null;
}

/**
 * Validasi Panjang String
 */
function validate_string(mixed $value, string $fieldLabel, int $minLength = 1, int $maxLength = 255): ?string {
    $trimmed = trim((string)$value);
    $len = mb_strlen($trimmed);
    if ($len < $minLength) {
        return "{$fieldLabel} wajib diisi (minimal {$minLength} karakter).";
    }
    if ($len > $maxLength) {
        return "{$fieldLabel} tidak boleh melebihi {$maxLength} karakter.";
    }
    return null;
}

/**
 * Validasi Unik SKU (Slide 33: SKU harus unik)
 */
function validate_unique_sku(PDO $pdo, string $sku, ?int $excludeId = null): ?string {
    $sql = "SELECT id FROM products WHERE sku = :sku";
    $params = ['sku' => trim($sku)];
    if ($excludeId !== null) {
        $sql .= " AND id != :id";
        $params['id'] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) {
        return "SKU '{$sku}' sudah digunakan oleh produk lain. SKU harus unik.";
    }
    return null;
}

/**
 * Flash message helper untuk feedback UI
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION["flash_{$type}"] = $message;
}

function get_flash(string $type): ?string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION["flash_{$type}"])) {
        $msg = $_SESSION["flash_{$type}"];
        unset($_SESSION["flash_{$type}"]);
        return $msg;
    }
    return null;
}
