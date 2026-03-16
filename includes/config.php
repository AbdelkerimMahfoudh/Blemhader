<?php
/**
 * Blemhader - Site configuration and language handling
 * Deployment: set DB_HOST, DB_NAME, DB_USER, DB_PASS (and optionally ENV) via environment.
 * See config.example.php for required variables.
 */

if (!defined('SITE_NAME_AR')) {
    define('SITE_NAME_AR', getenv('SITE_NAME_AR') ?: 'Blemhader');
}
if (!defined('SITE_NAME_FR')) {
    define('SITE_NAME_FR', getenv('SITE_NAME_FR') ?: 'Blemhader');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('BASE_URL') ?: 'index.php');
}

// Database: use environment in production; fallback to local defaults for development
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'blue_dev_corp');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: '');
}

// Environment: set ENV=production on deploy to hide DB errors and enable production behavior
if (!defined('ENV')) {
    define('ENV', getenv('ENV') ?: 'development');
}
if (!defined('DEBUG')) {
    define('DEBUG', (ENV !== 'production' && (getenv('DEBUG') === '1' || getenv('DEBUG') === 'true')));
}
if (ENV === 'production') {
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
}

define('UPLOADS_DIR', __DIR__ . '/../uploads/videos');
define('UPLOADS_IMAGES_DIR', __DIR__ . '/../uploads/images');
define('MAX_VIDEO_SIZE', 100 * 1024 * 1024); // 100 MB
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);   // 5 MB for thumbnails

require_once __DIR__ . '/db.php';

$default_lang = 'ar';
$current_lang = $default_lang;

if (!empty($_GET['lang']) && in_array($_GET['lang'], ['ar', 'fr'], true)) {
    $current_lang = $_GET['lang'];
    setcookie('blemhader_lang', $current_lang, time() + (86400 * 365), '/');
} elseif (!empty($_COOKIE['blemhader_lang']) && $_COOKIE['blemhader_lang'] === 'fr') {
    $current_lang = 'fr';
} elseif (!empty($_COOKIE['blemhader_lang']) && $_COOKIE['blemhader_lang'] === 'ar') {
    $current_lang = 'ar';
}

$body_lang = $current_lang === 'fr' ? 'fr' : 'ar';
$body_dir = $current_lang === 'fr' ? 'ltr' : 'rtl';
$body_class = $current_lang === 'fr' ? 'fr' : '';

$theme = 'light';
if (!empty($_GET['theme']) && in_array($_GET['theme'], ['light', 'dark'], true)) {
    $theme = $_GET['theme'];
    setcookie('blemhader_theme', $theme, time() + (86400 * 365), '/');
} elseif (!empty($_COOKIE['blemhader_theme']) && $_COOKIE['blemhader_theme'] === 'dark') {
    $theme = 'dark';
}
$body_class .= ($body_class ? ' ' : '') . ($theme === 'dark' ? 'dark-mode' : '');

$page_title_ar = SITE_NAME_AR;
$page_title_fr = SITE_NAME_FR;

$now = new DateTime('now', new DateTimeZone('Africa/Nouakchott'));
$day_num = (int) $now->format('w');
$day_en = $now->format('l');
$month_num = (int) $now->format('n');
$day_of_month = $now->format('j');
$year = $now->format('Y');

$ar_days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$ar_months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
$fr_months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$fr_days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

$date_ar = $ar_days[$day_num] . '، ' . $day_of_month . ' ' . $ar_months[$month_num - 1] . ' ' . $year;
$date_fr = $fr_days[$day_num] . ', ' . $day_of_month . ' ' . $fr_months[$month_num - 1] . ' ' . $year;

$nav_pages = [
    'home'         => ['file' => 'index.php',      'ar' => 'الرئيسية',           'fr' => 'Accueil'],
    'live'         => ['file' => 'live.php',      'ar' => 'البثوث المباشرة',    'fr' => 'Les directs'],
    'meetings'     => ['file' => 'meetings.php',  'ar' => 'لقاءات',             'fr' => 'Rencontres'],
    'news'         => ['file' => 'news.php',      'ar' => 'أخبار',              'fr' => 'Actualités'],
    'economy'      => ['file' => 'economy.php',   'ar' => 'اقتصاد',             'fr' => 'Économie'],
    'documentaries'=> ['file' => 'documentaries.php', 'ar' => 'وثائقيات',       'fr' => 'Documentaires'],
];

$script_name = isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF'], '.php') : 'index';
$current_page = ($script_name === 'index' || $script_name === '') ? 'home' : $script_name;
if (!isset($nav_pages[$current_page])) {
    $current_page = 'home';
}
