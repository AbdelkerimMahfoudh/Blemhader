<?php
require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/helpers.php';

$items = [];
if ($pdo) {
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $stmt->execute(['news']);
    $cat = $stmt->fetch();
    if ($cat) {
        $st = $pdo->prepare('SELECT id, title_ar, title_fr, image_url, excerpt_ar, excerpt_fr, body_ar, body_fr, published_at FROM articles WHERE category_id = ? AND status = "published" ORDER BY sort_order ASC, published_at DESC, id DESC');
        $st->execute([(int)$cat['id']]);
        $items = $st->fetchAll();
    }
}
?>

<div class="container">
  <div class="section-header">
    <div class="section-title">
      <span class="ar-text">أخبار</span>
      <span class="fr-text">Actualités</span>
    </div>
  </div>

  <?php if (empty($items)): ?>
  <p class="ar-text" style="color: var(--mid); line-height: 1.7;">هنا تجد آخر الأخبار والمستجدات. المحتوى قيد الإعداد.</p>
  <p class="fr-text" style="color: var(--mid); line-height: 1.7;">Retrouvez ici les dernières actualités. Contenu en cours de préparation.</p>
  <?php else: ?>
  <div class="post-card-grid">
    <?php foreach ($items as $item):
      $preview_ar = post_preview($item['excerpt_ar'] ?? '', $item['body_ar'] ?? '');
      $preview_fr = post_preview($item['excerpt_fr'] ?? '', $item['body_fr'] ?? '');
    ?>
    <div class="post-card">
      <a href="article.php?id=<?php echo (int)$item['id']; ?>" class="post-card-link">
        <div class="post-card-img-wrap">
          <?php if (!empty($item['image_url'])): ?>
          <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="" class="post-card-img">
          <?php else: ?>
          <div class="post-card-img-placeholder"></div>
          <?php endif; ?>
        </div>
        <div class="post-card-body">
          <h3>
            <span class="ar-text"><?php echo htmlspecialchars($item['title_ar']); ?></span>
            <span class="fr-text"><?php echo htmlspecialchars($item['title_fr']); ?></span>
          </h3>
          <p>
            <span class="ar-text"><?php echo htmlspecialchars($preview_ar); ?></span>
            <span class="fr-text"><?php echo htmlspecialchars($preview_fr); ?></span>
          </p>
          <span class="post-card-more">
            <span class="ar-text">اقرأ المزيد</span>
            <span class="fr-text">Lire la suite</span>
          </span>
        </div>
      </a>
      <?php if (!empty($item['published_at'])): ?>
      <div class="post-card-meta"><?php echo date('d/m/Y', strtotime($item['published_at'])); ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
