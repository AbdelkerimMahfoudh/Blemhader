<?php
require_once __DIR__ . '/auth.php';
if (!isset($current_lang)) {
    $current_lang = 'ar';
    $body_lang = 'ar';
    $body_dir = 'rtl';
    $body_class = '';
}
$page_title = $current_lang === 'fr' ? $page_title_fr : $page_title_ar;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($body_lang); ?>" dir="<?php echo htmlspecialchars($body_dir); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Playfair+Display:wght@700&family=Source+Serif+4:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>
<body lang="<?php echo htmlspecialchars($body_lang); ?>" dir="<?php echo htmlspecialchars($body_dir); ?>"<?php if ($body_class) { ?> class="<?php echo htmlspecialchars($body_class); ?>"<?php } ?>>

<!-- LANG BAR + THEME TOGGLE -->
<div class="lang-bar">
  <div class="lang-toggle">
    <a href="<?php echo htmlspecialchars(BASE_URL); ?>?lang=ar" class="lang-btn<?php echo $current_lang === 'ar' ? ' active' : ''; ?>" id="btn-ar">العربية</a>
    <a href="<?php echo htmlspecialchars(BASE_URL); ?>?lang=fr" class="lang-btn<?php echo $current_lang === 'fr' ? ' active' : ''; ?>" id="btn-fr">Français</a>
  </div>
  <button type="button" class="theme-toggle" id="theme-toggle" title="Toggle dark/light mode" aria-label="Toggle theme">
    <span class="theme-icon-light" aria-hidden="true">☀</span>
    <span class="theme-icon-dark" aria-hidden="true">🌙</span>
  </button>
</div>

<?php
$ticker_items = [];
if (!empty($pdo)) {
    try {
        $st = $pdo->prepare("SELECT text_ar, text_fr FROM ticker_items WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY created_at DESC");
        $st->execute();
        $ticker_items = $st ? $st->fetchAll() : [];
    } catch (PDOException $e) {
        $ticker_items = [];
    }
}
?>
<?php if (!empty($ticker_items)): ?>
<!-- BREAKING TICKER -->
<div class="ticker-wrap">
  <div class="ticker-label">
    <span class="ar-text">عاجل</span>
    <span class="fr-text">FLASH</span>
  </div>
  <div class="ticker-track" id="ticker">
    <?php foreach ($ticker_items as $ti): ?>
    <span class="ar-text"><?php echo htmlspecialchars($ti['text_ar']); ?></span>
    <span class="fr-text"><?php echo htmlspecialchars($ti['text_fr']); ?></span>
    <?php endforeach; ?>
    <?php foreach ($ticker_items as $ti): ?>
    <span class="ar-text"><?php echo htmlspecialchars($ti['text_ar']); ?></span>
    <span class="fr-text"><?php echo htmlspecialchars($ti['text_fr']); ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- HEADER -->
<header>
  <div class="header-inner">
    <div class="header-top">
      <a href="<?php echo htmlspecialchars(BASE_URL); ?>?lang=<?php echo $current_lang; ?>" class="header-brand header-brand-name">
        <img src="assets/images/name.png" alt="Blemhader" class="logo-name">
      </a>
      <div class="header-brand header-brand-logo">
        <img src="assets/images/Blemhader.png" alt="" class="logo-img">
      </div>
      <div class="header-meta">
        <div class="date-badge" id="date-badge">
          <span class="ar-text"><?php echo htmlspecialchars($date_ar); ?></span>
          <span class="fr-text"><?php echo htmlspecialchars($date_fr); ?></span>
        </div>
        <div class="social-row">
          <a class="social-icon" href="https://web.facebook.com/moussa.bouhli" target="_blank" rel="noopener noreferrer" title="Facebook">f</a>
          <a class="social-icon" href="https://x.com/208RjnrV4g6cqmh" target="_blank" rel="noopener noreferrer" title="Twitter">𝕏</a>
          <a class="social-icon" href="https://www.instagram.com/moussabouhli/" target="_blank" rel="noopener noreferrer" title="Instagram">◎</a>
          <a class="social-icon" href="https://www.youtube.com/@moussabouhli" target="_blank" rel="noopener noreferrer" title="YouTube">▶</a>
        </div>
      </div>
    </div>
  </div>

  <nav class="nav-desktop">
    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false">
      <span class="nav-toggle-line"></span>
      <span class="nav-toggle-line"></span>
      <span class="nav-toggle-line"></span>
    </button>
    <div class="nav-inner">
      <?php
      $lang_param = '?lang=' . $current_lang;
      foreach ($nav_pages as $key => $page) {
          $href = $page['file'] . $lang_param;
          $active = ($current_page === $key) ? ' active' : '';
      ?>
      <a class="nav-link<?php echo $active; ?>" href="<?php echo htmlspecialchars($href); ?>">
        <span class="ar-text"><?php echo htmlspecialchars($page['ar']); ?></span>
        <span class="fr-text"><?php echo htmlspecialchars($page['fr']); ?></span>
      </a>
      <?php } ?>
    </div>
  </nav>

  <div class="nav-overlay" id="nav-overlay" aria-hidden="true"></div>
  <aside class="nav-side-menu" id="nav-side-menu">
    <div class="nav-side-inner">
      <?php
      $lang_param = '?lang=' . $current_lang;
      foreach ($nav_pages as $key => $page) {
          $href = $page['file'] . $lang_param;
          $active = ($current_page === $key) ? ' active' : '';
      ?>
      <a class="nav-side-link<?php echo $active; ?>" href="<?php echo htmlspecialchars($href); ?>">
        <span class="ar-text"><?php echo htmlspecialchars($page['ar']); ?></span>
        <span class="fr-text"><?php echo htmlspecialchars($page['fr']); ?></span>
      </a>
      <?php } ?>
    </div>
  </aside>
</header>
