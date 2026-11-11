<?php
$html = file_get_contents(__DIR__ . '/../public/test_composer_prod.html');
$html = str_replace("newCommentText: ''", "newCommentText: 'Hey @Rytzz '", $html);
$script = '<script>window.addEventListener("DOMContentLoaded", function() { setTimeout(function() { window.scrollTo(0, document.body.scrollHeight); }, 300); });</script></body>';
$html = str_replace('</body>', $script, $html);
file_put_contents(__DIR__ . '/../public/test_mobile_scrolled.html', $html);
echo "Scrolled test html written\n";

