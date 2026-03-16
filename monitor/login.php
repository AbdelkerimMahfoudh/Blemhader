<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password && admin_login($username, $password)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Blemhader</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(165deg, #e8dfd4 0%, #dfd3c4 50%, #d4c4b0 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-card { max-width: 380px; }
    .login-card .card { border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .login-card .btn-primary { background: #0D5C63; border-color: #0D5C63; }
    .login-card .btn-primary:hover { background: #08454b; border-color: #08454b; }
    .login-card .form-control:focus { border-color: #0D5C63; box-shadow: 0 0 0 0.2rem rgba(13,92,99,0.25); }
  </style>
</head>
<body>
  <div class="login-card w-100 px-3">
    <div class="card">
      <div class="card-body p-4">
        <h4 class="card-title mb-3">Admin Login</h4>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Log in</button>
        </form>
        <p class="text-muted small mt-3 mb-0"><a href="<?php echo htmlspecialchars(BASE_URL); ?>">← Back to site</a></p>
      </div>
    </div>
  </div>
</body>
</html>
