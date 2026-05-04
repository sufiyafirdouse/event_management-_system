<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

ensure_default_admin_exists();

if (admin_id()) {
	redirect('/admin/dashboard.php');
}

$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	if (!csrf_verify($_POST['_csrf'] ?? null)) {
		$error = 'Invalid CSRF token.';
	} else {
		$username = trim((string)($_POST['username'] ?? ''));
		$password = (string)($_POST['password'] ?? '');

		if ($username === '' || $password === '') {
			$error = 'Username and password are required.';
		} else {
			$conn = db();
			$stmt = $conn->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
			$stmt->bind_param('s', $username);
			$stmt->execute();
			$row = $stmt->get_result()->fetch_assoc();

			if ($row && password_verify($password, (string)$row['password_hash'])) {
				$_SESSION['admin_id'] = (int)$row['id'];
				set_flash('success', 'Welcome back.');
				redirect('/admin/dashboard.php');
			}
			$error = 'Invalid credentials.';
		}
	}
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Admin Login</h1>

<?php if ($error): ?>
	<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
	<form method="post" action="">
		<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
		<div class="field">
			<label>Username</label>
			<input class="input" type="text" name="username" autocomplete="username" required>
		</div>
		<div class="field">
			<label>Password</label>
			<input class="input" type="password" name="password" autocomplete="current-password" required>
		</div>
		<button class="btn" type="submit">Login</button>
	</form>
	<p class="muted" style="margin-top:12px;">
		Default admin (auto-created if none exists): <strong>admin / admin123</strong>
	</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

