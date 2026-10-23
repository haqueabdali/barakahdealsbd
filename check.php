<?php
/*
 * Barakah Deals BD — hosting check.
 * Open https://yourdomain.com/check.php after uploading, take a screenshot, then delete this file.
 */
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$root = __DIR__;
$rows = [];
function row(&$rows, $ok, $label, $detail = '') { $rows[] = [$ok, $label, $detail]; }

row($rows, version_compare(PHP_VERSION, '7.4', '>='), 'PHP version 7.4 or newer', 'Running ' . PHP_VERSION);

$css = @file_get_contents("$root/css/style.css");
row($rows, $css !== false && strpos($css, 'brand theme') !== false, 'css/style.css uploaded and complete', $css === false ? 'MISSING' : number_format(strlen($css)) . ' bytes (expected about 28,000)');
foreach (['index.html', 'js/app.js', 'js/config.js', 'js/products.js', 'images/logo.png', 'images/hero-products.webp', 'api/store.php', 'api/lib.php', 'admin/index.php', '.htaccess'] as $f) {
    row($rows, is_file("$root/$f") && filesize("$root/$f") > 0, "$f present", is_file("$root/$f") ? number_format(filesize("$root/$f")) . ' bytes' : 'MISSING');
}
$perm = is_file("$root/css/style.css") ? substr(sprintf('%o', fileperms("$root/css/style.css")), -3) : '-';
row($rows, $perm === '-' || (int) $perm[2] >= 4, 'css/style.css readable by visitors', "permission $perm (should be 644)");

// Old website files that can take over the home page
$old = array_values(array_filter(['index.php', 'wp-config.php', 'default.html', 'index.htm'], function ($f) use ($root) { return is_file("$root/$f"); }));
row($rows, !$old, 'No old website files in this folder', $old ? 'Found: ' . implode(', ', $old) . ' — remove them (back up first)' : 'OK');

// Site extracted into a subfolder by mistake?
$nested = glob("$root/*/index.html") ?: [];
$nested = array_values(array_filter($nested, function ($f) { return is_file(dirname($f) . '/css/style.css'); }));
row($rows, !$nested, 'Site not duplicated in a subfolder', $nested ? 'Another copy found in: ' . basename(dirname($nested[0])) . '/' : 'OK');

foreach (['api/data', 'images/uploads'] as $d) {
    $p = "$root/$d";
    $ok = is_dir($p) && is_writable($p);
    row($rows, $ok, "$d writable", is_dir($p) ? ($ok ? 'OK' : 'Not writable — set permission 755') : 'Folder missing');
}
row($rows, function_exists('imagecreatetruecolor'), 'GD image library (photo resizing)', function_exists('imagecreatetruecolor') ? 'OK' : 'Missing — photos are stored without resizing');
row($rows, function_exists('mb_strlen'), 'mbstring extension', function_exists('mb_strlen') ? 'OK' : 'Missing — fallback is used');
row($rows, function_exists('mail'), 'PHP mail() available (order emails)', function_exists('mail') ? 'OK' : 'Disabled by host');
$server = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Hosting check — Barakah Deals BD</title>
<style>
body{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#faf7f0;color:#1d2b25;margin:0;padding:20px}
.box{max-width:820px;margin:0 auto;background:#fff;border:1px solid #e7e1d3;border-radius:12px;padding:20px;border-top:4px solid #c9a24a}
h1{color:#0d3b2e;margin:0 0 4px;font-size:22px}table{width:100%;border-collapse:collapse;margin:14px 0;font-size:14px}
td{padding:8px 10px;border-bottom:1px solid #eee;vertical-align:top}td:first-child{width:28px;font-size:18px}
.bad td{background:#fdf0ef}.muted{color:#6b766f;font-size:13px}code{background:#f1ece0;padding:1px 5px;border-radius:4px}
</style></head><body><div class="box">
<h1>Barakah Deals BD — hosting check</h1>
<p class="muted">Server: <?= htmlspecialchars($server) ?> · Take a screenshot of this whole page and send it, then <b>delete check.php</b>.</p>
<h3>Server</h3>
<table>
<?php foreach ($rows as $r): ?>
<tr class="<?= $r[0] ? '' : 'bad' ?>"><td><?= $r[0] ? '✅' : '❌' ?></td><td><b><?= htmlspecialchars($r[1]) ?></b><br><span class="muted"><?= htmlspecialchars($r[2]) ?></span></td></tr>
<?php endforeach; ?>
</table>
<h3>Browser</h3>
<table id="web"><tr><td>⏳</td><td>Testing how files are delivered to visitors…</td></tr></table>
</div>
<script>
(async function () {
  var out = [];
  function add(ok, label, detail) { out.push('<tr class="' + (ok ? '' : 'bad') + '"><td>' + (ok ? '✅' : '❌') + '</td><td><b>' + label + '</b><br><span class="muted">' + detail + '</span></td></tr>'); }
  async function get(url) {
    try { var r = await fetch(url + (url.indexOf('?') < 0 ? '?' : '&') + 't=' + Date.now(), { cache: 'no-store' }); return { status: r.status, type: r.headers.get('content-type') || '', text: await r.text(), cache: r.headers.get('x-litespeed-cache') || r.headers.get('cf-cache-status') || '' }; }
    catch (e) { return { status: 0, type: '', text: String(e), cache: '' }; }
  }
  var c = await get('css/style.css');
  add(c.status === 200 && /text\/css/.test(c.type) && c.text.indexOf('brand theme') > -1, 'Stylesheet delivered as CSS',
      'HTTP ' + c.status + ' · ' + (c.type || 'no type') + ' · ' + c.text.length + ' chars' + (c.cache ? ' · cache: ' + c.cache : '') + (c.text.indexOf('brand theme') < 0 ? ' · starts with: <code>' + c.text.slice(0, 80).replace(/[<&]/g, '') + '</code>' : ''));
  var j = await get('js/app.js');
  add(j.status === 200 && /javascript/.test(j.type), 'Shop script delivered', 'HTTP ' + j.status + ' · ' + (j.type || 'no type'));
  var s = await get('api/store.php');
  add(s.status === 200 && s.text.indexOf('window.STORE') > -1, 'Live store data (PHP) works', 'HTTP ' + s.status + ' · ' + (s.text.indexOf('window.STORE') > -1 ? 'OK' : 'starts with: <code>' + s.text.slice(0, 120).replace(/[<&]/g, '') + '</code>'));
  var d = await get('api/data/settings.json');
  add(d.status === 403 || d.status === 404, 'Private order data is protected', 'HTTP ' + d.status + (d.status === 200 ? ' — .htaccess is not active, data is public!' : ''));
  var i = await get('images/logo.png');
  add(i.status === 200 && /image/.test(i.type), 'Images delivered', 'HTTP ' + i.status + ' · ' + i.type);
  var fontsOk = await new Promise(function (res) {
    var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = 'https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap';
    l.onload = function () { res(true); }; l.onerror = function () { res(false); }; setTimeout(function () { res(false); }, 8000);
    document.head.appendChild(l);
  });
  add(fontsOk, 'Google Fonts reachable', fontsOk ? 'OK' : 'Blocked or slow — headings use a fallback font');
  document.getElementById('web').innerHTML = out.join('');
})();
</script>
</body></html>
