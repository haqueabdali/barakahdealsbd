<?php
/* Single order: details, status update (with stock handling), customer email, invoice and delete */
require __DIR__ . '/_init.php';

$id = (string) ($_GET['id'] ?? $_POST['id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $do = $_POST['do'] ?? 'update';

    if ($do === 'delete') {
        $removed = null;
        bd_with_orders(function ($orders) use ($id, &$removed) {
            foreach ($orders as $k => $o) {
                if ($o['id'] === $id) { $removed = $o; unset($orders[$k]); }
            }
            return array_values($orders);
        });
        // Stock still reserved by an open order goes back to the shop
        if ($removed && !in_array($removed['status'], ['Cancelled', 'Delivered'], true)) {
            bd_adjust_stock($removed['items'], +1);
        }
        flash($removed ? "Order {$id} deleted." : 'Order not found.', $removed ? 'ok' : 'err');
        redirect('orders.php');
    }

    $status = (string) ($_POST['status'] ?? '');
    $note = bd_clean($_POST['admin_note'] ?? '', 500);
    $old = null;
    $updated = null;
    if (in_array($status, bd_statuses(), true)) {
        bd_with_orders(function ($orders) use ($id, $status, $note, &$old, &$updated) {
            foreach ($orders as &$o) {
                if ($o['id'] === $id) {
                    $old = $o['status'];
                    if ($o['status'] !== $status) {
                        $o['status'] = $status;
                        $o['history'][] = ['status' => $status, 'date' => date('c')];
                    }
                    $o['admin_note'] = $note;
                    $updated = $o;
                }
            }
            return $orders;
        });
    }
    if ($updated) {
        // Cancelling returns stock; re-opening a cancelled order takes it again
        if ($old !== 'Cancelled' && $status === 'Cancelled') bd_adjust_stock($updated['items'], +1);
        if ($old === 'Cancelled' && $status !== 'Cancelled') bd_adjust_stock($updated['items'], -1);

        $msg = 'Order updated.';
        if (!empty($_POST['email_customer']) && $old !== $status) {
            $email = $updated['customer']['email'] ?? '';
            $texts = [
                'Confirmed' => 'Your order has been confirmed and is being prepared.',
                'Shipped' => 'Good news — your order has been shipped and is on its way!',
                'Delivered' => 'Your order has been delivered. JazakAllah Khair for shopping with us!',
                'Cancelled' => 'Your order has been cancelled. Please contact us if you have any questions.',
                'Pending' => 'Your order is pending confirmation.',
            ];
            $body = "Assalamu Alaikum {$updated['customer']['name']},\n\n{$texts[$status]}\n\n" . bd_order_text($updated) . "\n\nBarakah Deals BD";
            if ($email && bd_mail($email, "Order {$id}: {$status} — Barakah Deals BD", $body, bd_notify_email())) {
                $msg .= ' Customer emailed.';
            } elseif ($email) {
                $msg .= ' (Email could not be sent from this server.)';
            } else {
                $msg .= ' (Customer has no email — call or WhatsApp them instead.)';
            }
        }
        flash($msg);
    }
    redirect('order.php?id=' . urlencode($id));
}

$o = null;
foreach (bd_read_orders() as $x) if ($x['id'] === $id) { $o = $x; break; }
if (!$o) {
    admin_head('Order not found', 'orders');
    echo '<p class="alert">Order not found. <a href="orders.php">Back to orders</a></p>';
    admin_foot();
    exit;
}
$s = bd_settings();
$waText = rawurlencode("Assalamu Alaikum {$o['customer']['name']}, this is Barakah Deals BD about your order {$o['id']} (" . bd_money($o['total']) . ').');

/* ---------- Printable invoice ---------- */
if (isset($_GET['print'])) {
    admin_head('Invoice ' . $o['id'], 'orders');
    ?>
    <p class="no-print"><a href="order.php?id=<?= h(urlencode($o['id'])) ?>">← Back to order</a> &nbsp; <button class="btn gold" onclick="window.print()">🖨 Print</button></p>
    <div class="invoice">
      <div class="inv-head">
        <div style="display:flex;gap:12px;align-items:center">
          <img src="../images/logo.png" alt="" width="70" height="70">
          <div><b style="font-size:20px;color:var(--em)"><?= h($s['name']) ?></b><br><small class="muted"><?= h($s['phone']) ?> · <?= h($s['email']) ?><br><?= h($s['address']) ?></small></div>
        </div>
        <div style="text-align:right"><b style="font-size:22px;color:var(--em)">INVOICE</b><br><?= h($o['id']) ?><br><small class="muted"><?= h(date('d M Y', strtotime($o['date']))) ?></small></div>
      </div>
      <p><b>Bill to:</b> <?= h($o['customer']['name']) ?> · <?= h($o['customer']['phone']) ?><br><?= h($o['customer']['address']) ?>, <?= h($o['customer']['district']) ?></p>
      <table>
        <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
        <?php foreach ($o['items'] as $i): ?><tr><td><?= h($i['name']) ?></td><td><?= (int) $i['qty'] ?></td><td><?= bd_money($i['price']) ?></td><td><?= bd_money($i['price'] * $i['qty']) ?></td></tr><?php endforeach; ?>
        <tr><td colspan="3">Subtotal</td><td><?= bd_money($o['subtotal']) ?></td></tr>
        <?php if ($o['discount']): ?><tr><td colspan="3">Discount (<?= h($o['coupon']) ?>)</td><td>-<?= bd_money($o['discount']) ?></td></tr><?php endif; ?>
        <tr><td colspan="3">Delivery</td><td><?= $o['shipping'] ? bd_money($o['shipping']) : 'FREE' ?></td></tr>
        <tr class="total"><td colspan="3"><?= $o['payment']['method'] === 'cod' ? 'Amount to collect (Cash on Delivery)' : 'Total' ?></td><td><?= bd_money($o['total']) ?></td></tr>
      </table>
      <p><b>Payment:</b> <?= h(bd_payment_name($o['payment']['method'])) ?><?php if ($o['payment']['trxId']): ?> — TrxID <?= h($o['payment']['trxId']) ?><?php endif; ?></p>
      <p class="muted" style="text-align:center;margin-top:30px">JazakAllah Khair for shopping with <?= h($s['name']) ?> · <?= h($s['taglineBn'] ?? '') ?></p>
    </div>
    <?php
    admin_foot();
    exit;
}

admin_head('Order ' . $o['id'], 'orders');
?>
<p><a href="orders.php">← All orders</a></p>
<div class="grid2" style="grid-template-columns:1.5fr 1fr">
  <section class="card">
    <div class="head"><h1><?= h($o['id']) ?> <?= status_pill($o['status']) ?></h1><a class="btn light sm" href="?id=<?= h(urlencode($o['id'])) ?>&print=1">🖨 Invoice</a></div>
    <p class="muted"><?= h(date('d M Y, h:i A', strtotime($o['date']))) ?></p>
    <table>
      <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
      <?php foreach ($o['items'] as $i): ?>
        <tr><td><?= h($i['name']) ?></td><td><?= (int) $i['qty'] ?></td><td><?= bd_money($i['price']) ?></td><td><?= bd_money($i['price'] * $i['qty']) ?></td></tr>
      <?php endforeach; ?>
      <tr><td colspan="3">Subtotal</td><td><?= bd_money($o['subtotal']) ?></td></tr>
      <?php if ($o['discount']): ?><tr><td colspan="3">Discount (<?= h($o['coupon']) ?>)</td><td>-<?= bd_money($o['discount']) ?></td></tr><?php endif; ?>
      <tr><td colspan="3">Delivery</td><td><?= $o['shipping'] ? bd_money($o['shipping']) : 'FREE' ?></td></tr>
      <tr class="total"><td colspan="3">Total</td><td><?= bd_money($o['total']) ?></td></tr>
    </table>
    <h3>Payment</h3>
    <p><?= h(bd_payment_name($o['payment']['method'])) ?>
      <?php if ($o['payment']['trxId']): ?><br>Transaction ID: <code><?= h($o['payment']['trxId']) ?></code> · Sent from <?= h($o['payment']['from']) ?>
      <br><small class="muted">Check this TrxID in your <?= h(bd_payment_name($o['payment']['method'])) ?> app before confirming.</small><?php endif; ?></p>
    <?php if ($o['notes']): ?><h3>Customer note</h3><p><?= h($o['notes']) ?></p><?php endif; ?>
  </section>
  <section>
    <div class="card">
      <h2>Customer</h2>
      <p><b><?= h($o['customer']['name']) ?></b><br>
        📞 <a href="tel:<?= h($o['customer']['phone']) ?>"><?= h($o['customer']['phone']) ?></a><br>
        <?php if ($o['customer']['email']): ?>✉ <?= h($o['customer']['email']) ?><br><?php endif; ?>
        📍 <?= h($o['customer']['address']) ?>, <?= h($o['customer']['district']) ?></p>
      <a class="btn sm" style="background:#25d366" target="_blank" rel="noopener" href="https://wa.me/88<?= h($o['customer']['phone']) ?>?text=<?= $waText ?>">💬 WhatsApp customer</a>
      <a class="btn sm light" href="tel:<?= h($o['customer']['phone']) ?>">📞 Call</a>
    </div>
    <div class="card">
      <h2>Update order</h2>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= h($o['id']) ?>">
        <label>Status
          <select name="status"><?php foreach (bd_statuses() as $st): ?><option <?= $st === $o['status'] ? 'selected' : '' ?>><?= h($st) ?></option><?php endforeach; ?></select>
          <span class="hint">Cancelling returns the items to stock.</span>
        </label>
        <label>Internal note
          <textarea name="admin_note" rows="3" placeholder="e.g. courier name and tracking number"><?= h($o['admin_note'] ?? '') ?></textarea>
        </label>
        <label class="check"><input type="checkbox" name="email_customer" value="1" <?= $o['customer']['email'] ? 'checked' : 'disabled' ?>> Email the customer about the status change</label>
        <button class="btn">Save</button>
      </form>
      <?php if (!empty($o['history'])): ?>
        <h3>History</h3>
        <ul class="hist"><?php foreach ($o['history'] as $hst): ?><li><?= h($hst['status']) ?> — <?= h(date('d M Y, h:i A', strtotime($hst['date']))) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
    <form method="post" class="card" onsubmit="return confirm('Delete order <?= h($o['id']) ?> permanently?')">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= h($o['id']) ?>"><input type="hidden" name="do" value="delete">
      <button class="btn danger">🗑 Delete order</button> <small class="muted">For test or spam orders.</small>
    </form>
  </section>
</div>
<?php admin_foot();
