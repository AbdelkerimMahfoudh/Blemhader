<?php
if (!isset($admin_page_title)) $admin_page_title = 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($admin_page_title); ?> | monitor.blemhader</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f8; }
    .admin-sidebar { background: #1a2332; min-height: 100vh; color: #fff; width: 220px; flex-shrink: 0; }
    .admin-sidebar a { color: #d1d5db; text-decoration: none; display: block; padding: 10px 16px; }
    .admin-sidebar a:hover { color: #fff; background: rgba(255,255,255,0.08); }
    .admin-sidebar a.active { color: #fff; background: rgba(13,92,99,0.5); }
    .admin-header { background: #fff; border-bottom: 1px solid #e2e6ea; }
  </style>
</head>
<body>
  <div class="d-flex">
    <aside class="admin-sidebar">
      <div class="p-3 border-bottom border-secondary">
        <strong>Blemhader Admin</strong>
      </div>
      <nav class="py-2">
        <a href="index.php">Dashboard</a>
        <a href="live.php" class="<?php echo (isset($admin_section) && $admin_section === 'live') ? 'active' : ''; ?>">البثوث المباشرة</a>
        <a href="meetings.php" class="<?php echo (isset($admin_section) && $admin_section === 'meetings') ? 'active' : ''; ?>">لقاءات</a>
        <a href="news.php" class="<?php echo (isset($admin_section) && $admin_section === 'news') ? 'active' : ''; ?>">أخبار</a>
        <a href="economy.php" class="<?php echo (isset($admin_section) && $admin_section === 'economy') ? 'active' : ''; ?>">اقتصاد</a>
        <a href="documentaries.php" class="<?php echo (isset($admin_section) && $admin_section === 'documentaries') ? 'active' : ''; ?>">وثائقيات</a>
        <a href="ticker.php" class="<?php echo (isset($admin_section) && $admin_section === 'ticker') ? 'active' : ''; ?>">عاجل / Ticker</a>
        <a href="hero_slides.php">Hero Slides</a>
        <?php if (is_admin_role()): ?><a href="users.php" class="<?php echo (isset($admin_section) && $admin_section === 'users') ? 'active' : ''; ?>">Users</a><?php endif; ?>
      </nav>
    </aside>
    <main class="flex-grow-1">
      <header class="admin-header px-4 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo htmlspecialchars($admin_page_title); ?></h5>
        <div>
          <span class="text-muted me-3"><?php echo htmlspecialchars(get_admin_username()); ?></span>
          <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
      </header>
      <div class="p-4">
