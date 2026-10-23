<?php
/* Change the admin password (stored hashed in api/data/admin.json) */
require __DIR__ . '/_init.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    if (!admin_password_ok($current)) {
        $error = 'Current password is wrong.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== ($_POST['confirm'] ?? '')) {
        $error = 'The new passwords do not match.';
    } else {
        bd_with_store('admin', function ($acc) use ($new) {
            $acc['hash'] = password_hash($new, PASSWORD_DEFAULT);
            $acc['changed'] = date('c');
            return $acc;
        });
        flash('Password changed. Use the new password next time you log in.');
        redirect('account.php');
    }
}

admin_head('Password', 'account');
?>
<h1>Change admin password</h1>
<?php if ($error): ?><p class="alert"><?= h($error) ?></p><?php endif; ?>
<?php if (admin_using_default_password()): ?><p class="alert">⚠ You are using the default password. Please set your own now.</p><?php endif; ?>
<form method="post" class="card" style="max-width:420px">
  <?= csrf_field() ?>
  <label>Current password <input type="password" name="current" required autocomplete="current-password"></label>
  <label>New password <input type="password" name="new" required minlength="8" autocomplete="new-password"><span class="hint">At least 8 characters.</span></label>
  <label>Confirm new password <input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
  <button class="btn gold">Change password</button>
</form>
<?php admin_foot();
