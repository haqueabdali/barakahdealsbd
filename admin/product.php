<?php
/* Add / edit a product, including photo upload */
require __DIR__ . '/_init.php';

$catalog = bd_catalog();
$id = (string) ($_GET['id'] ?? '');
$product = null;
foreach ($catalog['products'] as $p) if ($p['id'] === $id) { $product = $p; break; }
$isNew = $product === null;
if ($isNew) {
    $product = ['id' => '', 'name' => '', 'category' => $catalog['categories'][0]['id'] ?? '', 'price' => '', 'oldPrice' => '',
        'rating' => 5, 'reviews' => 0, 'stock' => 10, 'icon' => '🛍️', 'image' => '', 'tags' => [], 'description' => '', 'features' => [], 'active' => true];
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $in = [
        'name' => bd_clean($_POST['name'] ?? '', 150),
        'category' => (string) ($_POST['category'] ?? ''),
        'price' => (float) ($_POST['price'] ?? 0),
        'oldPrice' => (float) ($_POST['oldPrice'] ?? 0),
        'stock' => max(0, (int) ($_POST['stock'] ?? 0)),
        'rating' => min(5, max(0, (float) ($_POST['rating'] ?? 5))),
        'reviews' => max(0, (int) ($_POST['reviews'] ?? 0)),
        'icon' => bd_clean($_POST['icon'] ?? '🛍️', 8),
        'description' => bd_clean($_POST['description'] ?? '', 2000),
        'features' => array_values(array_filter(array_map(function ($l) { return bd_clean($l, 200); }, preg_split('/\r?\n/', (string) ($_POST['features'] ?? ''))))),
        'tags' => array_values(array_intersect(['flash', 'featured', 'new'], (array) ($_POST['tags'] ?? []))),
        'active' => !empty($_POST['active']),
    ];
    if (mb_strlen($in['name']) < 2) $errors[] = 'Please enter the product name.';
    if ($in['price'] <= 0) $errors[] = 'Please enter a selling price.';
    if (!in_array($in['category'], array_column($catalog['categories'], 'id'), true)) $errors[] = 'Please choose a category.';
    if ($in['oldPrice'] && $in['oldPrice'] <= $in['price']) $in['oldPrice'] = 0;

    $uploadError = '';
    $newImage = admin_save_upload($_FILES['image'] ?? [], $uploadError);
    if ($uploadError) $errors[] = $uploadError;

    if (!$errors) {
        $oldImage = $product['image'] ?? '';
        $image = $oldImage;
        if ($newImage) $image = $newImage;
        elseif (!empty($_POST['remove_image'])) $image = '';
        $in['image'] = $image;

        $savedId = $id;
        bd_with_store('catalog', function ($cat) use ($in, $isNew, $id, &$savedId) {
            if ($isNew) {
                $base = slugify($in['name']);
                $ids = array_column($cat['products'], 'id');
                $savedId = $base; $n = 2;
                while (in_array($savedId, $ids, true)) $savedId = $base . '-' . $n++;
                array_unshift($cat['products'], ['id' => $savedId] + $in);
            } else {
                foreach ($cat['products'] as &$p) {
                    if ($p['id'] === $id) $p = array_merge($p, $in);
                }
            }
            return $cat;
        });
        if ($image !== $oldImage) admin_delete_upload($oldImage);
        flash($isNew ? 'Product added. It is now live in the shop.' : 'Product saved.');
        redirect('product.php?id=' . urlencode($savedId));
    }
    if ($newImage) admin_delete_upload($newImage);
    $product = array_merge($product, $in);
}

admin_head($isNew ? 'Add product' : 'Edit product', 'products');
?>
<p><a href="products.php">← All products</a></p>
<h1><?= $isNew ? 'Add product' : 'Edit product' ?></h1>
<?php foreach ($errors as $e): ?><p class="alert"><?= h($e) ?></p><?php endforeach; ?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="grid2" style="grid-template-columns:1.6fr 1fr">
    <div class="card">
      <label>Product name <input name="name" value="<?= h($product['name']) ?>" required maxlength="150"></label>
      <div class="grid3">
        <label>Selling price (৳) <input type="number" name="price" min="0" step="1" value="<?= h($product['price']) ?>" required></label>
        <label>Regular price (৳) <input type="number" name="oldPrice" min="0" step="1" value="<?= h($product['oldPrice'] ?: '') ?>"><span class="hint">Shown crossed out. Leave empty for no discount.</span></label>
        <label>Stock <input type="number" name="stock" min="0" value="<?= (int) $product['stock'] ?>"><span class="hint">0 = sold out. Goes down automatically with orders.</span></label>
      </div>
      <label>Category
        <select name="category"><?php foreach ($catalog['categories'] as $c): ?><option value="<?= h($c['id']) ?>" <?= $c['id'] === $product['category'] ? 'selected' : '' ?>><?= h($c['icon'] . ' ' . $c['name']) ?></option><?php endforeach; ?></select>
      </label>
      <label>Description <textarea name="description" rows="4"><?= h($product['description']) ?></textarea></label>
      <label>Key features <textarea name="features" rows="4" placeholder="One feature per line"><?= h(implode("\n", $product['features'])) ?></textarea><span class="hint">One per line — shown as a ✓ list on the product page.</span></label>
      <div class="grid3">
        <label>Rating (0–5) <input type="number" name="rating" min="0" max="5" step="0.1" value="<?= h($product['rating']) ?>"></label>
        <label>Number of reviews <input type="number" name="reviews" min="0" value="<?= (int) $product['reviews'] ?>"></label>
        <label>Icon (if no photo) <input name="icon" value="<?= h($product['icon']) ?>" maxlength="8"></label>
      </div>
    </div>
    <div>
      <div class="card">
        <h2>Photo</h2>
        <?php if (!empty($product['image'])): ?>
          <img class="preview" id="preview" src="../<?= h($product['image']) ?>" alt="">
          <label class="check"><input type="checkbox" name="remove_image" value="1"> Remove photo</label>
        <?php else: ?>
          <img class="preview" id="preview" alt="" style="display:none">
        <?php endif; ?>
        <label>Upload new photo <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" onchange="if(this.files[0]){var p=document.getElementById('preview');p.src=window.URL.createObjectURL(this.files[0]);p.style.display='block'}">
          <span class="hint">JPG, PNG or WEBP up to 5 MB. A square photo on a white background looks best.</span></label>
      </div>
      <div class="card">
        <h2>Visibility</h2>
        <label class="check"><input type="checkbox" name="active" value="1" <?= !empty($product['active']) || !isset($product['active']) ? 'checked' : '' ?>> Show in shop</label>
        <label class="check"><input type="checkbox" name="tags[]" value="flash" <?= in_array('flash', $product['tags'], true) ? 'checked' : '' ?>> 🔥 Flash Deals</label>
        <label class="check"><input type="checkbox" name="tags[]" value="featured" <?= in_array('featured', $product['tags'], true) ? 'checked' : '' ?>> ⭐ Top Picks</label>
        <label class="check"><input type="checkbox" name="tags[]" value="new" <?= in_array('new', $product['tags'], true) ? 'checked' : '' ?>> 🆕 New Arrivals</label>
      </div>
      <button class="btn gold" style="width:100%;justify-content:center;padding:14px"><?= $isNew ? '＋ Add product' : '✓ Save product' ?></button>
      <?php if (!$isNew): ?><p style="text-align:center"><a target="_blank" href="../#/product/<?= h(rawurlencode($product['id'])) ?>">View in shop ↗</a></p><?php endif; ?>
    </div>
  </div>
</form>
<?php admin_foot();
