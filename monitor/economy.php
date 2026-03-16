<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/helpers.php';

$admin_section = 'economy';
$admin_page_title = 'اقتصاد / Economy';

$stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
$stmt->execute(['economy']);
$cat = $stmt->fetch();
$category_id = $cat ? (int)$cat['id'] : 0;

if (!$category_id) {
    die('Category "economy" not found.');
}

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_ar = trim($_POST['title_ar'] ?? '');
    $title_fr = trim($_POST['title_fr'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $excerpt_ar = trim($_POST['excerpt_ar'] ?? '');
    $excerpt_fr = trim($_POST['excerpt_fr'] ?? '');
    $body_ar = trim($_POST['body_ar'] ?? '');
    $body_fr = trim($_POST['body_fr'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft','published']) ? $_POST['status'] : 'draft';
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if (isset($_POST['delete'])) {
        $del_id = (int)$_POST['delete'];
        $pdo->prepare('DELETE FROM articles WHERE id = ? AND category_id = ?')->execute([$del_id, $category_id]);
        $message = 'Deleted.';
        header('Location: economy.php?msg=' . urlencode($message));
        exit;
    }

    $uploaded = handle_image_upload();
    $final_image = $uploaded ?: $image_url;
    if ($edit_id && !$final_image) {
        $row = $pdo->prepare('SELECT image_url FROM articles WHERE id = ? AND category_id = ?');
        $row->execute([$edit_id, $category_id]);
        $r = $row->fetch();
        if ($r && !empty(trim($r['image_url'] ?? ''))) $final_image = trim($r['image_url']);
    }

    if ($title_ar && $title_fr && ($body_ar || $body_fr)) {
        $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
        $slug = 'economy-' . ($edit_id ? $edit_id : time());
        if ($edit_id) {
            $pdo->prepare('UPDATE articles SET title_ar=?, title_fr=?, image_url=?, excerpt_ar=?, excerpt_fr=?, body_ar=?, body_fr=?, status=?, sort_order=?, published_at=? WHERE id=? AND category_id=?')
                ->execute([$title_ar, $title_fr, $final_image ?: null, $excerpt_ar ?: null, $excerpt_fr ?: null, $body_ar ?: null, $body_fr ?: null, $status, $sort_order, $published_at ?: null, $edit_id, $category_id]);
            $message = 'Updated.';
        } else {
            $pdo->prepare('INSERT INTO articles (category_id, title_ar, title_fr, slug, excerpt_ar, excerpt_fr, body_ar, body_fr, image_url, status, sort_order, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$category_id, $title_ar, $title_fr, $slug, $excerpt_ar ?: null, $excerpt_fr ?: null, $body_ar ?: null, $body_fr ?: null, $final_image ?: null, $status, $sort_order, $published_at]);
            $message = 'Added.';
        }
        header('Location: economy.php?msg=' . urlencode($message));
        exit;
    } elseif ($title_ar || $title_fr) {
        $message = 'Title (AR & FR) and at least one body (AR or FR) are required.';
    }
}

$message = $message ?: ($_GET['msg'] ?? '');
$items = $pdo->prepare('SELECT id, title_ar, title_fr, image_url, status, sort_order, created_at FROM articles WHERE category_id = ? ORDER BY sort_order ASC, published_at DESC, id DESC');
$items->execute([$category_id]);
$items = $items->fetchAll();

$editing = null;
if ($edit_id) {
    foreach ($items as $i) { if ((int)$i['id'] === $edit_id) { $editing = $i; break; } }
    if (!$editing) {
        $st = $pdo->prepare('SELECT * FROM articles WHERE id = ? AND category_id = ?');
        $st->execute([$edit_id, $category_id]);
        $editing = $st->fetch();
    }
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="mb-4">
  <a href="economy.php" class="btn btn-outline-secondary btn-sm">List</a>
  <?php if (!$editing): ?><a href="economy.php?add=1" class="btn btn-primary btn-sm">Add new</a><?php endif; ?>
</div>

<?php if ($editing || isset($_GET['add'])): ?>
<form method="post" enctype="multipart/form-data" class="card mb-4">
  <div class="card-body">
    <h6><?php echo $editing ? 'Edit' : 'Add new'; ?></h6>
    <div class="row g-2 mb-2">
      <div class="col-md-6">
        <label class="form-label">Title (AR)</label>
        <input type="text" name="title_ar" class="form-control" required value="<?php echo htmlspecialchars($editing['title_ar'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Title (FR)</label>
        <input type="text" name="title_fr" class="form-control" required value="<?php echo htmlspecialchars($editing['title_fr'] ?? ''); ?>">
      </div>
    </div>
    <div class="mb-2">
      <label class="form-label">Thumbnail</label>
      <input type="url" name="image_url" class="form-control mb-1" placeholder="https://... (optional)" value="<?php echo htmlspecialchars($editing['image_url'] ?? ''); ?>">
      <label class="form-label small text-muted">Or upload image (jpg, png, webp, gif, max 5 MB)</label>
      <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
      <?php if (!empty($editing['image_url'])): ?>
      <small class="text-muted">Current: <?php echo htmlspecialchars($editing['image_url']); ?></small>
      <?php endif; ?>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-6">
        <label class="form-label">Excerpt (AR) – first paragraph shown on list</label>
        <textarea name="excerpt_ar" class="form-control" rows="2" placeholder="Optional summary"><?php echo htmlspecialchars($editing['excerpt_ar'] ?? ''); ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Excerpt (FR)</label>
        <textarea name="excerpt_fr" class="form-control" rows="2"><?php echo htmlspecialchars($editing['excerpt_fr'] ?? ''); ?></textarea>
      </div>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-6">
        <label class="form-label">Body (AR) – full post</label>
        <textarea name="body_ar" class="form-control" rows="8" required><?php echo htmlspecialchars($editing['body_ar'] ?? ''); ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Body (FR)</label>
        <textarea name="body_fr" class="form-control" rows="8" required><?php echo htmlspecialchars($editing['body_fr'] ?? ''); ?></textarea>
      </div>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="draft" <?php echo ($editing['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
          <option value="published" <?php echo ($editing['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Sort order</label>
        <input type="number" name="sort_order" class="form-control" value="<?php echo (int)($editing['sort_order'] ?? 0); ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update' : 'Add'; ?></button>
    <?php if ($editing): ?><a href="economy.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
  </div>
</form>
<?php endif; ?>

<table class="table table-sm">
  <thead><tr><th>Title</th><th>Thumbnail</th><th>Status</th><th>Order</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $i): ?>
    <tr>
      <td><?php echo htmlspecialchars($i['title_ar'] ?: $i['title_fr']); ?></td>
      <td><?php echo !empty($i['image_url']) ? 'Yes' : '—'; ?></td>
      <td><?php echo htmlspecialchars($i['status']); ?></td>
      <td><?php echo (int)$i['sort_order']; ?></td>
      <td>
        <a href="economy.php?edit=<?php echo (int)$i['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
        <form method="post" class="d-inline" onsubmit="return confirm('Delete?');">
          <input type="hidden" name="delete" value="<?php echo (int)$i['id']; ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php if (empty($items)): ?><p class="text-muted">No posts yet. Add one above.</p><?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
