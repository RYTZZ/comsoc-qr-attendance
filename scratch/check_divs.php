<?php
$content = file_get_contents(__DIR__ . '/../resources/views/components/announcement-engagement-section.blade.php');
preg_match_all('/<div\b[^>]*>/', $content, $opens);
preg_match_all('/<\/div>/', $content, $closes);
echo "Opens: " . count($opens[0]) . ", Closes: " . count($closes[0]) . "\n";

