<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/helpers.php';

// Only superadmins may manage accounts.
if (!is_superadmin()) {
    header('Location: index.php');
    exit;
}

$admin_section = 'users';
$admin_page_title = 'Users';

$message = '';
$error = '';
$is_ajax = !empty($_POST['ajax']);

function superadmin_count($pdo) {
    return (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "superadmin"')->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$pdo) {
        $error = 'Database not available.';
    } elseif (isset($_POST['add_user'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $display_name = trim($_POST['display_name'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['superadmin', 'admin', 'editor'], true) ? $_POST['role'] : 'editor';
        if (strlen($username) < 2) {
            $error = 'Username must be at least 2 characters.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $avatar = handle_image_upload('avatar_file');
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $pdo->prepare('INSERT INTO users (username, password_hash, role, display_name, avatar_url) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$username, $hash, $role, $display_name ?: null, $avatar ?: null]);
                $message = 'Account created.';
            } catch (PDOException $e) {
                $error = ($e->getCode() == 23000) ? 'Username already exists.' : 'Could not create account.';
            }
        }
    } elseif (isset($_POST['edit_user']) && ($eid = (int)$_POST['edit_user']) > 0) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';                 // blank = keep
        $display_name = trim($_POST['display_name'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['superadmin', 'admin', 'editor'], true) ? $_POST['role'] : 'editor';

        $cur = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
        $cur->execute([$eid]);
        $target = $cur->fetch();

        if (!$target) {
            $error = 'Account not found.';
        } elseif (strlen($username) < 2) {
            $error = 'Username must be at least 2 characters.';
        } elseif ($password !== '' && strlen($password) < 8) {
            $error = 'Password must be at least 8 characters (or leave blank to keep).';
        } elseif ($target['role'] === 'superadmin' && $role !== 'superadmin' && superadmin_count($pdo) <= 1) {
            $error = 'Cannot demote the last superadmin.';
        } else {
            $avatar = handle_image_upload('avatar_file'); // '' if none
            try {
                $sql = 'UPDATE users SET username = ?, role = ?, display_name = ?';
                $params = [$username, $role, $display_name ?: null];
                if ($password !== '') { $sql .= ', password_hash = ?'; $params[] = password_hash($password, PASSWORD_DEFAULT); }
                if ($avatar !== '')   { $sql .= ', avatar_url = ?';    $params[] = $avatar; }
                $sql .= ' WHERE id = ?'; $params[] = $eid;
                $pdo->prepare($sql)->execute($params);
                $message = 'Account updated.';
                // Keep the current session in sync if a superadmin edited their own account.
                if ($eid === (int)($_SESSION['admin_user_id'] ?? 0)) {
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_role'] = $role;
                    $_SESSION['admin_display_name'] = $display_name;
                    if ($avatar !== '') $_SESSION['admin_avatar'] = $avatar;
                }
            } catch (PDOException $e) {
                $error = ($e->getCode() == 23000) ? 'Username already exists.' : 'Could not update account.';
            }
        }
    } elseif (isset($_POST['delete']) && ($del_id = (int)$_POST['delete']) > 0) {
        $cur = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ?');
        $cur->execute([$del_id]);
        $target = $cur->fetch();
        if (!$target) {
            $error = 'Account not found.';
        } elseif ($target['username'] === 'blemhader_bot') {
            $error = 'The system account cannot be deleted.';
        } elseif ($target['role'] === 'superadmin' && superadmin_count($pdo) <= 1) {
            $error = 'Cannot delete the last superadmin (the site would have no one to manage accounts).';
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$del_id]);
            // If a superadmin deleted their own account, end their session.
            if ($del_id === (int)($_SESSION['admin_user_id'] ?? 0)) {
                admin_logout();
                header('Location: login.php');
                exit;
            }
            $message = 'Account deleted.';
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($error ? ['ok' => false, 'error' => $error] : ['ok' => true, 'message' => $message]);
        exit;
    }
}

$users = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT id, username, role, display_name, avatar_url, created_at FROM users ORDER BY FIELD(role, "superadmin","admin","editor"), username');
    $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$role_badge = ['superadmin' => 'dark', 'admin' => 'danger', 'editor' => 'secondary'];

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0">Accounts</h6>
  <button type="button" class="btn btn-primary" id="btn-create-account">+ Create account</button>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th style="width:64px;"></th><th>Display name</th><th>Username</th><th>Role</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <?php if (!empty($u['avatar_url'])): ?>
            <img src="../<?php echo htmlspecialchars($u['avatar_url']); ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
            <?php else: ?>
            <span class="d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;border-radius:50%;background:#e2e6ea;color:#6b7280;font-weight:700;"><?php echo htmlspecialchars(strtoupper(substr($u['display_name'] ?: $u['username'], 0, 1))); ?></span>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($u['display_name'] ?: '—'); ?></td>
          <td><?php echo htmlspecialchars($u['username']); ?><?php echo (int)$u['id'] === (int)($_SESSION['admin_user_id'] ?? 0) ? ' <span class="text-muted small">(you)</span>' : ''; ?></td>
          <td><span class="badge bg-<?php echo $role_badge[$u['role']] ?? 'secondary'; ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
          <td class="text-muted small"><?php echo htmlspecialchars($u['created_at']); ?></td>
          <td class="text-end">
            <?php if ($u['username'] === 'blemhader_bot'): ?>
              <span class="text-muted small">system</span>
            <?php else: ?>
              <button type="button" class="btn btn-outline-primary btn-sm btn-edit-account"
                data-id="<?php echo (int)$u['id']; ?>"
                data-username="<?php echo htmlspecialchars($u['username']); ?>"
                data-display="<?php echo htmlspecialchars($u['display_name'] ?? ''); ?>"
                data-role="<?php echo htmlspecialchars($u['role']); ?>"
                data-avatar="<?php echo $u['avatar_url'] ? '../' . htmlspecialchars($u['avatar_url']) : ''; ?>">Edit</button>
              <form method="post" class="d-inline" onsubmit="return confirm('<?php echo (int)$u['id'] === (int)($_SESSION['admin_user_id'] ?? 0) ? 'Delete YOUR OWN account? You will be logged out.' : 'Delete this account?'; ?>');">
                <input type="hidden" name="delete" value="<?php echo (int)$u['id']; ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<p class="text-muted small mt-3">Superadmins can create, edit, and delete any account (including their own). The last superadmin and the system account are protected from deletion. Admins and editors edit only their own account (My account).</p>

<script>
function accountModal(opts) {
  // opts: {title, id (null=create), username, display, role, avatar, requirePassword}
  var avatarSrc = opts.avatar || 'https://www.gravatar.com/avatar/?d=mp&s=96';
  Swal.fire({
    title: opts.title,
    html:
      '<div style="text-align:center;margin-bottom:12px;">' +
        '<img id="swal-avatar-preview" src="' + avatarSrc + '" style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:2px solid #e2e6ea;cursor:pointer;">' +
        '<div class="text-muted small mt-1">Click to ' + (opts.avatar ? 'change' : 'add') + ' the profile photo</div>' +
        '<input type="file" id="swal-avatar" accept="image/*" style="display:none;">' +
      '</div>' +
      '<input id="swal-display" class="swal2-input" placeholder="Display name (shown publicly)" value="' + (opts.display || '').replace(/"/g, '&quot;') + '">' +
      '<input id="swal-username" class="swal2-input" placeholder="Username (login)" value="' + (opts.username || '').replace(/"/g, '&quot;') + '">' +
      '<input id="swal-password" type="password" class="swal2-input" placeholder="' + (opts.requirePassword ? 'Password (min 8 chars)' : 'New password (leave blank to keep)') + '">' +
      '<select id="swal-role" class="swal2-select" style="width:80%;">' +
        '<option value="admin">Admin</option>' +
        '<option value="editor">Editor</option>' +
        '<option value="superadmin">Superadmin</option>' +
      '</select>',
    showCancelButton: true,
    confirmButtonText: opts.id ? 'Save' : 'Create',
    confirmButtonColor: '#0D5C63',
    focusConfirm: false,
    didOpen: function () {
      if (opts.role) document.getElementById('swal-role').value = opts.role;
      var img = document.getElementById('swal-avatar-preview');
      var file = document.getElementById('swal-avatar');
      img.addEventListener('click', function () { file.click(); });
      file.addEventListener('change', function () { if (file.files && file.files[0]) img.src = URL.createObjectURL(file.files[0]); });
    },
    preConfirm: function () {
      var username = document.getElementById('swal-username').value.trim();
      var password = document.getElementById('swal-password').value;
      if (username.length < 2) { Swal.showValidationMessage('Username must be at least 2 characters'); return false; }
      if ((opts.requirePassword || password.length > 0) && password.length < 8) { Swal.showValidationMessage('Password must be at least 8 characters'); return false; }
      var fd = new FormData();
      fd.append('ajax', '1');
      if (opts.id) fd.append('edit_user', opts.id); else fd.append('add_user', '1');
      fd.append('display_name', document.getElementById('swal-display').value.trim());
      fd.append('username', username);
      if (password.length) fd.append('password', password);
      fd.append('role', document.getElementById('swal-role').value);
      var f = document.getElementById('swal-avatar');
      if (f.files && f.files[0]) fd.append('avatar_file', f.files[0]);
      return fetch('users.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (!d.ok) { Swal.showValidationMessage(d.error || 'Failed'); return false; } return d; })
        .catch(function () { Swal.showValidationMessage('Network error'); return false; });
    }
  }).then(function (res) {
    if (res.isConfirmed) {
      Swal.fire({ icon: 'success', title: opts.id ? 'Saved' : 'Account created', timer: 1000, showConfirmButton: false })
        .then(function () { location.reload(); });
    }
  });
}

document.getElementById('btn-create-account').addEventListener('click', function () {
  accountModal({ title: 'Create account', id: null, requirePassword: true });
});
document.querySelectorAll('.btn-edit-account').forEach(function (b) {
  b.addEventListener('click', function () {
    accountModal({
      title: 'Edit account', id: b.dataset.id, username: b.dataset.username,
      display: b.dataset.display, role: b.dataset.role, avatar: b.dataset.avatar, requirePassword: false
    });
  });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>