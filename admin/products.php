<?php
/* Product list: search, filter, quick stock/price edit, show/hide, delete */
require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $do = $_POST['do'] ?? '';
    $id = (string) ($_POST['id'] ?? '');
    $deletedImage = null;
    bd_with_store('catalog', function ($cat) use ($do, $id, &$deletedImage) {
        foreach ($cat['products'] as $k => &$p) {
            if ($p['id'] !== $id) continue;
            if ($do === 'toggle') {
                $p['active'] = isset($p['active']) ? !$p['active'] : false;
            } elseif ($do === 'quick') {
                $p['price'] = max(0, (float) ($_POST['price'] ?? $p['price']));
                $p['stock'] = max(0, (int) ($_POST['stock'] ?? $p['stock']));
            } elseif ($do === 'delete') {
                $deletedImage = $p['image'] ?? '';
                unset($cat['products'][$k]);
            }
        }
        unset($p);
        $cat['products'] = array_values($cat['products']);
        return $cat;
    }, ['categories' => [], 'products' => []]);
    if ($do === 'delete') {
        admin_delete_upload($deletedImage);
        flash('Product deleted.');
    } else {
        flash('Product updated.');
    }
    redirect('products.php?' . http_build_query(array_filter(['q' => $_POST['q'] ?? '', 'cat' => $_POST['cat'] ?? ''])));
}

$catalog = bd_catalog();
$cats = [];
foreach ($catalog['categories'] as $c) $cats[$c['id']] = $c['name'];
$q = trim($_GET['q'] ?? '');
$catFilter = $_GET['cat'] ?? '';
$list = array_filter($catalog['products'], function ($p) use ($q, $catFilter) {
    if ($catFilter !== '' && $p['category'] !== $catFilter) return false;
    if ($q !== '' && stripos($p['name'], $q) === false) return false;
    return true;
});

admin_head('Products', 'products');
?>
<div class="head"><h1>Products (<?= count($catalog['products']) ?>)</h1><a class="btn gold" href="product.php">＋ Add product</a></div>
<form class="toolbar" method="get">
  <input class="grow" type="search" name="q" value="<?= h($q) ?>" placeholder="Search products">
  <select name="cat" onchange="this.form.submit()" style="width:auto">
    <option value="">All categories</option>
    <?php foreach ($cats as $cid => $cname): ?><option value="<?= h($cid) ?>" <?= $cid === $catFilter ? 'selected' : '' ?>><?= h($cname) ?></option><?php endforeach; ?>
  </select>
  <button class="btn">Search</button>
</form>
<div class="card table-wrap">
  <table>
    <tr><th></th><th>Product</th><th>Category</th><th style="width:250px">Price (৳) · Stock</th><th>Status</th><th></th></tr>
    <?php if (!$list): ?><tr><td colspan="6" class="muted">No products found.</td></tr><?php endif; ?>
    <?php foreach ($list as $p): $active = !isset($p['active']) || $p['active']; ?>
      <tr>
        <td><?php if (!empty($p['image'])): ?><img class="thumb" src="../<?= h($p['image']) ?>" alt=""><?php else: ?><span style="font-size:30px"><?= h($p['icon'] ?? '🛍️') ?></span><?php endif; ?></td>
        <td><a href="product.php?id=<?= h(urlencode($p['id'])) ?>"><b><?= h($p['name']) ?></b></a>
          <?php if (!empty($p['tags'])): ?><br><small class="muted"><?= h(implode(', ', $p['tags'])) ?></small><?php endif; ?></td>
        <td><?= h($cats[$p['category']] ?? $p['category']) ?></td>
        <td>
          <form method="post" class="toolbar" style="margin:0;flex-wrap:nowrap">
            <?= csrf_field() ?><input type="hidden" name="do" value="quick"><input type="hidden" name="id" value="<?= h($p['id']) ?>">
            <input type="hidden" name="q" value="<?= h($q) ?>"><input type="hidden" name="cat" value="<?= h($catFilter) ?>">
            <input type="number" name="price" value="<?= h($p['price']) ?>" min="0" step="1" style="width:90px" aria-label="Price">
            <input type="number" name="stock" value="<?= (int) $p['stock'] ?>" min="0" style="width:70px" aria-label="Stock">
            <button class="btn sm">Save</button>
          </form>
        </td>
        <td>
          <?php if (!$active): ?><span class="pill s-hidden">Hidden</span>
          <?php elseif ($p['stock'] <= 0): ?><span class="pill s-out">Out of stock</span>
          <?php elseif ($p['stock'] <= 5): ?><span class="pill s-low">Low stock</span>
          <?php else: ?><span class="pill s-delivered">Live</span><?php endif; ?>
        </td>
        <td style="white-space:nowrap">
          <a class="btn sm light" href="product.php?id=<?= h(urlencode($p['id'])) ?>">Edit</a>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= h($p['id']) ?>">
            <button class="btn sm light" title="Show or hide in the shop"><?= $active ? 'Hide' : 'Show' ?></button></form>
          <form method="post" class="inline" onsubmit="return confirm('Delete this product?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= h($p['id']) ?>">
            <button class="btn sm danger">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php admin_foot();
