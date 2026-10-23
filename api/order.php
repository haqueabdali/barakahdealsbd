<?php
/*
 * POST /api/order.php — receives an order from the checkout page.
 * Prices, delivery charge and coupon discount are recalculated here from the
 * admin-managed catalog and settings, so totals can't be changed by the browser.
 * Stock is reserved when the order is placed and returned if it's cancelled.
 */
require __DIR__ . '/lib.php';

$cfg = bd_config();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bd_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input', false, null, 0, 20000);
$in = json_decode((string) $raw, true);
if (!is_array($in)) {
    bd_json(['ok' => false, 'error' => 'Invalid request'], 400);
}

// Honeypot field: real customers never fill it in
if (!empty($in['website'])) {
    bd_json(['ok' => false, 'error' => 'Could not place the order'], 400);
}

/* ---------- Rate limit per IP ---------- */
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = bd_data_dir() . '/rate.json';
$fh = @fopen($rateFile, 'c+');
if ($fh) {
    flock($fh, LOCK_EX);
    $rates = json_decode((string) stream_get_contents($fh), true);
    if (!is_array($rates)) $rates = [];
    $now = time();
    foreach ($rates as $k => $times) {
        $rates[$k] = array_values(array_filter((array) $times, function ($t) use ($now) { return $t > $now - 3600; }));
        if (!$rates[$k]) unset($rates[$k]);
    }
    $key = hash('sha256', $ip);
    $limited = isset($rates[$key]) && count($rates[$key]) >= (int) $cfg['max_orders_per_hour'];
    if (!$limited) {
        $rates[$key][] = $now;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($rates));
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    if ($limited) {
        bd_json(['ok' => false, 'error' => 'Too many orders from your network. Please call or WhatsApp us to order.'], 429);
    }
}

/* ---------- Validate customer ---------- */
$c = isset($in['customer']) && is_array($in['customer']) ? $in['customer'] : [];
$customer = [
    'name' => bd_clean($c['name'] ?? '', 100),
    'phone' => bd_phone($c['phone'] ?? ''),
    'email' => bd_clean($c['email'] ?? '', 150),
    'district' => bd_clean($c['district'] ?? '', 50),
    'address' => bd_clean($c['address'] ?? '', 300),
];
$errors = [];
if (mb_strlen($customer['name']) < 3) $errors[] = 'Please enter your full name';
if (!$customer['phone']) $errors[] = 'Please enter a valid Bangladeshi mobile number';
if ($customer['email'] !== '' && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email';
if ($customer['district'] === '') $errors[] = 'Please select your district';
if (mb_strlen($customer['address']) < 8) $errors[] = 'Please enter your full delivery address';

$p = isset($in['payment']) && is_array($in['payment']) ? $in['payment'] : [];
$method = (string) ($p['method'] ?? '');
$payment = ['method' => $method, 'from' => '', 'trxId' => ''];
if (!isset(bd_payment_methods()[$method]) || !bd_payment_enabled($method)) {
    $errors[] = 'Please choose an available payment method';
} elseif ($method !== 'cod') {
    $payment['from'] = bd_phone($p['from'] ?? '');
    $payment['trxId'] = strtoupper(bd_clean($p['trxId'] ?? '', 20));
    if (!$payment['from']) $errors[] = 'Please enter the number you paid from';
    if (!preg_match('/^[A-Z0-9]{8,12}$/', $payment['trxId'])) $errors[] = 'Please enter a valid transaction ID';
}

/* ---------- Validate items & compute totals ---------- */
$catalog = bd_products();
$items = [];
$subtotal = 0;
$reqItems = isset($in['items']) && is_array($in['items']) ? array_slice($in['items'], 0, 50) : [];
foreach ($reqItems as $it) {
    $id = is_array($it) ? (string) ($it['id'] ?? '') : '';
    $qty = is_array($it) ? (int) ($it['qty'] ?? 0) : 0;
    if (!isset($catalog[$id]) || $qty < 1) {
        $errors[] = 'One of the products in your cart is no longer available. Please refresh the page.';
        continue;
    }
    $prod = $catalog[$id];
    $items[] = ['id' => $id, 'name' => $prod['name'], 'price' => (float) $prod['price'], 'qty' => $qty];
    $subtotal += $prod['price'] * $qty;
}
if (!$items && !$errors) $errors[] = 'Your cart is empty';

if ($errors) {
    bd_json(['ok' => false, 'error' => $errors[0], 'errors' => array_values(array_unique($errors))], 422);
}

$settings = bd_settings();
$code = strtoupper(bd_clean($in['coupon'] ?? '', 30));
$coupons = array_change_key_case((array) ($settings['coupons'] ?? []), CASE_UPPER);
$discount = 0;
if ($code !== '' && isset($coupons[$code])) {
    $cp = $coupons[$code];
    if ($subtotal >= (float) $cp['minOrder']) {
        $discount = $cp['type'] === 'percent' ? round($subtotal * $cp['value'] / 100) : min((float) $cp['value'], $subtotal);
    }
}
$delivery = $settings['delivery'] ?? ['insideDhaka' => 70, 'outsideDhaka' => 130, 'freeAbove' => 0];
$freeShip = $delivery['freeAbove'] > 0 && $subtotal >= $delivery['freeAbove'];
$shipping = $freeShip ? 0 : (float) ($customer['district'] === 'Dhaka' ? $delivery['insideDhaka'] : $delivery['outsideDhaka']);

/* ---------- Reserve stock (atomic, so two customers can't buy the last item) ---------- */
$stockError = '';
try {
    bd_with_store('catalog', function ($cat) use ($items, &$stockError) {
        $index = [];
        foreach ($cat['products'] as $k => $prod) $index[$prod['id']] = $k;
        foreach ($items as $i) {
            $prod = $cat['products'][$index[$i['id']]];
            if ((int) $prod['stock'] < $i['qty']) {
                $stockError = (int) $prod['stock'] <= 0 ? "{$prod['name']} is out of stock" : "Only {$prod['stock']} of {$prod['name']} available";
                return null; // don't save
            }
        }
        foreach ($items as $i) {
            $cat['products'][$index[$i['id']]]['stock'] -= $i['qty'];
        }
        return $cat;
    });
} catch (Throwable $e) {
    error_log('Barakah stock update failed: ' . $e->getMessage());
    bd_json(['ok' => false, 'error' => 'Sorry, we could not place your order right now. Please order via WhatsApp or call us.'], 500);
}
if ($stockError) {
    bd_json(['ok' => false, 'error' => $stockError], 409);
}

$order = [
    'id' => '',
    'date' => date('c'),
    'status' => 'Pending',
    'customer' => $customer,
    'notes' => bd_clean($in['notes'] ?? '', 500),
    'payment' => $payment,
    'items' => $items,
    'subtotal' => $subtotal,
    'discount' => $discount,
    'coupon' => $discount ? $code : null,
    'shipping' => $shipping,
    'total' => $subtotal - $discount + $shipping,
    'ip' => $ip,
    'history' => [['status' => 'Pending', 'date' => date('c')]],
];

/* ---------- Save ---------- */
try {
    bd_with_orders(function ($orders) use (&$order) {
        $ids = array_column($orders, 'id');
        do {
            $id = 'BD' . strtoupper(substr(base_convert((string) random_int(60466176, 2176782335), 10, 36), 0, 6));
        } while (in_array($id, $ids, true));
        $order['id'] = $id;
        array_unshift($orders, $order);
        return $orders;
    });
} catch (Throwable $e) {
    error_log('Barakah order save failed: ' . $e->getMessage());
    bd_adjust_stock($items, +1); // give the reserved stock back
    bd_json(['ok' => false, 'error' => 'Sorry, we could not save your order. Please order via WhatsApp or call us.'], 500);
}

/* ---------- Notify ---------- */
$text = bd_order_text($order);
bd_mail(bd_notify_email(), "New order {$order['id']} — " . bd_money($order['total']), "New order received on Barakah Deals BD\n\n" . $text, $customer['email']);
if ($cfg['email_customer'] && $customer['email']) {
    bd_mail(
        $customer['email'],
        "Your order {$order['id']} — Barakah Deals BD",
        "Assalamu Alaikum {$customer['name']},\n\nJazakAllah Khair for your order! Our team will call you shortly to confirm it.\n\n" . $text . "\n\nTrack your order any time on our website using your order ID and phone number.\n\nBarakah Deals BD",
        bd_notify_email()
    );
}

// Public copy of the order for the browser (no IP / history)
$public = $order;
unset($public['ip'], $public['history']);
bd_json(['ok' => true, 'order' => $public]);
