<?php
require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/helpers.php';

$hero_slugs = ['live', 'meetings', 'news', 'economy', 'documentaries'];
$hero_items = [];
if ($pdo) {
    foreach ($hero_slugs as $slug) {
        $st = $pdo->prepare('SELECT c.id, c.slug, c.name_ar, c.name_fr FROM categories c WHERE c.slug = ?');
        $st->execute([$slug]);
        $cat = $st->fetch();
        if (!$cat) continue;
        $st2 = $pdo->prepare('SELECT a.id, a.title_ar, a.title_fr, a.video_url, a.image_url, a.published_at, a.read_minutes, a.excerpt_ar, a.excerpt_fr, a.body_ar, a.body_fr FROM articles a WHERE a.category_id = ? AND a.status = "published" ORDER BY a.sort_order ASC, a.published_at DESC, a.id DESC LIMIT 1');
        $st2->execute([(int)$cat['id']]);
        $item = $st2->fetch();
        if ($item) {
            $item['cat_ar'] = $cat['name_ar'];
            $item['cat_fr'] = $cat['name_fr'];
            $item['cat_slug'] = $cat['slug'];
            $hero_items[$slug] = $item;
        }
    }
}
$hero_slides_order = ['live', 'meetings', 'news', 'economy', 'documentaries'];
$hero_slides = [];
foreach ($hero_slides_order as $slug) {
    $it = $hero_items[$slug] ?? null;
    if (!$it) continue;
    $link = in_array($slug, ['news','economy']) ? 'article.php?id=' . $it['id'] : $slug . '.php';
    $media = '';
    if (!empty($it['video_url'])) {
        $media = embed_video($it['video_url']);
    } elseif (!empty($it['image_url'])) {
        $media = '<img src="' . htmlspecialchars($it['image_url']) . '" alt="" style="width:100%;height:100%;object-fit:cover;">';
    } else {
        $media = '<div class="thumb-placeholder bg-1" style="width:100%;height:100%;"></div>';
    }
    $meta_ar = $meta_fr = '—';
    if (!empty($it['published_at'])) {
        $ts = strtotime($it['published_at']);
        $j = (int)date('j', $ts); $m = (int)date('n', $ts); $y = date('Y', $ts);
        $meta_ar = $j . ' ' . ($ar_months[$m - 1] ?? '') . '، ' . $y;
        $meta_fr = $j . ' ' . ($fr_months[$m - 1] ?? '') . ' ' . $y;
        if (!empty($it['read_minutes'])) {
            $meta_ar .= ' · ' . (int)$it['read_minutes'] . ' دقائق';
            $meta_fr .= ' · ' . (int)$it['read_minutes'] . ' min';
        }
    }
    $hero_slides[] = [
        'link' => $link,
        'media' => $media,
        'catAr' => $it['cat_ar'] ?? '',
        'catFr' => $it['cat_fr'] ?? '',
        'titleAr' => $it['title_ar'] ?? '',
        'titleFr' => $it['title_fr'] ?? '',
        'metaAr' => $meta_ar,
        'metaFr' => $meta_fr
    ];
}
$hero_main = $hero_slides[0] ?? null;
$hero_side_order = ['meetings', 'news', 'economy', 'documentaries'];
$bg_class = ['meetings' => 'bg-2', 'news' => 'bg-3', 'economy' => 'bg-4', 'documentaries' => 'bg-7'];

$section_items = [];
if ($pdo) {
    $section_slugs = ['live', 'meetings', 'news', 'economy', 'documentaries'];
    foreach ($section_slugs as $slug) {
        $st = $pdo->prepare('SELECT c.id, c.name_ar, c.name_fr FROM categories c WHERE c.slug = ?');
        $st->execute([$slug]);
        $cat = $st->fetch();
        if (!$cat) continue;
        $limit = in_array($slug, ['economy']) ? 5 : (in_array($slug, ['news','documentaries']) ? 3 : 4);
        $st2 = $pdo->prepare('SELECT a.id, a.title_ar, a.title_fr, a.video_url, a.image_url, a.excerpt_ar, a.excerpt_fr, a.body_ar, a.body_fr, a.published_at, a.read_minutes FROM articles a WHERE a.category_id = ? AND a.status = "published" ORDER BY a.sort_order ASC, a.published_at DESC, a.id DESC LIMIT ' . (int)$limit);
        $st2->execute([(int)$cat['id']]);
        $section_items[$slug] = ['cat' => $cat, 'items' => $st2->fetchAll()];
    }
}
?>

<!-- MAIN CONTENT -->
<div class="container">

  <!-- الرئيسية GRID (rotates every 5s, latest from each category) -->
  <div class="hero-grid">
    <div class="hero-main" id="hero-main-card">
      <?php if ($hero_main): ?>
      <a href="<?php echo htmlspecialchars($hero_main['link']); ?>" id="hero-main-link" style="display:block;position:relative;height:420px;">
        <div class="hero-video-wrap">
          <div class="hero-embed" id="hero-media"><?php echo $hero_main['media']; ?></div>
        </div>
        <div class="hero-overlay">
          <span class="cat-tag">
            <span class="ar-text" id="hero-cat-ar"><?php echo htmlspecialchars($hero_main['catAr']); ?></span>
            <span class="fr-text" id="hero-cat-fr"><?php echo htmlspecialchars($hero_main['catFr']); ?></span>
          </span>
          <h2>
            <span class="ar-text" id="hero-title-ar"><?php echo htmlspecialchars($hero_main['titleAr']); ?></span>
            <span class="fr-text" id="hero-title-fr"><?php echo htmlspecialchars($hero_main['titleFr']); ?></span>
          </h2>
          <div class="meta">
            <span class="ar-text" id="hero-meta-ar"><?php echo htmlspecialchars($hero_main['metaAr']); ?></span>
            <span class="fr-text" id="hero-meta-fr"><?php echo htmlspecialchars($hero_main['metaFr']); ?></span>
          </div>
        </div>
      </a>
      <script>window.HERO_SLIDES = <?php echo json_encode($hero_slides); ?>;</script>
      <?php else: ?>
      <div class="hero-video-wrap">
        <div class="thumb-placeholder bg-1" style="width:100%;height:100%;"></div>
      </div>
      <div class="hero-overlay">
        <span class="cat-tag"><span class="ar-text">البثوث المباشرة</span><span class="fr-text">Les directs</span></span>
        <h2><span class="ar-text">المحتوى قيد الإعداد</span><span class="fr-text">Contenu en cours de préparation</span></h2>
      </div>
      <?php endif; ?>
    </div>

    <div class="hero-side">
      <?php foreach ($hero_side_order as $slug): $item = $hero_items[$slug] ?? null; if (!$item) continue;
        $href = in_array($slug, ['news','economy']) ? 'article.php?id=' . $item['id'] : $slug . '.php';
        $thumb = item_thumb_url($item);
      ?>
      <a href="<?php echo htmlspecialchars($href); ?>" class="side-card text-decoration-none text-dark d-flex">
        <?php if ($thumb): ?>
        <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" class="thumb">
        <?php else: ?>
        <div class="thumb-placeholder <?php echo $bg_class[$slug] ?? 'bg-2'; ?> thumb"></div>
        <?php endif; ?>
        <div class="info">
          <span class="cat-mini">
            <span class="ar-text"><?php echo htmlspecialchars($item['cat_ar'] ?? ''); ?></span>
            <span class="fr-text"><?php echo htmlspecialchars($item['cat_fr'] ?? ''); ?></span>
          </span>
          <h3>
            <span class="ar-text"><?php echo htmlspecialchars($item['title_ar'] ?? ''); ?></span>
            <span class="fr-text"><?php echo htmlspecialchars($item['title_fr'] ?? ''); ?></span>
          </h3>
          <span class="date">
            <?php if (!empty($item['published_at'])): $ts = strtotime($item['published_at']); $j = (int)date('j', $ts); $m = (int)date('n', $ts); ?>
            <span class="ar-text"><?php echo $j . ' ' . ($ar_months[$m - 1] ?? '') . '، ' . date('Y', $ts); ?></span>
            <span class="fr-text"><?php echo $j . ' ' . ($fr_months[$m - 1] ?? '') . ' ' . date('Y', $ts); ?></span>
            <?php else: ?>—<?php endif; ?>
          </span>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (empty(array_filter($hero_items))): ?>
      <p class="text-muted small"><span class="ar-text">أضف محتوى من monitor.blemhader لعرضه هنا.</span><span class="fr-text">Ajoutez du contenu via monitor.blemhader pour l'afficher ici.</span></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- البثوث المباشرة (live.php) -->
  <div class="section-header">
    <div class="section-title">
      <span class="ar-text">البثوث المباشرة</span>
      <span class="fr-text">Les directs</span>
    </div>
    <a class="section-more" href="live.php">
      <span class="ar-text">مشاهدة الكل</span>
      <span class="fr-text">Voir tout</span>
    </a>
  </div>
  <?php $live_items = $section_items['live']['items'] ?? []; ?>
  <?php if (empty($live_items)): ?>
  <p class="ar-text" style="margin-bottom:24px;color:var(--mid);"><a href="live.php" style="color:var(--red);font-weight:700;">مشاهدة البث المباشر</a></p>
  <p class="fr-text" style="margin-bottom:24px;color:var(--mid);"><a href="live.php" style="color:var(--red);font-weight:700;">Voir le direct</a></p>
  <?php else: ?>
  <div class="video-card-grid">
    <?php foreach (array_slice($live_items, 0, 2) as $item): ?>
    <div class="video-card">
      <div class="video-wrap"><?php echo embed_video($item['video_url'] ?? ''); ?></div>
      <div class="video-body">
        <h3><a href="live.php" class="text-dark text-decoration-none"><span class="ar-text"><?php echo htmlspecialchars($item['title_ar']); ?></span><span class="fr-text"><?php echo htmlspecialchars($item['title_fr']); ?></span></a></h3>
        <?php if (!empty($item['published_at'])): ?><div class="card-meta" style="font-size:11px;color:#9ca3af;"><?php echo date('d/m/Y', strtotime($item['published_at'])); ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- لقاءات (meetings.php) -->
  <div class="section-header">
    <div class="section-title">
      <span class="ar-text">لقاءات</span>
      <span class="fr-text">Rencontres</span>
    </div>
    <a class="section-more" href="meetings.php">
      <span class="ar-text">مشاهدة الكل</span>
      <span class="fr-text">Voir tout</span>
    </a>
  </div>
  <?php $meetings_items = $section_items['meetings']['items'] ?? []; ?>
  <?php if (empty($meetings_items)): ?>
  <p class="text-muted"><span class="ar-text">المحتوى قيد الإعداد.</span><span class="fr-text">Contenu en cours de préparation.</span></p>
  <?php else: ?>
  <div class="video-card-grid">
    <?php foreach (array_slice($meetings_items, 0, 2) as $item): ?>
    <div class="video-card">
      <div class="video-wrap"><?php echo embed_video($item['video_url'] ?? ''); ?></div>
      <div class="video-body">
        <h3><a href="meetings.php" class="text-dark text-decoration-none"><span class="ar-text"><?php echo htmlspecialchars($item['title_ar']); ?></span><span class="fr-text"><?php echo htmlspecialchars($item['title_fr']); ?></span></a></h3>
        <?php if (!empty($item['published_at'])): ?><div class="card-meta" style="font-size:11px;color:#9ca3af;"><?php echo date('d/m/Y', strtotime($item['published_at'])); ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- أخبار (news.php) -->
  <div class="section-header">
    <div class="section-title">
      <span class="ar-text">أخبار</span>
      <span class="fr-text">Actualités</span>
    </div>
    <a class="section-more" href="news.php">
      <span class="ar-text">مشاهدة الكل</span>
      <span class="fr-text">Voir tout</span>
    </a>
  </div>
  <?php $news_items = $section_items['news']['items'] ?? []; ?>
  <?php if (empty($news_items)): ?>
  <p class="text-muted"><span class="ar-text">المحتوى قيد الإعداد.</span><span class="fr-text">Contenu en cours de préparation.</span></p>
  <?php else: ?>
  <div class="news-grid-3">
    <?php foreach ($news_items as $item): $prev_ar = post_preview($item['excerpt_ar'] ?? '', $item['body_ar'] ?? ''); $prev_fr = post_preview($item['excerpt_fr'] ?? '', $item['body_fr'] ?? ''); $thumb = item_thumb_url($item); ?>
    <a href="article.php?id=<?php echo (int)$item['id']; ?>" class="news-card text-decoration-none text-dark">
      <?php if ($thumb): ?><img src="<?php echo htmlspecialchars($thumb); ?>" alt="" class="card-img"><?php else: ?><div class="card-img-placeholder bg-5 card-img"></div><?php endif; ?>
      <span class="cat-tag"><span class="ar-text"><?php echo htmlspecialchars($section_items['news']['cat']['name_ar']); ?></span><span class="fr-text"><?php echo htmlspecialchars($section_items['news']['cat']['name_fr']); ?></span></span>
      <h3><span class="ar-text"><?php echo htmlspecialchars($item['title_ar']); ?></span><span class="fr-text"><?php echo htmlspecialchars($item['title_fr']); ?></span></h3>
      <p><span class="ar-text"><?php echo htmlspecialchars($prev_ar); ?></span><span class="fr-text"><?php echo htmlspecialchars($prev_fr); ?></span></p>
      <div class="card-meta">
        <?php if (!empty($item['published_at'])): ?><span class="ar-text"><?php echo date('d/m/Y', strtotime($item['published_at'])); ?></span><span class="fr-text"><?php echo date('d/m/Y', strtotime($item['published_at'])); ?></span><?php endif; ?>
        <?php if (!empty($item['read_minutes'])): ?><span>◷ <span class="ar-text"><?php echo (int)$item['read_minutes']; ?> دقائق</span><span class="fr-text"><?php echo (int)$item['read_minutes']; ?> min</span></span><?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- اقتصاد (economy.php) -->
  <div class="section-header">
    <div class="section-title">
      <span class="ar-text">اقتصاد</span>
      <span class="fr-text">Économie</span>
    </div>
    <a class="section-more" href="economy.php">
      <span class="ar-text">المزيد</span>
      <span class="fr-text">Plus</span>
    </a>
  </div>
  <?php $economy_items = $section_items['economy']['items'] ?? []; ?>
  <?php if (empty($economy_items)): ?>
  <p class="text-muted"><span class="ar-text">المحتوى قيد الإعداد.</span><span class="fr-text">Contenu en cours de préparation.</span></p>
  <?php else: ?>
  <?php foreach ($economy_items as $i => $item): $n = str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?>
  <a href="article.php?id=<?php echo (int)$item['id']; ?>" class="list-article text-decoration-none text-dark d-flex">
    <div class="list-num"><?php echo $n; ?></div>
    <div class="list-content">
      <h4><span class="ar-text"><?php echo htmlspecialchars($item['title_ar']); ?></span><span class="fr-text"><?php echo htmlspecialchars($item['title_fr']); ?></span></h4>
      <?php if (!empty($item['published_at'])): ?>
      <div class="meta"><span class="ar-text"><?php echo date('d/m/Y', strtotime($item['published_at'])); ?></span><span class="fr-text"><?php echo date('d/m/Y', strtotime($item['published_at'])); ?></span></div>
      <?php endif; ?>
    </div>
  </a>
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- وثائقيات (documentaries.php) -->
  <div class="section-header">
    <div class="section-title">
      <span class="ar-text">وثائقيات</span>
      <span class="fr-text">Documentaires</span>
    </div>
    <a class="section-more" href="documentaries.php">
      <span class="ar-text">مشاهدة الكل</span>
      <span class="fr-text">Voir tout</span>
    </a>
  </div>
  <?php $doc_items = $section_items['documentaries']['items'] ?? []; ?>
  <?php if (empty($doc_items)): ?>
  <p class="text-muted"><span class="ar-text">المحتوى قيد الإعداد.</span><span class="fr-text">Contenu en cours de préparation.</span></p>
  <?php else: ?>
  <div class="video-card-grid">
    <?php foreach ($doc_items as $item): ?>
    <div class="video-card">
      <div class="video-wrap"><?php echo embed_video($item['video_url'] ?? ''); ?></div>
      <div class="video-body">
        <h3><a href="documentaries.php" class="text-dark text-decoration-none"><span class="ar-text"><?php echo htmlspecialchars($item['title_ar']); ?></span><span class="fr-text"><?php echo htmlspecialchars($item['title_fr']); ?></span></a></h3>
        <?php if (!empty($item['excerpt_ar']) || !empty($item['excerpt_fr'])): ?><p><span class="ar-text"><?php echo htmlspecialchars($item['excerpt_ar'] ?? ''); ?></span><span class="fr-text"><?php echo htmlspecialchars($item['excerpt_fr'] ?? ''); ?></span></p><?php endif; ?>
        <?php if (!empty($item['published_at'])): ?><div class="card-meta" style="font-size:11px;color:#9ca3af;"><?php echo date('d/m/Y', strtotime($item['published_at'])); ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
