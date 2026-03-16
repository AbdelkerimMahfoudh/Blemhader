<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if (!is_admin_role()) {
    header('Location: index.php');
    exit;
}

$admin_section = 'users';
$admin_page_title = 'Users';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$pdo) {
        $error = 'Database not available.';
    } elseif (isset($_POST['add_user'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'editor';
        if (!in_array($role, ['admin', 'editor'], true)) $role = 'editor';
        if (strlen($username) < 2) {
            $error = 'Username must be at least 2 characters.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
                $stmt->execute([$username, $hash, $role]);
                $message = 'User added.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) $error = 'Username already exists.';
                else $error = 'Could not add user.';
            }
        }
    } elseif (isset($_POST['delete']) && ($del_id = (int)$_POST['delete']) > 0) {
        if ($del_id === ($_SESSION['admin_user_id'] ?? 0)) {
            $error = 'You cannot delete your own account.';
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$del_id]);
            $message = 'User removed.';
        }
    }
}

$users = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY username');
    $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="row">
  <div class="col-md-5 mb-4">
    <div class="card">
      <div class="card-header"><strong>Add user</strong></div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required minlength="2">
          </div>
          <div class="mb-2">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required minlength="8">
          </div>
          <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role">
              <option value="editor">Editor</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <button type="submit" name="add_user" class="btn btn-primary">Add user</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><strong>Existing users</strong></div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <thead><tr><th>Username</th><th>Role</th><th>Created</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo htmlspecialchars($u['username']); ?></td>
              <td><span class="badge bg-<?php echo $u['role'] === 'admin' ? 'danger' : 'secondary'; ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
              <td><?php echo htmlspecialchars($u['created_at']); ?></td>
              <td>
                <?php if ((int)$u['id'] !== (int)($_SESSION['admin_user_id'] ?? 0)): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Remove this user?');">
                  <input type="hidden" name="delete" value="<?php echo (int)$u['id']; ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                </form>
                <?php else: ?><span class="text-muted">(you)</span><?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<p class="text-muted small mt-3">Only admins can access this page. Editors can log in and manage content but cannot add or remove users.</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
