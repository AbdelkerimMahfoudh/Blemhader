<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$admin_page_title = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<p class="text-muted">Welcome. Manage content from the sidebar.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
