<?php
/**
 * Blemhader - 404 Not Found page (animated, bilingual AR/FR, theme-aware).
 * Wired via .htaccess:  ErrorDocument 404 /404.php
 */
require_once __DIR__ . '/includes/config.php';

http_response_code(404);

$lang     = (isset($current_lang) && $current_lang === 'fr') ? 'fr' : 'ar';
$body_dir = isset($body_dir) ? $body_dir : ($lang === 'fr' ? 'ltr' : 'rtl');
$body_cls = isset($body_class) ? $body_class : '';
$home     = 'index.php?lang=' . $lang;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo htmlspecialchars($body_dir); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <base href="/">
  <title><?php echo $lang === 'fr' ? 'Page introuvable' : 'الصفحة غير موجودة'; ?> | Blemhader</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Source+Serif+4:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    .e404-wrap{
      min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center;
      text-align:center; padding:40px 20px; background:var(--bg); color:var(--text);
      font-family:'Cairo',sans-serif; position:relative; overflow:hidden;
    }
    body.fr .e404-wrap{ font-family:'Source Serif 4',serif; }

    /* soft animated background glow */
    .e404-wrap::before{
      content:""; position:absolute; inset:0; pointer-events:none;
      background:radial-gradient(circle at 50% 38%, rgba(13,92,99,.18), transparent 55%);
      animation:e404-breathe 6s ease-in-out infinite;
    }
    @keyframes e404-breathe{ 0%,100%{opacity:.5;} 50%{opacity:1;} }

    .e404-brand{ margin-bottom:28px; z-index:1; }
    .e404-brand img{ height:54px; width:auto; opacity:.95; }

    /* the big 404 */
    .e404-code{
      position:relative; z-index:1; display:flex; align-items:center; justify-content:center;
      gap:6px; font-weight:900; line-height:1;
      font-size:clamp(90px,22vw,190px); color:var(--text);
      letter-spacing:2px;
    }
    .e404-code .digit{ animation:e404-float 3.2s ease-in-out infinite; }
    .e404-code .digit:nth-child(1){ animation-delay:0s; }
    .e404-code .digit:nth-child(3){ animation-delay:.4s; }
    @keyframes e404-float{ 0%,100%{transform:translateY(0);} 50%{transform:translateY(-14px);} }

    /* the middle "0" = animated broadcast / radar pulse */
    .e404-signal{
      position:relative; width:clamp(90px,22vw,190px); height:clamp(90px,22vw,190px);
      display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto;
    }
    .e404-signal .ring{
      position:absolute; inset:0; margin:auto; border-radius:50%;
      border:6px solid var(--accent); opacity:0;
      animation:e404-pulse 2.4s cubic-bezier(.21,.61,.35,1) infinite;
    }
    .e404-signal .ring:nth-child(2){ animation-delay:.8s; border-color:var(--gold); }
    .e404-signal .ring:nth-child(3){ animation-delay:1.6s; }
    @keyframes e404-pulse{
      0%{ transform:scale(.25); opacity:0; }
      18%{ opacity:.9; }
      100%{ transform:scale(1); opacity:0; }
    }
    .e404-signal .dot{
      position:relative; width:20%; height:20%; border-radius:50%;
      background:var(--accent); box-shadow:0 0 24px var(--accent);
      animation:e404-blink 1.6s ease-in-out infinite;
    }
    @keyframes e404-blink{ 0%,100%{transform:scale(1);opacity:1;} 50%{transform:scale(.6);opacity:.55;} }

    .e404-title{
      z-index:1; margin:18px 0 6px; font-size:clamp(22px,4vw,30px); font-weight:700; color:var(--text);
    }
    .e404-text{ z-index:1; max-width:520px; margin:0 auto 26px; font-size:16px; color:var(--text-muted); line-height:1.8; }

    .e404-actions{ z-index:1; display:flex; flex-wrap:wrap; gap:12px; justify-content:center; }
    .e404-btn{
      display:inline-flex; align-items:center; gap:8px; padding:12px 26px; border-radius:40px;
      font-weight:700; font-size:15px; text-decoration:none; transition:transform .18s ease, box-shadow .18s ease;
    }
    .e404-btn-primary{ background:var(--accent); color:#fff; box-shadow:0 6px 18px rgba(13,92,99,.35); }
    .e404-btn-primary:hover{ transform:translateY(-3px); box-shadow:0 10px 24px rgba(13,92,99,.45); color:#fff; }
    .e404-btn-ghost{ background:transparent; color:var(--text); border:1.5px solid var(--border); }
    .e404-btn-ghost:hover{ transform:translateY(-3px); border-color:var(--accent); color:var(--accent); }

    .e404-links{ z-index:1; margin-top:30px; display:flex; flex-wrap:wrap; gap:8px 20px; justify-content:center; }
    .e404-links a{ color:var(--text-muted); text-decoration:none; font-size:14px; font-weight:600; transition:color .15s; }
    .e404-links a:hover{ color:var(--accent); }

    @media (prefers-reduced-motion: reduce){
      .e404-code .digit, .e404-signal .ring, .e404-signal .dot, .e404-wrap::before{ animation:none !important; }
      .e404-signal .ring:first-child{ opacity:.5; transform:scale(1); }
    }
  </style>
</head>
<body lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo htmlspecialchars($body_dir); ?>"<?php echo $body_cls ? ' class="' . htmlspecialchars($body_cls) . '"' : ''; ?>>

  <main class="e404-wrap">
    <a class="e404-brand" href="<?php echo htmlspecialchars($home); ?>">
      <img src="assets/images/name.png" alt="Blemhader">
    </a>

    <div class="e404-code" role="img" aria-label="404">
      <span class="digit">4</span>
      <span class="e404-signal" aria-hidden="true">
        <span class="ring"></span>
        <span class="ring"></span>
        <span class="ring"></span>
        <span class="dot"></span>
      </span>
      <span class="digit">4</span>
    </div>

    <h1 class="e404-title">
      <span class="ar-text">انقطع البث... الصفحة غير موجودة</span>
      <span class="fr-text">Signal perdu... page introuvable</span>
    </h1>

    <p class="e404-text">
      <span class="ar-text">يبدو أن الصفحة التي تبحث عنها قد حُذفت أو غُيّر عنوانها أو لم تكن موجودة أصلاً. عُد إلى البث المباشر للأحداث.</span>
      <span class="fr-text">La page que vous cherchez a peut-être été supprimée, renommée ou n'a jamais existé. Revenez au direct de l'actualité.</span>
    </p>

    <div class="e404-actions">
      <a class="e404-btn e404-btn-primary" href="<?php echo htmlspecialchars($home); ?>">
        <span class="ar-text">العودة إلى الرئيسية</span>
        <span class="fr-text">Retour à l'accueil</span>
      </a>
      <a class="e404-btn e404-btn-ghost" href="news.php?lang=<?php echo $lang; ?>">
        <span class="ar-text">آخر الأخبار</span>
        <span class="fr-text">Dernières actualités</span>
      </a>
    </div>

    <nav class="e404-links" aria-label="sections">
      <a href="live.php?lang=<?php echo $lang; ?>"><span class="ar-text">البثوث المباشرة</span><span class="fr-text">Les directs</span></a>
      <a href="meetings.php?lang=<?php echo $lang; ?>"><span class="ar-text">لقاءات</span><span class="fr-text">Rencontres</span></a>
      <a href="economy.php?lang=<?php echo $lang; ?>"><span class="ar-text">اقتصاد</span><span class="fr-text">Économie</span></a>
      <a href="documentaries.php?lang=<?php echo $lang; ?>"><span class="ar-text">وثائقيات</span><span class="fr-text">Documentaires</span></a>
    </nav>
  </main>

</body>
</html>
