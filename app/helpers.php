<?php

if (!function_exists('sanitize_filename')) {
    function sanitize_filename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return trim($filename, '._-') ?: 'file';
    }
}
