<?php
/* Shared helpers for the store API and admin panel. Not meant to be opened directly. */

if (!function_exists('mb_strlen')) {
    // Fallback for hosts without the mbstring extension
    function mb_strlen($s)
    {
        return preg_match_all('/./us', (string) $s);
    }
}

if (!defined('BD_ROOT')) {
    define('BD_ROOT', dirname(__DIR__));
}

function bd_config()
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
        date_default_timezone_set($cfg['timezone'] ?? 'Asia/Dhaka');
    }
    return $cfg;
}

function bd_data_dir()
{
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function bd_json($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function bd_money($n)
{
    return '৳' . number_format((float) $n, 0, '.', ',');
}

/** Normalise a Bangladeshi mobile number to 01XXXXXXXXX, or return '' if invalid. */
function bd_phone($raw)
{
    $digits = preg_replace('/\D+/', '', (string) $raw);
    if (strpos($digits, '880') === 0) {
        $digits = substr($digits, 2);
    } elseif (strpos($digits, '88') === 0 && strlen($digits) === 13) {
        $digits = substr($digits, 2);
    }
    return preg_match('/^01[3-9]\d{8}$/', $digits) ? $digits : '';
}

function bd_clean($s, $max = 500)
{
    $s = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $s));
    return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
}

/* ---------- JSON storage with locking ---------- */

/**
 * Runs $fn with the decoded contents of api/data/$name.json under an exclusive lock.
 * If the file doesn't exist yet it starts from api/seed/$name.json (or $default).
 * If $fn returns a value other than null, it is saved back to the file.
 */
function bd_with_store($name, callable $fn, $default = [])
{
    $file = bd_data_dir() . "/{$name}.json";
    $fh = @fopen($file, 'c+');
    if (!$fh) {
        throw new RuntimeException("Cannot open {$name}.json. Check that the api/data folder is writable (permission 755).");
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $data = bd_seed($name, $default);
    }
    $result = $fn($data);
    if ($result !== null) {
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($fh);
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

function bd_read_store($name, $default = [])
{
    $file = bd_data_dir() . "/{$name}.json";
    if (is_file($file)) {
        $data = json_decode((string) file_get_contents($file), true);
        if (is_array($data)) {
            return $data;
        }
    }
    return bd_seed($name, $default);
}

function bd_seed($name, $default)
{
    $seed = __DIR__ . "/seed/{$name}.json";
    if (is_file($seed)) {
        $data = json_decode((string) file_get_contents($seed), true);
        if (is_array($data)) {
            return $data;
        }
    }
    return $default;
}

/* ---------- Settings & catalog ---------- */

function bd_settings()
{
    static $s = null;
    if ($s === null) {
        $s = bd_read_store('settings');
    }
    return $s;
}

function bd_catalog()
{
    static $c = null;
    if ($c === null) {
        $c = bd_read_store('catalog', ['categories' => [], 'products' => []]);
    }
    return $c;
}

/** [id => product] for products that are visible in the shop */
function bd_products()
{
    $out = [];
    foreach (bd_catalog()['products'] as $p) {
        if (!isset($p['active']) || $p['active']) {
            $out[$p['id']] = $p;
        }
    }
    return $out;
}

function bd_payment_methods()
{
    return ['cod' => 'Cash on Delivery', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket'];
}

function bd_payment_name($method)
{
    $names = bd_payment_methods();
    return $names[$method] ?? $method;
}

function bd_payment_enabled($method)
{
    $p = bd_settings()['payments'][$method] ?? null;
    return is_array($p) && !empty($p['enabled']);
}

/** Adds (+1) or removes (-1) order quantities to/from product stock. */
function bd_adjust_stock(array $items, $direction)
{
    bd_with_store('catalog', function ($catalog) use ($items, $direction) {
        foreach ($catalog['products'] as &$p) {
            foreach ($items as $i) {
                if ($p['id'] === $i['id']) {
                    $p['stock'] = max(0, (int) $p['stock'] + $direction * (int) $i['qty']);
                }
            }
        }
        return $catalog;
    });
}

/* ---------- Orders ---------- */

function bd_with_orders(callable $fn)
{
    return bd_with_store('orders', $fn, []);
}

function bd_read_orders()
{
    return bd_read_store('orders', []);
}

function bd_statuses()
{
    return ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
}

/* ---------- Email ---------- */

function bd_mail($to, $subject, $body, $replyTo = '')
{
    $cfg = bd_config();
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $from = $cfg['from_email'];
    $fromName = '=?UTF-8?B?' . base64_encode($cfg['from_name']) . '?=';
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        "From: {$fromName} <{$from}>",
    ];
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = "Reply-To: {$replyTo}";
    }
    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to, $encSubject, $body, implode("\r\n", $headers), '-f' . $from);
}

/** Where new-order emails go: Admin → Settings → notification email, else api/config.php */
function bd_notify_email()
{
    $s = bd_settings();
    return !empty($s['notifyEmail']) ? $s['notifyEmail'] : bd_config()['shop_email'];
}

function bd_order_text($o)
{
    $lines = [];
    $lines[] = "Order ID: {$o['id']}";
    $lines[] = 'Date: ' . date('d M Y, h:i A', strtotime($o['date']));
    $lines[] = '';
    foreach ($o['items'] as $i) {
        $lines[] = "• {$i['name']} × {$i['qty']} = " . bd_money($i['price'] * $i['qty']);
    }
    $lines[] = '';
    $lines[] = 'Subtotal: ' . bd_money($o['subtotal']);
    if (!empty($o['discount'])) {
        $lines[] = "Discount ({$o['coupon']}): -" . bd_money($o['discount']);
    }
    $lines[] = 'Delivery: ' . ($o['shipping'] ? bd_money($o['shipping']) : 'FREE');
    $lines[] = 'TOTAL: ' . bd_money($o['total']);
    $pay = 'Payment: ' . bd_payment_name($o['payment']['method']);
    if (!empty($o['payment']['trxId'])) {
        $pay .= " (TrxID: {$o['payment']['trxId']}, from {$o['payment']['from']})";
    }
    $lines[] = $pay;
    $lines[] = '';
    $lines[] = "Name: {$o['customer']['name']}";
    $lines[] = "Phone: {$o['customer']['phone']}";
    if (!empty($o['customer']['email'])) {
        $lines[] = "Email: {$o['customer']['email']}";
    }
    $lines[] = "Address: {$o['customer']['address']}, {$o['customer']['district']}";
    if (!empty($o['notes'])) {
        $lines[] = "Notes: {$o['notes']}";
    }
    return implode("\n", $lines);
}
