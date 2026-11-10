<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/helpers.php';

$admin_section = 'news';
$admin_page_title = 'أخبار / News';

$stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
$stmt->execute(['news']);
$cat = $stmt->fetch();
$category_id = $cat ? (int)$cat['id'] : 0;

if (!$category_id) {
    die('Category "news" not found.');
}

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    // PHP discards the whole request when it exceeds post_max_size (typical with large videos)
    $message = 'Upload too large for the server (post_max_size = ' . ini_get('post_max_size') . '). Use a smaller file or a video link.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_ar = trim($_POST['title_ar'] ?? '');
    $title_fr = trim($_POST['title_fr'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $media_type = ($_POST['media_type'] ?? 'image') === 'video' ? 'video' : 'image';
    $excerpt_ar = trim($_POST['excerpt_ar'] ?? '');
    $excerpt_fr = trim($_POST['excerpt_fr'] ?? '');
    $body_ar = trim($_POST['body_ar'] ?? '');
    $body_fr = trim($_POST['body_fr'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft','published']) ? $_POST['status'] : 'draft';
    $scope = in_array($_POST['scope'] ?? '', ['local','regional','global'], true) ? $_POST['scope'] : 'local';
    $show_author = isset($_POST['show_author']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if (isset($_POST['delete'])) {
        $del_id = (int)$_POST['delete'];
        $pdo->prepare('DELETE FROM articles WHERE id = ? AND category_id = ?')->execute([$del_id, $category_id]);
        $message = 'Deleted.';
        header('Location: news.php?msg=' . urlencode($message));
        exit;
    }

    // A post has either a photo or a video (never both)
    $existing = ['image_url' => '', 'video_url' => ''];
    if ($edit_id) {
        $row = $pdo->prepare('SELECT image_url, video_url FROM articles WHERE id = ? AND category_id = ?');
        $row->execute([$edit_id, $category_id]);
        $existing = $row->fetch() ?: $existing;
    }

    $final_image = '';
    $final_video = '';
    $video_file_attempted = false;
    $uploaded_video = '';
    if ($media_type === 'image') {
        $final_image = handle_image_upload() ?: $image_url;
        if (!$final_image) $final_image = trim($existing['image_url'] ?? '');
    } else {
        $video_file_attempted = !empty($_FILES['video_file']['name']);
        $uploaded_video = handle_video_upload();
        $final_video = $uploaded_video ?: $video_url;
        if (!$final_video && !$video_file_attempted) $final_video = trim($existing['video_url'] ?? '');
    }

    if ($video_file_attempted && !$uploaded_video) {
        $message = 'Video upload failed (allowed: mp4, webm, ogg, mov; max ' . (int)(MAX_VIDEO_SIZE / 1024 / 1024) . ' MB; also check the server upload_max_filesize / post_max_size).';
    } elseif ($media_type === 'video' && $video_url && !$uploaded_video && embed_video($video_url) === '') {
        $message = 'Unsupported video link. Use a YouTube, Vimeo, Facebook or direct .mp4/.webm/.ogg link.';
    } elseif ($media_type === 'video' && !$final_video && ($title_ar || $title_fr)) {
        $message = 'Video selected: paste a video link or upload a video file.';
    } elseif ($title_ar && $title_fr && ($body_ar || $body_fr || $final_video)) {
        $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
        $slug = 'news-' . ($edit_id ? $edit_id : time());
        if ($edit_id) {
            $editor_id = (int)($_SESSION['admin_user_id'] ?? 0) ?: null;
            $pdo->prepare('UPDATE articles SET title_ar=?, title_fr=?, image_url=?, video_url=?, excerpt_ar=?, excerpt_fr=?, body_ar=?, body_fr=?, status=?, scope=?, show_author=?, author_id=COALESCE(author_id, ?), sort_order=?, published_at=? WHERE id=? AND category_id=?')
                ->execute([$title_ar, $title_fr, $final_image ?: null, $final_video ?: null, $excerpt_ar ?: null, $excerpt_fr ?: null, $body_ar ?: null, $body_fr ?: null, $status, $scope, $show_author, $editor_id, $sort_order, $published_at ?: null, $edit_id, $category_id]);
            $message = 'Updated.';
        } else {
            $author_id = (int)($_SESSION['admin_user_id'] ?? 0) ?: null;
            $pdo->prepare('INSERT INTO articles (category_id, scope, author_id, show_author, title_ar, title_fr, slug, excerpt_ar, excerpt_fr, body_ar, body_fr, image_url, video_url, status, sort_order, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$category_id, $scope, $author_id, $show_author, $title_ar, $title_fr, $slug, $excerpt_ar ?: null, $excerpt_fr ?: null, $body_ar ?: null, $body_fr ?: null, $final_image ?: null, $final_video ?: null, $status, $sort_order, $published_at]);
            $message = 'Added.';
        }
        header('Location: news.php?msg=' . urlencode($message));
        exit;
    } elseif ($title_ar || $title_fr) {
        $message = 'Title (AR & FR) and at least a body (AR or FR) or a video are required.';
    }
}

$message = $message ?: ($_GET['msg'] ?? '');
$items = $pdo->prepare('SELECT id, title_ar, title_fr, image_url, video_url, status, scope, sort_order, created_at FROM articles WHERE category_id = ? ORDER BY sort_order ASC, published_at DESC, id DESC');
$items->execute([$category_id]);
$items = $items->fetchAll();

$editing = null;
if ($edit_id) {
    // Always load the full row (the list query above omits body/excerpt columns,
    // so reusing it would leave the edit form's body fields empty and break saves).
    $st = $pdo->prepare('SELECT * FROM articles WHERE id = ? AND category_id = ?');
    $st->execute([$edit_id, $category_id]);
    $editing = $st->fetch();
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="mb-4">
  <a href="news.php" class="btn btn-outline-secondary btn-sm">List</a>
  <?php if (!$editing): ?><a href="news.php?add=1" class="btn btn-primary btn-sm">Add new</a><?php endif; ?>
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
    <?php $is_video = ($_POST['media_type'] ?? (!empty($editing['video_url']) ? 'video' : 'image')) === 'video'; ?>
    <div class="mb-2">
      <label class="form-label d-block">Media</label>
      <div class="btn-group mb-2" role="group">
        <input type="radio" class="btn-check" name="media_type" id="media_image" value="image" <?php echo $is_video ? '' : 'checked'; ?>>
        <label class="btn btn-outline-primary btn-sm" for="media_image">Photo</label>
        <input type="radio" class="btn-check" name="media_type" id="media_video" value="video" <?php echo $is_video ? 'checked' : ''; ?>>
        <label class="btn btn-outline-primary btn-sm" for="media_video">Video</label>
      </div>
      <div id="media_image_fields"<?php echo $is_video ? ' style="display:none"' : ''; ?>>
        <input type="url" name="image_url" class="form-control mb-1" placeholder="https://... (optional)" value="<?php echo htmlspecialchars($editing['image_url'] ?? ''); ?>">
        <label class="form-label small text-muted">Or upload image (jpg, png, webp, gif, max 5 MB)</label>
        <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
        <?php if (!empty($editing['image_url'])): ?>
        <small class="text-muted">Current: <?php echo htmlspecialchars($editing['image_url']); ?></small>
        <?php endif; ?>
      </div>
      <div id="media_video_fields"<?php echo $is_video ? '' : ' style="display:none"'; ?>>
        <input type="url" name="video_url" class="form-control mb-1" placeholder="YouTube, Vimeo, Facebook or direct .mp4 link" value="<?php echo htmlspecialchars((!empty($editing['video_url']) && strpos($editing['video_url'], 'uploads/') !== 0) ? $editing['video_url'] : ''); ?>">
        <label class="form-label small text-muted">Or upload video (mp4, webm, ogg, mov, max <?php echo (int)(MAX_VIDEO_SIZE / 1024 / 1024); ?> MB)</label>
        <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime">
        <?php if (!empty($editing['video_url'])): ?>
        <small class="text-muted">Current: <?php echo htmlspecialchars($editing['video_url']); ?></small>
        <?php endif; ?>
      </div>
    </div>
    <script>
    document.querySelectorAll('input[name="media_type"]').forEach(function (r) {
      r.addEventListener('change', function () {
        var video = document.getElementById('media_video').checked;
        document.getElementById('media_image_fields').style.display = video ? 'none' : '';
        document.getElementById('media_video_fields').style.display = video ? '' : 'none';
      });
    });
    </script>
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
        <label class="form-label">Body (AR) – full post (optional for video posts)</label>
        <textarea name="body_ar" class="form-control" rows="8"><?php echo htmlspecialchars($editing['body_ar'] ?? ''); ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Body (FR)</label>
        <textarea name="body_fr" class="form-control" rows="8"><?php echo htmlspecialchars($editing['body_fr'] ?? ''); ?></textarea>
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
        <label class="form-label">Scope / القسم</label>
        <select name="scope" class="form-select">
          <option value="local" <?php echo ($editing['scope'] ?? 'local') === 'local' ? 'selected' : ''; ?>>National · وطني</option>
          <option value="regional" <?php echo ($editing['scope'] ?? '') === 'regional' ? 'selected' : ''; ?>>Régional · إقليمي</option>
          <option value="global" <?php echo ($editing['scope'] ?? '') === 'global' ? 'selected' : ''; ?>>International · دولي</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Sort order</label>
        <input type="number" name="sort_order" class="form-control" value="<?php echo (int)($editing['sort_order'] ?? 0); ?>">
      </div>
    </div>
    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" role="switch" id="show_author" name="show_author" value="1" <?php echo !empty($editing['show_author']) ? 'checked' : ''; ?>>
      <label class="form-check-label" for="show_author">Show publisher / إظهار الناشر <span class="text-muted small">(displays your name below the date)</span></label>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Update' : 'Add'; ?></button>
    <?php if ($editing): ?><a href="news.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
  </div>
</form>
<?php endif; ?>

<table class="table table-sm">
  <thead><tr><th>Title</th><th>Media</th><th>Scope</th><th>Status</th><th>Order</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $i): ?>
    <tr>
      <td><?php echo htmlspecialchars($i['title_ar'] ?: $i['title_fr']); ?></td>
      <td><?php echo !empty($i['video_url']) ? 'Video' : (!empty($i['image_url']) ? 'Photo' : '—'); ?></td>
      <td><?php $sc = $i['scope'] ?? 'local'; echo ['local'=>'وطني','regional'=>'إقليمي','global'=>'دولي'][$sc] ?? $sc; ?></td>
      <td><?php echo htmlspecialchars($i['status']); ?></td>
      <td><?php echo (int)$i['sort_order']; ?></td>
      <td>
        <a href="news.php?edit=<?php echo (int)$i['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
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