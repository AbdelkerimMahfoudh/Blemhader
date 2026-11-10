<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin(); // any logged-in user (superadmin / admin / editor)
require_once __DIR__ . '/../includes/helpers.php';

$admin_section = 'profile';
$admin_page_title = 'My account';

$message = '';
$error = '';
$uid = (int)($_SESSION['admin_user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && $uid) {
    $username = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';        // blank = keep
    $password2 = $_POST['password2'] ?? '';

    if (strlen($username) < 2) {
        $error = 'Username must be at least 2 characters.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'Password must be at least 8 characters (or leave blank to keep).';
    } elseif ($password !== '' && $password !== $password2) {
        $error = 'The two passwords do not match.';
    } else {
        $avatar = handle_image_upload('avatar_file'); // '' if none/invalid
        try {
            $sql = 'UPDATE users SET username = ?, display_name = ?';
            $params = [$username, $display_name ?: null];
            if ($password !== '') { $sql .= ', password_hash = ?'; $params[] = password_hash($password, PASSWORD_DEFAULT); }
            if ($avatar !== '')   { $sql .= ', avatar_url = ?';    $params[] = $avatar; }
            $sql .= ' WHERE id = ?'; $params[] = $uid;
            $pdo->prepare($sql)->execute($params);
            // Keep session in sync (note: role is intentionally NOT editable here).
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_display_name'] = $display_name;
            if ($avatar !== '') $_SESSION['admin_avatar'] = $avatar;
            $message = 'Your account was updated.';
        } catch (PDOException $e) {
            $error = ($e->getCode() == 23000) ? 'That username is already taken.' : 'Could not update your account.';
        }
    }
}

$me = null;
if ($pdo && $uid) {
    $st = $pdo->prepare('SELECT username, role, display_name, avatar_url FROM users WHERE id = ?');
    $st->execute([$uid]);
    $me = $st->fetch();
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="card" style="max-width:560px;">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <div class="text-center mb-3">
        <img id="avatar-preview" src="<?php echo !empty($me['avatar_url']) ? '../' . htmlspecialchars($me['avatar_url']) : 'https://www.gravatar.com/avatar/?d=mp&s=120'; ?>"
             style="width:104px;height:104px;border-radius:50%;object-fit:cover;border:2px solid #e2e6ea;cursor:pointer;" title="Click to change photo">
        <div class="text-muted small mt-1">Click the photo to change it</div>
        <input type="file" id="avatar_file" name="avatar_file" accept="image/*" style="display:none;">
        <div class="mt-2"><span class="badge bg-secondary"><?php echo htmlspecialchars($me['role'] ?? ''); ?></span> <span class="text-muted small">(role can only be changed by a superadmin)</span></div>
      </div>

      <div class="mb-3">
        <label class="form-label">Display name</label>
        <input type="text" name="display_name" class="form-control" value="<?php echo htmlspecialchars($me['display_name'] ?? ''); ?>" placeholder="Shown publicly when 'show publisher' is on">
      </div>
      <div class="mb-3">
        <label class="form-label">Username (login)</label>
        <input type="text" name="username" class="form-control" required minlength="2" value="<?php echo htmlspecialchars($me['username'] ?? ''); ?>">
      </div>
      <hr>
      <p class="text-muted small mb-2">Leave the password fields blank to keep your current password.</p>
      <div class="row g-2 mb-3">
        <div class="col-md-6"><input type="password" name="password" class="form-control" placeholder="New password (min 8)"></div>
        <div class="col-md-6"><input type="password" name="password2" class="form-control" placeholder="Confirm new password"></div>
      </div>
      <button type="submit" class="btn btn-primary">Save changes</button>
    </form>
  </div>
</div>

<script>
(function () {
  var img = document.getElementById('avatar-preview');
  var file = document.getElementById('avatar_file');
  img.addEventListener('click', function () { file.click(); });
  file.addEventListener('change', function () { if (file.files && file.files[0]) img.src = URL.createObjectURL(file.files[0]); });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
