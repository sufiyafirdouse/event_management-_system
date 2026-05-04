<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

$conn = db();
$sql = "
	SELECT
		b.id,
		b.booking_ref,
		b.status,
		b.payment_status,
		b.amount,
		b.created_at,
		u.name AS user_name,
		u.email AS user_email,
		e.title AS event_title,
		e.start_datetime AS event_start
	FROM bookings b
	INNER JOIN users u ON u.id = b.user_id
	INNER JOIN events e ON e.id = b.event_id
	ORDER BY b.created_at DESC
";
$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Bookings</h1>

<div class="card">
	<table class="table">
		<thead>
		<tr>
			<th>Ref</th>
			<th>User</th>
			<th>Event</th>
			<th>Event Start</th>
			<th>Status</th>
			<th>Payment</th>
			<th>Amount</th>
			<th>Booked At</th>
		</tr>
		</thead>
		<tbody>
		<?php if (!$rows): ?>
			<tr><td colspan="8" class="muted">No bookings yet.</td></tr>
		<?php else: ?>
			<?php foreach ($rows as $r): ?>
				<tr>
					<td><?= e($r['booking_ref']) ?></td>
					<td>
						<div><?= e($r['user_name']) ?></div>
						<div class="muted"><?= e($r['user_email']) ?></div>
					</td>
					<td><?= e($r['event_title']) ?></td>
					<td><?= e($r['event_start']) ?></td>
					<td><?= e($r['status']) ?></td>
					<td><?= e($r['payment_status']) ?></td>
					<td><?= e(number_format((float)$r['amount'], 2)) ?></td>
					<td><?= e($r['created_at']) ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

