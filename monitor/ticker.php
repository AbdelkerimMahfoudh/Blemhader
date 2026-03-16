<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$admin_section = 'ticker';
$admin_page_title = 'عاجل / Ticker (trending)';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $del_id = (int)$_POST['delete'];
        $pdo->prepare('DELETE FROM ticker_items WHERE id = ?')->execute([$del_id]);
        $message = 'Deleted.';
        header('Location: ticker.php?msg=' . urlencode($message));
        exit;
    }

    $text_ar = trim($_POST['text_ar'] ?? '');
    $text_fr = trim($_POST['text_fr'] ?? '');
    $url = trim($_POST['url'] ?? '');

    if ($text_ar && $text_fr) {
        $pdo->prepare('INSERT INTO ticker_items (text_ar, text_fr, url, sort_order, is_active) VALUES (?, ?, ?, 0, 1)')
            ->execute([$text_ar, $text_fr, $url ?: null]);
        $message = 'Added. Items show for 10 minutes, then disappear.';
        header('Location: ticker.php?msg=' . urlencode($message));
        exit;
    } else {
        $message = 'Text (AR & FR) required.';
    }
}

$message = $message ?: ($_GET['msg'] ?? '');

// Show items from last 10 minutes (same filter as public ticker)
$st = $pdo->prepare("
  SELECT id, text_ar, text_fr, url, created_at
  FROM ticker_items
  WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
  ORDER BY created_at DESC
");
$st->execute();
$items = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<p class="text-muted mb-3">Trending news appear in the breaking bar for 10 minutes, then disappear automatically.</p>

<form method="post" class="card mb-4">
  <div class="card-body">
    <h6>Add trending news</h6>
    <div class="row g-2 mb-2">
      <div class="col-md-6">
        <label class="form-label">Text (AR)</label>
        <input type="text" name="text_ar" class="form-control" required placeholder="النص بالعربية" maxlength="500">
      </div>
      <div class="col-md-6">
        <label class="form-label">Text (FR)</label>
        <input type="text" name="text_fr" class="form-control" required placeholder="Texte en français" maxlength="500">
      </div>
    </div>
    <div class="mb-2">
      <label class="form-label">URL (optional)</label>
      <input type="url" name="url" class="form-control" placeholder="https://...">
    </div>
    <button type="submit" class="btn btn-primary">Add</button>
  </div>
</form>

<h6>Currently trending (last 10 min)</h6>
<table class="table table-sm">
  <thead><tr><th>AR</th><th>FR</th><th>Added</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $i): ?>
    <tr>
      <td><?php echo htmlspecialchars($i['text_ar']); ?></td>
      <td><?php echo htmlspecialchars($i['text_fr']); ?></td>
      <td><?php echo htmlspecialchars($i['created_at']); ?></td>
      <td>
        <form method="post" class="d-inline" onsubmit="return confirm('Remove from ticker?');">
          <input type="hidden" name="delete" value="<?php echo (int)$i['id']; ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php if (empty($items)): ?><p class="text-muted">No trending items. Add one above.</p><?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
