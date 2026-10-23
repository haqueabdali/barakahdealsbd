<?php
/*
 * GET /api/store.php — the live store settings and product catalog managed in the
 * Admin Panel, delivered as JavaScript. index.html loads it after js/config.js and
 * js/products.js, so it overrides those fallback defaults whenever PHP is available.
 */
require __DIR__ . '/lib.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$settings = bd_settings();
$catalog = bd_catalog();
$products = array_values(array_filter($catalog['products'], function ($p) {
    return !isset($p['active']) || $p['active'];
}));
$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG;

// Internal-only settings never go to the browser
unset($settings['notifyEmail']);

echo "/* Live store data from the admin panel */\n";
echo 'window.STORE = Object.assign(window.STORE || {}, ' . json_encode($settings, $flags) . ");\n";
echo 'window.CATEGORIES = ' . json_encode($catalog['categories'], $flags) . ";\n";
echo 'window.PRODUCTS = ' . json_encode($products, $flags) . ";\n";
echo "window.STORE_LIVE = true;\n";
