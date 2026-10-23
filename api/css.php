<?php
/*
 * Fallback stylesheet delivery. The shop loads css/style.css normally; if the server
 * delivers it with the wrong type or blocks it, js/app.js loads it from here instead.
 */
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');
$file = dirname(__DIR__) . '/css/style.css';
if (is_file($file)) {
    readfile($file);
} else {
    http_response_code(404);
    echo '/* css/style.css is missing on the server — upload the css folder */';
}
