<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

$conn = db();
$events = (int)$conn->query('SELECT COUNT(*) AS c FROM events')->fetch_assoc()['c'];
$users = (int)$conn->query('SELECT COUNT(*) AS c FROM users')->fetch_assoc()['c'];
$bookings = (int)$conn->query('SELECT COUNT(*) AS c FROM bookings')->fetch_assoc()['c'];
$attended = (int)$conn->query('SELECT COUNT(*) AS c FROM attendance WHERE attended = 1')->fetch_assoc()['c'];

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Admin Dashboard</h1>

<div class="grid">
	<div class="stat card">
		<div class="stat-label">Events</div>
		<div class="stat-value"><?= e((string)$events) ?></div>
		<a class="link" href="<?= e(url('/admin/manage_events.php')) ?>">Manage events</a>
	</div>
	<div class="stat card">
		<div class="stat-label">Users</div>
		<div class="stat-value"><?= e((string)$users) ?></div>
		<a class="link" href="<?= e(url('/admin/view_bookings.php')) ?>">View bookings</a>
	</div>
	<div class="stat card">
		<div class="stat-label">Bookings</div>
		<div class="stat-value"><?= e((string)$bookings) ?></div>
		<a class="link" href="<?= e(url('/admin/view_bookings.php')) ?>">All bookings</a>
	</div>
	<div class="stat card">
		<div class="stat-label">Attendance Marked</div>
		<div class="stat-value"><?= e((string)$attended) ?></div>
		<a class="link" href="<?= e(url('/admin/mark_attendance.php')) ?>">Mark attendance</a>
	</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

