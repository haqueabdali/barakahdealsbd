<?php
/* Categories: add, rename, reorder, delete */
require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $do = $_POST['do'] ?? '';
    $msg = '';
    $err = '';
    bd_with_store('catalog', function ($cat) use ($do, &$msg, &$err) {
        $id = (string) ($_POST['id'] ?? '');
        $name = bd_clean($_POST['name'] ?? '', 60);
        $icon = bd_clean($_POST['icon'] ?? '', 8) ?: '🛍️';
        $idx = array_search($id, array_column($cat['categories'], 'id'), true);
        if ($do === 'add') {
            if ($name === '') { $err = 'Please enter a category name.'; return null; }
            $base = slugify($name); $newId = $base; $n = 2;
            while (in_array($newId, array_column($cat['categories'], 'id'), true)) $newId = $base . '-' . $n++;
            $cat['categories'][] = ['id' => $newId, 'name' => $name, 'icon' => $icon];
            $msg = "Category “{$name}” added.";
        } elseif ($idx === false) {
            $err = 'Category not found.'; return null;
        } elseif ($do === 'save') {
            if ($name === '') { $err = 'Category name cannot be empty.'; return null; }
            $cat['categories'][$idx]['name'] = $name;
            $cat['categories'][$idx]['icon'] = $icon;
            $msg = 'Category saved.';
        } elseif ($do === 'up' || $do === 'down') {
            $to = $do === 'up' ? $idx - 1 : $idx + 1;
            if (isset($cat['categories'][$to])) {
                $tmp = $cat['categories'][$to];
                $cat['categories'][$to] = $cat['categories'][$idx];
                $cat['categories'][$idx] = $tmp;
            }
        } elseif ($do === 'delete') {
            $used = count(array_filter($cat['products'], function ($p) use ($id) { return $p['category'] === $id; }));
            if ($used) { $err = "This category still has {$used} product(s). Move or delete them first."; return null; }
            array_splice($cat['categories'], $idx, 1);
            $msg = 'Category deleted.';
        }
        return $cat;
    });
    if ($err) flash($err, 'err'); elseif ($msg) flash($msg);
    redirect('categories.php');
}

$catalog = bd_catalog();
$counts = [];
foreach ($catalog['products'] as $p) $counts[$p['category']] = ($counts[$p['category']] ?? 0) + 1;

admin_head('Categories', 'categories');
?>
<h1>Categories</h1>
<p class="muted">Categories appear on the home page and in the shop filters, in this order. Empty categories show “Coming soon · Inbox to order”.</p>
<div class="card table-wrap">
  <table>
    <tr><th style="width:90px">Icon</th><th>Name</th><th>Products</th><th></th></tr>
    <?php foreach ($catalog['categories'] as $i => $c): ?>
      <tr>
        <td colspan="2">
          <form method="post" class="toolbar" style="margin:0;flex-wrap:nowrap">
            <?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= h($c['id']) ?>">
            <input name="icon" value="<?= h($c['icon']) ?>" style="width:64px;text-align:center;font-size:20px" aria-label="Icon">
            <input name="name" value="<?= h($c['name']) ?>" aria-label="Name">
            <button class="btn sm">Save</button>
          </form>
        </td>
        <td><a href="products.php?cat=<?= h(urlencode($c['id'])) ?>"><?= (int) ($counts[$c['id']] ?? 0) ?></a></td>
        <td style="white-space:nowrap">
          <?php foreach (['up' => '↑', 'down' => '↓'] as $dir => $label): ?>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="<?= $dir ?>"><input type="hidden" name="id" value="<?= h($c['id']) ?>"><button class="btn sm light" title="Move <?= $dir ?>"><?= $label ?></button></form>
          <?php endforeach; ?>
          <form method="post" class="inline" onsubmit="return confirm('Delete this category?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= h($c['id']) ?>"><button class="btn sm danger">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<div class="card">
  <h2>Add category</h2>
  <form method="post" class="toolbar" style="margin:0">
    <?= csrf_field() ?><input type="hidden" name="do" value="add">
    <input name="icon" placeholder="🛍️" style="width:70px;text-align:center;font-size:20px" aria-label="Icon">
    <input class="grow" name="name" placeholder="Category name, e.g. Medical Equipment" required>
    <button class="btn gold">＋ Add</button>
  </form>
  <p class="muted" style="margin-bottom:0"><small>Tip: on Windows press <b>Win + .</b> (on Mac <b>Ctrl + Cmd + Space</b>) to pick an emoji icon.</small></p>
</div>
<?php admin_foot();
