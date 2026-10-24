<?php

return [
    /*
    | Dedicated key for signature encryption (format "base64:..."), generate with:
    |   php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
    | When empty, APP_KEY is used (APP_KEY itself is never altered by this feature).
    */
    'key' => env('SIGNATURE_ENCRYPTION_KEY'),

    // Comma separated retired keys, still accepted for decryption during rotation.
    'previous_keys' => array_filter(array_map('trim', explode(',', (string) env('SIGNATURE_PREVIOUS_KEYS', '')))),

    'cipher' => env('SIGNATURE_CIPHER'), // null => falls back to app.cipher

    'max_payload_chars' => 400000,   // data URL length ceiling
    'max_image_bytes' => 250000,     // decoded PNG bytes
    'max_width' => 1600,
    'max_height' => 800,
    'min_width' => 60,
    'min_height' => 20,
    'min_ink_pixels' => 150,

    'consent_version' => 'v1',
    'archive_disk' => env('SIGNATURE_ARCHIVE_DISK', 'local'), // private disk
];
