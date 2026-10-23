<?php
/* Admin dashboard — open https://yourdomain.com/admin/ */
require __DIR__ . '/_init.php';

$orders = bd_read_orders();
$today = date('Y-m-d');
$month = date('Y-m');
$stats = ['today' => 0, 'pending' => 0, 'month' => 0, 'all' => 0];
foreach ($orders as $o) {
    $d = date('Y-m-d', strtotime($o['date']));
    if ($d === $today) $stats['today']++;
    if ($o['status'] === 'Pending') $stats['pending']++;
    if ($o['status'] !== 'Cancelled') {
        $stats['all'] += $o['total'];
        if (substr($d, 0, 7) === $month) $stats['month'] += $o['total'];
    }
}
$products = bd_catalog()['products'];
$low = array_filter($products, function ($p) { return (int) $p['stock'] <= 5 && (!isset($p['active']) || $p['active']); });
usort($low, function ($a, $b) { return $a['stock'] - $b['stock']; });

admin_head('Dashboard', 'dash');
?>
<h1>Assalamu Alaikum 👋</h1>
<div class="stats">
  <div class="card"><small>Orders today</small><b><?= $stats['today'] ?></b></div>
  <div class="card"><small>Pending orders</small><b><?= $stats['pending'] ?></b></div>
  <div class="card"><small>Sales this month</small><b><?= bd_money($stats['month']) ?></b></div>
  <div class="card"><small>Total sales (excl. cancelled)</small><b><?= bd_money($stats['all']) ?></b></div>
</div>

<div class="grid2">
  <div class="card table-wrap">
    <div class="head" style="padding:14px 14px 0"><h2>Latest orders</h2><a class="btn sm light" href="orders.php">All orders →</a></div>
    <table>
      <tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr>
      <?php if (!$orders): ?><tr><td colspan="4" class="muted">No orders yet. They'll appear here as soon as customers check out.</td></tr><?php endif; ?>
      <?php foreach (array_slice($orders, 0, 8) as $o): ?>
        <tr class="row" onclick="location='order.php?id=<?= h(urlencode($o['id'])) ?>'">
          <td><b><?= h($o['id']) ?></b><br><small class="muted"><?= h(date('d M, h:i A', strtotime($o['date']))) ?></small></td>
          <td><?= h($o['customer']['name']) ?><br><small class="muted"><?= h($o['customer']['phone']) ?></small></td>
          <td><?= bd_money($o['total']) ?></td>
          <td><?= status_pill($o['status']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="card table-wrap">
    <div class="head" style="padding:14px 14px 0"><h2>Low stock (5 or less)</h2><a class="btn sm light" href="products.php">Products →</a></div>
    <table>
      <tr><th></th><th>Product</th><th>Stock</th></tr>
      <?php if (!$low): ?><tr><td colspan="3" class="muted">All products are well stocked.</td></tr><?php endif; ?>
      <?php foreach (array_slice($low, 0, 8) as $p): ?>
        <tr class="row" onclick="location='product.php?id=<?= h(urlencode($p['id'])) ?>'">
          <td><?php if (!empty($p['image'])): ?><img class="thumb" src="../<?= h($p['image']) ?>" alt=""><?php endif; ?></td>
          <td><?= h($p['name']) ?></td>
          <td><span class="pill <?= $p['stock'] <= 0 ? 's-out' : 's-low' ?>"><?= (int) $p['stock'] ?></span></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="card">
  <h2>Quick actions</h2>
  <div class="toolbar">
    <a class="btn gold" href="product.php">＋ Add product</a>
    <a class="btn light" href="orders.php?status=Pending">Pending orders</a>
    <a class="btn light" href="settings.php#payments">Payment methods</a>
    <a class="btn light" href="orders.php?action=csv">⬇ Export orders (CSV)</a>
  </div>
</div>
<?php admin_foot();
