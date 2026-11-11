<?php
$html = file_get_contents(__DIR__ . '/../public/test_composer_prod.html');
preg_match('/<button[^>]*aria-label="Send comment"[^>]*>.*?<\/button>/s', $html, $matches);
echo $matches[0] ?? 'Not found';
echo "\n";

