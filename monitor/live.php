<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/helpers.php';

$admin_section = 'live';
$admin_page_title = 'البثوث المباشرة / Live';

$stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
$stmt->execute(['live']);
$cat = $stmt->fetch();
$category_id = $cat ? (int)$cat['id'] : 0;

if (!$category_id) {
    die('Category "live" not found.');
}

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_ar = trim($_POST['title_ar'] ?? '');
    $title_fr = trim($_POST['title_fr'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $excerpt_ar = trim($_POST['excerpt_ar'] ?? '');
    $excerpt_fr = trim($_POST['excerpt_fr'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft','published']) ? $_POST['status'] : 'draft';
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if (isset($_POST['delete'])) {
        $del_id = (int)$_POST['delete'];
        $pdo->prepare('DELETE FROM articles WHERE id = ? AND category_id = ?')->execute([$del_id, $category_id]);
        $message = 'Deleted.';
        header('Location: live.php?msg=' . urlencode($message));
        exit;
    }

    $uploaded = handle_video_upload();
    $final_url = $uploaded ?: $video_url;
    if ($edit_id && !$final_url) {
        $row = $pdo->prepare('SELECT video_url FROM articles WHERE id = ? AND category_id = ?');
        $row->execute([$edit_id, $category_id]);
        $r = $row->fetch();
        if ($r) $final_url = trim($r['video_url'] ?? '');
    }

    if ($title_ar && $title_fr && $final_url) {
        $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
        if ($edit_id) {
            $pdo->prepare('UPDATE articles SET title_ar=?, title_fr=?, video_url=?, excerpt_ar=?, excerpt_fr=?, status=?, sort_order=?, published_at=? WHERE id=? AND category_id=?')
                ->execute([$title_ar, $title_fr, $final_url, $excerpt_ar ?: null, $excerpt_fr ?: null, $status, $sort_order, $published_at ?: null, $edit_id, $category_id]);
            $message = 'Updated.';
        } else {
            $slug = 'live-' . time();
            $pdo->prepare('INSERT INTO articles (category_id, title_ar, title_fr, slug, excerpt_ar, excerpt_fr, video_url, status, sort_order, published_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$category_id, $title_ar, $title_fr, $slug, $excerpt_ar ?: null, $excerpt_fr ?: null, $final_url, $status, $sort_order, $published_at]);
            $message = 'Added.';
        }
        header('Location: live.php?msg=' . urlencode($message));
        exit;
    } elseif ($title_ar || $title_fr) {
        $message = 'Provide a video URL or upload a video file.';
    }
}

$message = $message ?: ($_GET['msg'] ?? '');
$items = $pdo->prepare('SELECT id, title_ar, title_fr, video_url, status, sort_order, created_at FROM articles WHERE category_id = ? ORDER BY sort_order ASC, published_at DESC, id DESC');
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
  <a href="live.php" class="btn btn-outline-secondary btn-sm">List</a>
  <?php if (!$editing): ?><a href="live.php?add=1" class="btn btn-primary btn-sm">Add new</a><?php endif; ?>
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
      <label class="form-label">Video URL (YouTube, Vimeo, or .mp4 link)</label>
      <input type="url" name="video_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($editing['video_url'] ?? ''); ?>">
      <small class="text-muted">Leave empty if uploading a file.</small>
    </div>
    <div class="mb-2">
      <label class="form-label">Or upload video file (mp4, webm, ogg, max 100 MB)</label>
      <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime">
      <?php if (!empty($editing['video_url']) && strpos($editing['video_url'], 'uploads/') === 0): ?>
      <small class="text-muted">Current: <?php echo htmlspecialchars($editing['video_url']); ?></small>
      <?php endif; ?>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-6">
        <label class="form-label">Excerpt (AR)</label>
        <textarea name="excerpt_ar" class="form-control" rows="2"><?php echo htmlspecialchars($editing['excerpt_ar'] ?? ''); ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Excerpt (FR)</label>
        <textarea name="excerpt_fr" class="form-control" rows="2"><?php echo htmlspecialchars($editing['excerpt_fr'] ?? ''); ?></textarea>
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
    <?php if ($editing): ?><a href="live.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
  </div>
</form>
<?php endif; ?>

<table class="table table-sm">
  <thead><tr><th>Title</th><th>Video</th><th>Status</th><th>Order</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $i): ?>
    <tr>
      <td><?php echo htmlspecialchars($i['title_ar'] ?: $i['title_fr']); ?></td>
      <td><a href="<?php echo htmlspecialchars($i['video_url']); ?>" target="_blank" rel="noopener">Link</a></td>
      <td><?php echo htmlspecialchars($i['status']); ?></td>
      <td><?php echo (int)$i['sort_order']; ?></td>
      <td>
        <a href="live.php?edit=<?php echo (int)$i['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
        <form method="post" class="d-inline" onsubmit="return confirm('Delete?');">
          <input type="hidden" name="delete" value="<?php echo (int)$i['id']; ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php if (empty($items)): ?><p class="text-muted">No items yet. Add one above.</p><?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
