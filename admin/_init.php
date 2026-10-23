<?php
/* Admin panel bootstrap: session, login, CSRF, layout and helpers. Included by every admin page. */
require dirname(__DIR__) . '/api/lib.php';

$cfg = bd_config();
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_name('bd_admin');
session_start();
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function csrf()
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">'; }
function check_csrf()
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Session expired. Please go back, refresh the page and try again.');
    }
}

function flash($msg, $type = 'ok') { $_SESSION['flash'][] = [$type, $msg]; }
function redirect($url) { header('Location: ' . $url); exit; }

/* ---------- Admin password: Account page (hashed in api/data/admin.json) overrides api/config.php ---------- */
function admin_account() { return bd_read_store('admin', []); }
function admin_password_ok($pw)
{
    $acc = admin_account();
    if (!empty($acc['hash'])) return password_verify($pw, $acc['hash']);
    return hash_equals((string) bd_config()['admin_password'], (string) $pw);
}
function admin_using_default_password()
{
    $acc = admin_account();
    return empty($acc['hash']) && bd_config()['admin_password'] === 'ChangeMe@Barakah2026';
}

if (($_GET['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('./');
}

if (empty($_SESSION['admin'])) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
        if ($_SESSION['attempts'] > 10) {
            $error = 'Too many attempts. Close the browser and try again later.';
        } elseif (admin_password_ok($_POST['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['attempts'] = 0;
            redirect($_SERVER['REQUEST_URI']);
        } else {
            sleep(1);
            $error = 'Wrong password';
        }
    }
    admin_head('Admin login', '', false);
    ?>
    <div class="login">
      <img src="../images/logo.png" alt="" width="110" height="110">
      <h1>Admin Panel</h1>
      <p class="muted">Barakah Deals BD</p>
      <?php if ($error): ?><p class="alert"><?= h($error) ?></p><?php endif; ?>
      <form method="post">
        <input type="password" name="password" placeholder="Admin password" autofocus required>
        <button class="btn">Log in</button>
      </form>
    </div>
    <?php
    admin_foot();
    exit;
}

/* ---------- Image upload (resized and re-encoded with GD when available) ---------- */
function admin_save_upload($file, &$error)
{
    $error = '';
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($file['error'] !== UPLOAD_ERR_OK) { $error = 'Upload failed (error ' . (int) $file['error'] . '). The file may be too large.'; return ''; }
    if ($file['size'] > 5 * 1024 * 1024) { $error = 'Image is too large (max 5 MB).'; return ''; }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($types[$info[2]])) { $error = 'Please upload a JPG, PNG, WEBP or GIF image.'; return ''; }

    $dir = BD_ROOT . '/images/uploads';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(5));

    if (function_exists('imagecreatetruecolor')) {
        $loaders = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp', IMAGETYPE_GIF => 'imagecreatefromgif'];
        $load = $loaders[$info[2]];
        $src = function_exists($load) ? @$load($file['tmp_name']) : false;
        if ($src) {
            $w = imagesx($src); $hgt = imagesy($src);
            $scale = min(1, 900 / max($w, $hgt));
            $nw = max(1, (int) round($w * $scale)); $nh = max(1, (int) round($hgt * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            // White background, so transparent PNGs blend with the shop's image style
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $hgt);
            if (function_exists('imagewebp')) {
                $ok = imagewebp($dst, "$dir/$name.webp", 85); $ext = 'webp';
            } else {
                $ok = imagejpeg($dst, "$dir/$name.jpg", 86); $ext = 'jpg';
            }
            imagedestroy($src); imagedestroy($dst);
            if ($ok) return "images/uploads/$name.$ext";
        }
    }
    // No GD: store the original file (type already verified)
    $ext = $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name.$ext")) { $error = 'Could not save the image. Check that the images/uploads folder is writable.'; return ''; }
    return "images/uploads/$name.$ext";
}

function admin_delete_upload($path)
{
    if (is_string($path) && preg_match('#^images/uploads/[A-Za-z0-9._-]+$#', $path)) {
        @unlink(BD_ROOT . '/' . $path);
    }
}

function slugify($s)
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? substr($s, 0, 60) : 'item-' . bin2hex(random_bytes(3));
}

/* ---------- Layout ---------- */
function admin_head($title, $active = '', $nav = true)
{
    $pending = 0;
    if ($nav) {
        foreach (bd_read_orders() as $o) if ($o['status'] === 'Pending') $pending++;
    }
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> — Barakah Deals BD Admin</title>
<link rel="icon" href="../images/favicon.png">
<style><?php readfile(__DIR__ . '/admin.css'); /* inlined so it works even if the host mis-serves .css files */ ?></style>
</head><body class="<?= $nav ? 'has-nav' : '' ?>">
<?php if ($nav): ?>
<aside class="side" id="side">
  <a href="./" class="brand"><img src="../images/logo.png" alt="" width="44" height="44"><span>BARAKAH<small>ADMIN PANEL</small></span></a>
  <nav>
    <a href="./" class="<?= $active === 'dash' ? 'on' : '' ?>">📊 Dashboard</a>
    <a href="orders.php" class="<?= $active === 'orders' ? 'on' : '' ?>">🧾 Orders<?php if ($pending): ?><em><?= $pending ?></em><?php endif; ?></a>
    <a href="products.php" class="<?= $active === 'products' ? 'on' : '' ?>">🛍️ Products</a>
    <a href="categories.php" class="<?= $active === 'categories' ? 'on' : '' ?>">🗂️ Categories</a>
    <a href="settings.php" class="<?= $active === 'settings' ? 'on' : '' ?>">⚙️ Settings &amp; Payments</a>
    <a href="account.php" class="<?= $active === 'account' ? 'on' : '' ?>">🔒 Password</a>
    <a href="../" target="_blank">🌐 View shop ↗</a>
    <a href="?action=logout">↩ Log out</a>
  </nav>
</aside>
<header class="mtop"><button onclick="document.getElementById('side').classList.toggle('open')" aria-label="Menu">☰</button><b>Barakah Admin</b></header>
<main class="wrap">
<?php if (admin_using_default_password() && $active !== 'account'): ?>
  <p class="alert">⚠ You are still using the default admin password. <a href="account.php">Change it now</a>.</p>
<?php endif; ?>
<?php foreach ($_SESSION['flash'] ?? [] as $f): ?><p class="<?= $f[0] === 'ok' ? 'ok' : 'alert' ?>"><?= h($f[1]) ?></p><?php endforeach; unset($_SESSION['flash']); ?>
<?php endif;
}

function admin_foot()
{
    echo "</main></body></html>";
}

function status_pill($s)
{
    return '<span class="pill s-' . h(strtolower($s)) . '">' . h($s) . '</span>';
}
