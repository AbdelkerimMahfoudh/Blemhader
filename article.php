<?php
require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = null;
if ($id && $pdo) {
    $stmt = $pdo->prepare('SELECT a.*, c.slug as cat_slug, c.name_ar as cat_ar, c.name_fr as cat_fr, COALESCE(NULLIF(u.display_name, ""), u.username) AS author_name FROM articles a JOIN categories c ON a.category_id = c.id LEFT JOIN users u ON a.author_id = u.id WHERE a.id = ? AND a.status = ?');
    $stmt->execute([$id, 'published']);
    $article = $stmt->fetch();
}
if (!$article) {
    header('HTTP/1.0 404 Not Found');
    echo '<div class="container py-5"><p class="ar-text">المقال غير موجود.</p><p class="fr-text">L\'article n\'existe pas.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$back_file = in_array($article['cat_slug'], ['news','economy']) ? $article['cat_slug'] . '.php' : 'index.php';
$back_ar = $article['cat_ar'];
$back_fr = $article['cat_fr'];
?>
<div class="container">
  <div class="py-3">
    <a href="<?php echo htmlspecialchars($back_file); ?>" class="text-decoration-none" style="color:var(--accent);">
      <span class="ar-text">← <?php echo htmlspecialchars($back_ar); ?></span>
      <span class="fr-text">← <?php echo htmlspecialchars($back_fr); ?></span>
    </a>
  </div>
  <article class="post-single">
    <h1 class="mb-3">
      <span class="ar-text"><?php echo htmlspecialchars($article['title_ar']); ?></span>
      <span class="fr-text"><?php echo htmlspecialchars($article['title_fr']); ?></span>
    </h1>
    <?php $video_html = embed_video($article['video_url'] ?? ''); ?>
    <?php if ($video_html): ?>
    <?php /* Facebook embeds size themselves to the video's real shape; others go in a 16:9 box */ ?>
    <div class="post-video<?php echo strpos($video_html, 'fb-video') !== false ? ' post-video-fb' : ''; ?> mb-4"><?php echo $video_html; ?></div>
    <?php elseif (!empty($article['image_url'])): $img = htmlspecialchars($article['image_url']); ?>
    <div class="post-thumb mb-4" style="position:relative;height:420px;overflow:hidden;border-radius:8px;">
      <!-- Blurred copy of the photo fills the gutters so portraits don't leave blank bands and nothing is cropped -->
      <div style="position:absolute;inset:0;background:url('<?php echo $img; ?>') center center / cover no-repeat;filter:blur(22px) brightness(0.8);transform:scale(1.2);"></div>
      <img src="<?php echo $img; ?>" alt="" style="position:relative;z-index:1;display:block;width:100%;height:100%;object-fit:contain;">
    </div>
    <?php endif; ?>
    <?php if (!empty($article['published_at'])): ?>
    <div class="text-muted small mb-1"><?php echo date('d/m/Y', strtotime($article['published_at'])); ?></div>
    <?php endif; ?>
    <?php if (!empty($article['show_author']) && !empty($article['author_name'])): ?>
    <div class="text-muted small mb-3" style="font-weight:700;">
      <span class="ar-text">بقلم: <?php echo htmlspecialchars($article['author_name']); ?></span>
      <span class="fr-text">Par : <?php echo htmlspecialchars($article['author_name']); ?></span>
    </div>
    <?php endif; ?>
    <div class="post-body" style="line-height:1.8;">
      <div class="ar-text"><?php echo nl2br(htmlspecialchars($article['body_ar'] ?? '')); ?></div>
      <div class="fr-text"><?php echo nl2br(htmlspecialchars($article['body_fr'] ?? '')); ?></div>
    </div>
  </article>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>