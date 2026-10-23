<?php
/* Store settings: contact info, social links, payment methods, delivery charges, coupons */
require __DIR__ . '/_init.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $P = $_POST;
    $str = function ($k, $max = 200) use ($P) { return bd_clean($P[$k] ?? '', $max); };
    $url = function ($k) use ($P, &$errors) {
        $v = bd_clean($P[$k] ?? '', 300);
        if ($v !== '' && !preg_match('#^https?://#i', $v)) $v = 'https://' . $v;
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_URL)) { $errors[] = "“{$k}” is not a valid link."; return ''; }
        return $v;
    };

    $new = [
        'name' => $str('name', 80) ?: 'Barakah Deals BD',
        'tagline' => $str('tagline', 80),
        'taglineBn' => $str('taglineBn', 120),
        'handle' => $str('handle', 60),
        'phone' => $str('phone', 30),
        'whatsapp' => preg_replace('/\D+/', '', $P['whatsapp'] ?? ''),
        'messenger' => trim(preg_replace('#^(https?://)?(www\.)?(m\.me|facebook\.com|fb\.com)/#i', '', $str('messenger', 100)), '/'),
        'email' => $str('email', 120),
        'notifyEmail' => $str('notifyEmail', 120),
        'address' => $str('address', 200),
        'hours' => $str('hours', 100),
        'social' => ['facebook' => $url('facebook'), 'instagram' => $url('instagram'), 'youtube' => $url('youtube'), 'tiktok' => $url('tiktok')],
        'delivery' => [
            'insideDhaka' => max(0, (float) ($P['insideDhaka'] ?? 70)),
            'outsideDhaka' => max(0, (float) ($P['outsideDhaka'] ?? 130)),
            'freeAbove' => max(0, (float) ($P['freeAbove'] ?? 0)),
        ],
        'payments' => ['cod' => ['enabled' => !empty($P['pay_cod'])]],
        'flashSaleEndHour' => min(23, max(0, (int) ($P['flashSaleEndHour'] ?? 23))),
        'coupons' => [],
    ];
    if ($new['email'] !== '' && !filter_var($new['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Shop email is not valid.';
    if ($new['notifyEmail'] !== '' && !filter_var($new['notifyEmail'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Order notification email is not valid.';
    if ($new['whatsapp'] !== '' && strlen($new['whatsapp']) === 11 && $new['whatsapp'][0] === '0') $new['whatsapp'] = '88' . $new['whatsapp'];

    foreach (['bkash', 'nagad', 'rocket'] as $m) {
        $enabled = !empty($P["pay_{$m}"]);
        $number = $str("{$m}_number", 30);
        if ($enabled && !bd_phone($number)) $errors[] = bd_payment_name($m) . ': please enter a valid number to receive payments.';
        $new['payments'][$m] = ['enabled' => $enabled, 'number' => $number, 'type' => ($P["{$m}_type"] ?? '') === 'Merchant' ? 'Merchant' : 'Personal'];
    }
    $anyPayment = array_filter($new['payments'], function ($p) { return !empty($p['enabled']); });
    if (!$anyPayment) $errors[] = 'Please keep at least one payment method enabled.';

    foreach ((array) ($P['coupon_code'] ?? []) as $i => $code) {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', (string) $code));
        if ($code === '') continue;
        $new['coupons'][$code] = [
            'type' => ($P['coupon_type'][$i] ?? '') === 'flat' ? 'flat' : 'percent',
            'value' => max(0, (float) ($P['coupon_value'][$i] ?? 0)),
            'minOrder' => max(0, (float) ($P['coupon_min'][$i] ?? 0)),
        ];
    }

    if (!$errors) {
        bd_with_store('settings', function ($old) use ($new) { return array_merge($old, $new); });
        flash('Settings saved. The shop is updated.');
        redirect('settings.php');
    }
    $s = array_merge(bd_settings(), $new);
} else {
    $s = bd_settings();
}
$pay = $s['payments'] ?? [];

admin_head('Settings', 'settings');
?>
<h1>Settings &amp; Payments</h1>
<?php foreach ($errors as $e): ?><p class="alert"><?= h($e) ?></p><?php endforeach; ?>
<form method="post">
  <?= csrf_field() ?>

  <div class="card">
    <h2>📇 Store &amp; contact information</h2>
    <div class="grid2">
      <label>Shop name <input name="name" value="<?= h($s['name']) ?>"></label>
      <label>Tagline <input name="tagline" value="<?= h($s['tagline'] ?? '') ?>"></label>
      <label>Bangla tagline <input name="taglineBn" value="<?= h($s['taglineBn'] ?? '') ?>"></label>
      <label>Social handle <input name="handle" value="<?= h($s['handle'] ?? '') ?>" placeholder="@barakahdealsbd"></label>
      <label>Phone (shown on site) <input name="phone" value="<?= h($s['phone']) ?>" placeholder="+880 1328-558888"></label>
      <label>WhatsApp number <input name="whatsapp" value="<?= h($s['whatsapp']) ?>" placeholder="8801328558888"><span class="hint">Country code + number, digits only.</span></label>
      <label>Shop email (shown on site) <input type="email" name="email" value="<?= h($s['email']) ?>"></label>
      <label>Send new-order emails to <input type="email" name="notifyEmail" value="<?= h($s['notifyEmail'] ?? '') ?>" placeholder="<?= h(bd_config()['shop_email']) ?>"><span class="hint">Private — not shown on the site.</span></label>
      <label>Address <input name="address" value="<?= h($s['address']) ?>"></label>
      <label>Opening hours <input name="hours" value="<?= h($s['hours']) ?>"></label>
    </div>
  </div>

  <div class="card">
    <h2>📱 Facebook, Instagram &amp; social links</h2>
    <div class="grid2">
      <label>Facebook page link <input name="facebook" value="<?= h($s['social']['facebook'] ?? '') ?>" placeholder="https://www.facebook.com/barakahdealsbd"></label>
      <label>Messenger (Facebook page username) <input name="messenger" value="<?= h($s['messenger'] ?? '') ?>" placeholder="barakahdealsbd"><span class="hint">Used for the “Inbox to order” buttons (m.me link).</span></label>
      <label>Instagram link <input name="instagram" value="<?= h($s['social']['instagram'] ?? '') ?>" placeholder="https://www.instagram.com/barakahdealsbd"></label>
      <label>YouTube link <input name="youtube" value="<?= h($s['social']['youtube'] ?? '') ?>" placeholder="Optional"></label>
      <label>TikTok link <input name="tiktok" value="<?= h($s['social']['tiktok'] ?? '') ?>" placeholder="Optional"></label>
    </div>
    <p class="muted" style="margin:0"><small>Leave a link empty to hide that icon.</small></p>
  </div>

  <div class="card" id="payments">
    <h2>💳 Payment methods</h2>
    <p class="muted">Customers choose from the enabled methods at checkout. For mobile banking they enter the sender number and Transaction ID, which you can check in your app before confirming the order.</p>
    <div class="grid2">
      <div class="pay-card">
        <h3>💵 Cash on Delivery <label class="check" style="margin:0"><input type="checkbox" name="pay_cod" value="1" <?= !empty($pay['cod']['enabled']) ? 'checked' : '' ?>> Enabled</label></h3>
        <p class="muted" style="margin:0">Customer pays the delivery person in cash.</p>
      </div>
      <?php foreach (['bkash' => '🩷 bKash', 'nagad' => '🧡 Nagad', 'rocket' => '💜 Rocket'] as $m => $label): $pm = $pay[$m] ?? []; ?>
        <div class="pay-card">
          <h3><?= $label ?> <label class="check" style="margin:0"><input type="checkbox" name="pay_<?= $m ?>" value="1" <?= !empty($pm['enabled']) ? 'checked' : '' ?>> Enabled</label></h3>
          <div class="grid2" style="gap:10px">
            <label>Number <input name="<?= $m ?>_number" value="<?= h($pm['number'] ?? '') ?>" placeholder="01XXXXXXXXX"></label>
            <label>Account type
              <select name="<?= $m ?>_type">
                <option value="Personal" <?= ($pm['type'] ?? '') !== 'Merchant' ? 'selected' : '' ?>>Personal (Send Money)</option>
                <option value="Merchant" <?= ($pm['type'] ?? '') === 'Merchant' ? 'selected' : '' ?>>Merchant (Payment)</option>
              </select>
            </label>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h2>🚚 Delivery charges</h2>
    <div class="grid3">
      <label>Inside Dhaka (৳) <input type="number" min="0" name="insideDhaka" value="<?= h($s['delivery']['insideDhaka']) ?>"></label>
      <label>Outside Dhaka (৳) <input type="number" min="0" name="outsideDhaka" value="<?= h($s['delivery']['outsideDhaka']) ?>"></label>
      <label>Free delivery on orders above (৳) <input type="number" min="0" name="freeAbove" value="<?= h($s['delivery']['freeAbove']) ?>"><span class="hint">0 = no free delivery.</span></label>
    </div>
  </div>

  <div class="card">
    <h2>🏷️ Coupon codes</h2>
    <table id="coupons">
      <tr><th>Code</th><th>Discount type</th><th>Value</th><th>Minimum order (৳)</th></tr>
      <?php $rows = $s['coupons'] ?? []; $rows[''] = ['type' => 'percent', 'value' => '', 'minOrder' => ''];
      foreach ($rows as $code => $c): ?>
        <tr>
          <td><input name="coupon_code[]" value="<?= h($code) ?>" placeholder="<?= $code === '' ? 'NEWCODE' : '' ?>" style="text-transform:uppercase"></td>
          <td><select name="coupon_type[]"><option value="percent" <?= $c['type'] === 'percent' ? 'selected' : '' ?>>% off</option><option value="flat" <?= $c['type'] === 'flat' ? 'selected' : '' ?>>৳ off</option></select></td>
          <td><input type="number" min="0" name="coupon_value[]" value="<?= h($c['value']) ?>"></td>
          <td><input type="number" min="0" name="coupon_min[]" value="<?= h($c['minOrder']) ?>"></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <p class="muted" style="margin-bottom:0"><small>Fill the empty last row to add a coupon. Clear a code to remove it.</small></p>
  </div>

  <div class="card">
    <h2>🔥 Flash sale</h2>
    <label style="max-width:300px">Countdown ends daily at (hour 0–23) <input type="number" min="0" max="23" name="flashSaleEndHour" value="<?= (int) ($s['flashSaleEndHour'] ?? 23) ?>"></label>
  </div>

  <button class="btn gold" style="padding:14px 30px">✓ Save all settings</button>
</form>
<?php admin_foot();
