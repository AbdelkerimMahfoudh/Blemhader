<?php
require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/helpers.php';

// News scopes: National (local) / Regional / International (global)
$news_scopes = [
    'local'    => ['ar' => 'وطني',   'fr' => 'National'],
    'regional' => ['ar' => 'إقليمي', 'fr' => 'Régional'],
    'global'   => ['ar' => 'دولي',   'fr' => 'International'],
];

$by_scope = ['local' => [], 'regional' => [], 'global' => []];
if ($pdo) {
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $stmt->execute(['news']);
    $cat = $stmt->fetch();
    if ($cat) {
        $st = $pdo->prepare('SELECT id, scope, title_ar, title_fr, image_url, video_url, excerpt_ar, excerpt_fr, body_ar, body_fr, published_at FROM articles WHERE category_id = ? AND status = "published" ORDER BY sort_order ASC, published_at DESC, id DESC');
        $st->execute([(int)$cat['id']]);
        foreach ($st->fetchAll() as $row) {
            $sc = isset($by_scope[$row['scope']]) ? $row['scope'] : 'local';
            $by_scope[$sc][] = $row;
        }
    }
}

// Active tab from ?scope=, default to the first non-empty scope (else 'local')
$active = $_GET['scope'] ?? '';
if (!isset($news_scopes[$active])) {
    $active = 'local';
    foreach ($news_scopes as $key => $_l) { if (!empty($by_scope[$key])) { $active = $key; break; } }
}
?>

<div class="container">
  <div class="section-header">
    <div class="section-title">
      <span class="ar-text">أخبار</span>
      <span class="fr-text">Actualités</span>
    </div>
  </div>

  <!-- News scope tabs -->
  <div class="news-tabs" role="tablist">
    <?php foreach ($news_scopes as $key => $label): ?>
    <button type="button" class="news-tab<?php echo $key === $active ? ' active' : ''; ?>" data-scope="<?php echo $key; ?>" role="tab" aria-selected="<?php echo $key === $active ? 'true' : 'false'; ?>">
      <span class="ar-text"><?php echo htmlspecialchars($label['ar']); ?></span>
      <span class="fr-text"><?php echo htmlspecialchars($label['fr']); ?></span>
      <span class="news-tab-count"><?php echo count($by_scope[$key]); ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <?php foreach ($news_scopes as $key => $label): $items = $by_scope[$key]; ?>
  <div class="news-panel<?php echo $key === $active ? ' active' : ''; ?>" data-scope="<?php echo $key; ?>" role="tabpanel">
    <?php if (empty($items)): ?>
    <p class="ar-text" style="color: var(--mid); line-height: 1.7;">لا توجد أخبار في قسم «<?php echo htmlspecialchars($label['ar']); ?>» حالياً. المحتوى قيد الإعداد.</p>
    <p class="fr-text" style="color: var(--mid); line-height: 1.7;">Aucune actualité dans la rubrique « <?php echo htmlspecialchars($label['fr']); ?> » pour le moment. Contenu en cours de préparation.</p>
    <?php else: ?>
    <div class="post-card-grid">
      <?php foreach ($items as $item):
        $preview_ar = post_preview($item['excerpt_ar'] ?? '', $item['body_ar'] ?? '');
        $preview_fr = post_preview($item['excerpt_fr'] ?? '', $item['body_fr'] ?? '');
        $thumb = item_thumb_url($item);
        $has_video = !empty($item['video_url']);
      ?>
      <div class="post-card">
        <a href="article.php?id=<?php echo (int)$item['id']; ?>" class="post-card-link">
          <div class="post-card-img-wrap">
            <?php if ($thumb): ?>
            <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" class="post-card-img">
            <?php elseif ($has_video && preg_match('#\.(mp4|webm|ogg|mov)(\?|$)#i', $item['video_url'])): ?>
            <video src="<?php echo htmlspecialchars($item['video_url']); ?>#t=0.5" class="post-card-img" muted playsinline preload="metadata"></video>
            <?php else: ?>
            <div class="post-card-img-placeholder"></div>
            <?php endif; ?>
            <?php if ($has_video): ?><span class="post-card-play" aria-hidden="true">&#9654;</span><?php endif; ?>
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
              <?php if ($has_video): ?>
              <span class="ar-text">شاهد الفيديو</span>
              <span class="fr-text">Voir la vidéo</span>
              <?php else: ?>
              <span class="ar-text">اقرأ المزيد</span>
              <span class="fr-text">Lire la suite</span>
              <?php endif; ?>
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
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>