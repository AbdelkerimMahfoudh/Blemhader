/**
 * Blemhader - Language toggle, theme toggle, live date
 */

function setTheme(theme) {
  var body = document.body;
  if (theme === 'dark') {
    body.classList.add('dark-mode');
    document.cookie = 'blemhader_theme=dark;path=/;max-age=' + (86400 * 365);
  } else {
    body.classList.remove('dark-mode');
    document.cookie = 'blemhader_theme=light;path=/;max-age=' + (86400 * 365);
  }
}

// Theme toggle
(function () {
  var btn = document.getElementById('theme-toggle');
  if (!btn) return;
  btn.addEventListener('click', function () {
    var isDark = document.body.classList.contains('dark-mode');
    setTheme(isDark ? 'light' : 'dark');
  });
})();

function setLang(lang) {
  var body = document.body;
  var btnAr = document.getElementById('btn-ar');
  var btnFr = document.getElementById('btn-fr');

  if (lang === 'fr') {
    body.classList.add('fr');
    body.setAttribute('lang', 'fr');
    body.setAttribute('dir', 'ltr');
    if (btnFr) btnFr.classList.add('active');
    if (btnAr) btnAr.classList.remove('active');
    document.title = 'Blemhader';
  } else {
    body.classList.remove('fr');
    body.setAttribute('lang', 'ar');
    body.setAttribute('dir', 'rtl');
    if (btnAr) btnAr.classList.add('active');
    if (btnFr) btnFr.classList.remove('active');
    document.title = 'Blemhader';
  }

  // Persist choice for next page load
  document.cookie = 'blemhader_lang=' + lang + ';path=/;max-age=' + (86400 * 365);
  var newUrl = window.location.pathname + '?lang=' + lang;
  if (typeof history !== 'undefined' && history.replaceState) {
    history.replaceState(null, '', newUrl);
  }

  // Close mobile side menu when switching language to avoid visual jump
  var overlay = document.getElementById('nav-overlay');
  var menu = document.getElementById('nav-side-menu');
  var toggle = document.getElementById('nav-toggle');
  if (overlay) overlay.classList.remove('is-open');
  if (menu) menu.classList.remove('is-open');
  if (toggle) {
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
  }
  document.body.style.overflow = '';

  // Update nav/footer links so next navigation keeps the selected language (exclude lang-bar buttons)
  document.querySelectorAll('.nav-link, .nav-side-link, .footer-col a, .header-brand-name').forEach(function (a) {
    var href = a.getAttribute('href');
    if (!href || !href.includes('lang=')) return;
    var newHref = href.replace(/([?&])lang=(?:ar|fr)\b/, '$1lang=' + lang);
    if (newHref === href) {
      newHref = href + (href.indexOf('?') >= 0 ? '&' : '?') + 'lang=' + lang;
    }
    a.setAttribute('href', newHref);
  });
}

// Language buttons: optional client-side switch (no reload)
(function () {
  var btnAr = document.getElementById('btn-ar');
  var btnFr = document.getElementById('btn-fr');
  if (btnAr) {
    btnAr.addEventListener('click', function (e) {
      e.preventDefault();
      setLang('ar');
    });
  }
  if (btnFr) {
    btnFr.addEventListener('click', function (e) {
      e.preventDefault();
      setLang('fr');
    });
  }
})();

// Live date in header (AR/FR)
(function () {
  var badge = document.getElementById('date-badge');
  if (!badge) return;

  var arDays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
  var arMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
  var frDays = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
  var frMonths = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

  function formatDate() {
    var d = new Date();
    var dayNum = d.getDay();
    var day = d.getDate();
    var month = d.getMonth();
    var year = d.getFullYear();
    var ar = arDays[dayNum] + '، ' + day + ' ' + arMonths[month] + ' ' + year;
    var fr = frDays[dayNum] + ', ' + day + ' ' + frMonths[month] + ' ' + year;
    var arSpan = badge.querySelector('.ar-text');
    var frSpan = badge.querySelector('.fr-text');
    if (arSpan) arSpan.textContent = ar;
    if (frSpan) frSpan.textContent = fr;
  }

  formatDate();
})();

// Mobile hamburger menu
(function () {
  var toggle = document.getElementById('nav-toggle');
  var overlay = document.getElementById('nav-overlay');
  var menu = document.getElementById('nav-side-menu');

  if (!toggle || !overlay || !menu) return;

  function openMenu() {
    overlay.classList.add('is-open');
    menu.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close menu');
    document.body.style.overflow = 'hidden';
  }

  function closeMenu() {
    overlay.classList.remove('is-open');
    menu.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
    document.body.style.overflow = '';
  }

  function toggleMenu() {
    if (menu.classList.contains('is-open')) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  toggle.addEventListener('click', function () {
    toggleMenu();
  });

  overlay.addEventListener('click', function () {
    closeMenu();
  });

  menu.querySelectorAll('.nav-side-link').forEach(function (link) {
    link.addEventListener('click', function () {
      closeMenu();
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menu.classList.contains('is-open')) {
      closeMenu();
    }
  });
})();

// الرئيسية main card: rotate every 5 seconds through latest from each category
(function () {
  var slides = window.HERO_SLIDES;
  if (!slides || slides.length < 2) return;
  var idx = 0;
  var link = document.getElementById('hero-main-link');
  var media = document.getElementById('hero-media');
  var catAr = document.getElementById('hero-cat-ar');
  var catFr = document.getElementById('hero-cat-fr');
  var titleAr = document.getElementById('hero-title-ar');
  var titleFr = document.getElementById('hero-title-fr');
  var metaAr = document.getElementById('hero-meta-ar');
  var metaFr = document.getElementById('hero-meta-fr');
  if (!link || !media) return;

  function showSlide(i) {
    idx = (i + slides.length) % slides.length;
    var s = slides[idx];
    link.href = s.link;
    media.innerHTML = s.media;
    if (catAr) catAr.textContent = s.catAr;
    if (catFr) catFr.textContent = s.catFr;
    if (titleAr) titleAr.textContent = s.titleAr;
    if (titleFr) titleFr.textContent = s.titleFr;
    if (metaAr) metaAr.textContent = s.metaAr;
    if (metaFr) metaFr.textContent = s.metaFr;
  }

  setInterval(function () { showSlide(idx + 1); }, 5000);
})();
