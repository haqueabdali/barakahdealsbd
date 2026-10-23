<?php
/* GET /api/track.php?id=BDXXXXXX&phone=01XXXXXXXXX — order status for customers. */
require __DIR__ . '/lib.php';

$id = strtoupper(bd_clean($_GET['id'] ?? '', 20));
$phone = bd_phone($_GET['phone'] ?? '');
if ($id === '' || $phone === '') {
    bd_json(['ok' => false, 'error' => 'Please enter your order ID and phone number'], 400);
}

foreach (bd_read_orders() as $o) {
    if (strtoupper($o['id']) === $id && $o['customer']['phone'] === $phone) {
        bd_json(['ok' => true, 'order' => [
            'id' => $o['id'],
            'date' => $o['date'],
            'status' => $o['status'],
            'items' => $o['items'],
            'subtotal' => $o['subtotal'],
            'discount' => $o['discount'],
            'shipping' => $o['shipping'],
            'total' => $o['total'],
            'customer' => ['name' => $o['customer']['name'], 'district' => $o['customer']['district']],
        ]]);
    }
}
bd_json(['ok' => false, 'error' => 'Order not found'], 404);
