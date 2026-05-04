<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

$conn = db();
$events = $conn->query('SELECT id, title, start_datetime FROM events ORDER BY start_datetime DESC')->fetch_all(MYSQLI_ASSOC);

$eventId = (int)($_GET['event_id'] ?? 0);
$rows = [];
if ($eventId > 0) {
	$stmt = $conn->prepare(
		"SELECT
			b.id AS booking_id,
			b.booking_ref,
			u.name AS user_name,
			u.email AS user_email,
			COALESCE(a.attended, 0) AS attended
		FROM bookings b
		INNER JOIN users u ON u.id = b.user_id
		LEFT JOIN attendance a ON a.booking_id = b.id
		WHERE b.event_id = ? AND b.status = 'CONFIRMED'
		ORDER BY u.name ASC"
	);
	$stmt->bind_param('i', $eventId);
	$stmt->execute();
	$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Mark Attendance</h1>

<div class="card">
	<form method="get" action="">
		<div class="field">
			<label>Select Event</label>
			<select class="input" name="event_id" required>
				<option value="">-- choose --</option>
				<?php foreach ($events as $ev): ?>
					<option value="<?= e((string)$ev['id']) ?>" <?= ($eventId === (int)$ev['id']) ? 'selected' : '' ?>>
						<?= e($ev['title']) ?> (<?= e($ev['start_datetime']) ?>)
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<button class="btn" type="submit">Load</button>
	</form>
</div>

<?php if ($eventId > 0): ?>
	<div class="card" style="margin-top:16px;">
		<table class="table">
			<thead>
			<tr>
				<th>Ref</th>
				<th>User</th>
				<th>Email</th>
				<th>Status</th>
				<th>Action</th>
			</tr>
			</thead>
			<tbody>
			<?php if (!$rows): ?>
				<tr><td colspan="5" class="muted">No confirmed bookings for this event.</td></tr>
			<?php else: ?>
				<?php foreach ($rows as $r): ?>
					<tr>
						<td><?= e($r['booking_ref']) ?></td>
						<td><?= e($r['user_name']) ?></td>
						<td><?= e($r['user_email']) ?></td>
						<td><?= ((int)$r['attended'] === 1) ? 'Attended' : 'Not attended' ?></td>
						<td>
							<form method="post" action="<?= e(url('/attendance/update_status.php')) ?>" style="display:inline;">
								<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
								<input type="hidden" name="booking_id" value="<?= e((string)$r['booking_id']) ?>">
								<input type="hidden" name="event_id" value="<?= e((string)$eventId) ?>">
								<input type="hidden" name="attended" value="<?= ((int)$r['attended'] === 1) ? '0' : '1' ?>">
								<button class="btn btn-secondary" type="submit">
									<?= ((int)$r['attended'] === 1) ? 'Mark absent' : 'Mark attended' ?>
								</button>
							</form>
							<?php if ((int)$r['attended'] === 1): ?>
								<a class="btn" href="<?= e(url('/admin/generate_certificate.php?booking_id=' . (int)$r['booking_id'])) ?>">Issue certificate</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

