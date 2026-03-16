<?php
require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/helpers.php';

$items = [];
if ($pdo) {
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $stmt->execute(['documentaries']);
    $cat = $stmt->fetch();
    if ($cat) {
        $st = $pdo->prepare('SELECT id, title_ar, title_fr, video_url, excerpt_ar, excerpt_fr, published_at FROM articles WHERE category_id = ? AND status = "published" ORDER BY sort_order ASC, published_at DESC, id DESC');
        $st->execute([(int)$cat['id']]);
        $items = $st->fetchAll();
    }
}
?>

<div class="container">
  <div class="section-header">
    <div class="section-title">
      <span class="ar-text">وثائقيات</span>
      <span class="fr-text">Documentaires</span>
    </div>
  </div>

  <?php if (empty($items)): ?>
  <p class="ar-text" style="color: var(--mid); line-height: 1.7;">أفلام وثائقية وتقارير مصورة. المحتوى قيد الإعداد.</p>
  <p class="fr-text" style="color: var(--mid); line-height: 1.7;">Documentaires et reportages. Contenu en cours de préparation.</p>
  <?php else: ?>
  <div class="video-card-grid">
    <?php foreach ($items as $item): ?>
    <div class="video-card">
      <div class="video-wrap">
        <?php echo embed_video($item['video_url']); ?>
      </div>
      <div class="video-body">
        <h3>
          <span class="ar-text"><?php echo htmlspecialchars($item['title_ar']); ?></span>
          <span class="fr-text"><?php echo htmlspecialchars($item['title_fr']); ?></span>
        </h3>
        <?php if (!empty($item['excerpt_ar']) || !empty($item['excerpt_fr'])): ?>
        <p>
          <span class="ar-text"><?php echo htmlspecialchars($item['excerpt_ar'] ?? ''); ?></span>
          <span class="fr-text"><?php echo htmlspecialchars($item['excerpt_fr'] ?? ''); ?></span>
        </p>
        <?php endif; ?>
        <?php if (!empty($item['published_at'])): ?>
        <div class="card-meta" style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);font-size:11px;color:#9ca3af;">
          <?php echo date('d/m/Y', strtotime($item['published_at'])); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
