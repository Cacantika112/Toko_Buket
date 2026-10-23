<?php
/**
 * Helper Output Encoding (Slide 20: XSS & Output Encoding)
 * Mencegah Cross-Site Scripting (XSS) dengan htmlspecialchars
 */

declare(strict_types=1);

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_rupiah')) {
    function format_rupiah(float|int|string $number): string {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $datetime): string {
        if (!$datetime) return '-';
        return date('d M Y H:i', strtotime($datetime));
    }
}
