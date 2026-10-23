<?php
/* Orders list, search, status filter and CSV export */
require __DIR__ . '/_init.php';

$orders = bd_read_orders();

if (($_GET['action'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="barakah-orders-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Bangla/৳ correctly
    fputcsv($out, ['Order ID', 'Date', 'Status', 'Name', 'Phone', 'Email', 'District', 'Address', 'Items', 'Subtotal', 'Discount', 'Coupon', 'Delivery', 'Total', 'Payment', 'Sender', 'TrxID', 'Notes', 'Admin note']);
    foreach ($orders as $o) {
        $items = implode('; ', array_map(function ($i) { return "{$i['name']} x{$i['qty']}"; }, $o['items']));
        fputcsv($out, [
            $o['id'], date('Y-m-d H:i', strtotime($o['date'])), $o['status'],
            $o['customer']['name'], $o['customer']['phone'], $o['customer']['email'], $o['customer']['district'], $o['customer']['address'],
            $items, $o['subtotal'], $o['discount'], $o['coupon'], $o['shipping'], $o['total'],
            bd_payment_name($o['payment']['method']), $o['payment']['from'], $o['payment']['trxId'], $o['notes'], $o['admin_note'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

$filter = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$counts = array_fill_keys(bd_statuses(), 0);
foreach ($orders as $o) $counts[$o['status']] = ($counts[$o['status']] ?? 0) + 1;

$list = array_filter($orders, function ($o) use ($filter, $q) {
    if ($filter !== '' && $o['status'] !== $filter) return false;
    if ($q !== '') {
        $hay = strtolower($o['id'] . ' ' . $o['customer']['name'] . ' ' . $o['customer']['phone'] . ' ' . $o['customer']['district'] . ' ' . ($o['payment']['trxId'] ?? ''));
        if (strpos($hay, strtolower($q)) === false) return false;
    }
    return true;
});

admin_head('Orders', 'orders');
?>
<div class="head"><h1>Orders</h1><a class="btn light" href="?action=csv">⬇ Export CSV</a></div>
<form class="toolbar" method="get">
  <input class="grow" type="search" name="q" value="<?= h($q) ?>" placeholder="Search order ID, name, phone, district, TrxID">
  <select name="status" onchange="this.form.submit()" style="width:auto">
    <option value="">All statuses (<?= count($orders) ?>)</option>
    <?php foreach (bd_statuses() as $s): ?><option value="<?= h($s) ?>" <?= $s === $filter ? 'selected' : '' ?>><?= h($s) ?> (<?= $counts[$s] ?>)</option><?php endforeach; ?>
  </select>
  <button class="btn">Search</button>
</form>
<div class="card table-wrap">
  <table>
    <tr><th>Order</th><th>Date</th><th>Customer</th><th>District</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th></tr>
    <?php if (!$list): ?><tr><td colspan="8" class="muted">No orders found.</td></tr><?php endif; ?>
    <?php foreach ($list as $o): ?>
      <tr class="row" onclick="location='order.php?id=<?= h(urlencode($o['id'])) ?>'">
        <td><a href="order.php?id=<?= h(urlencode($o['id'])) ?>"><b><?= h($o['id']) ?></b></a></td>
        <td><?= h(date('d M, h:i A', strtotime($o['date']))) ?></td>
        <td><?= h($o['customer']['name']) ?><br><small class="muted"><?= h($o['customer']['phone']) ?></small></td>
        <td><?= h($o['customer']['district']) ?></td>
        <td><?= array_sum(array_column($o['items'], 'qty')) ?></td>
        <td><b><?= bd_money($o['total']) ?></b></td>
        <td><?= h(bd_payment_name($o['payment']['method'])) ?><?php if (!empty($o['payment']['trxId'])): ?><br><small class="muted"><?= h($o['payment']['trxId']) ?></small><?php endif; ?></td>
        <td><?= status_pill($o['status']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php admin_foot();
