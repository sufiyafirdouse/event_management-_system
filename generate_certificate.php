<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

$conn = db();

function generate_certificate_code(): string
{
	return 'CERT-' . strtoupper(bin2hex(random_bytes(6)));
}

$bookingId = (int)($_GET['booking_id'] ?? 0);
if ($bookingId > 0) {
	// Ensure attended
	$stmt = $conn->prepare(
		"SELECT
			b.id,
			b.booking_ref,
			u.name AS user_name,
			e.title AS event_title,
			COALESCE(a.attended, 0) AS attended
		FROM bookings b
		INNER JOIN users u ON u.id = b.user_id
		INNER JOIN events e ON e.id = b.event_id
		LEFT JOIN attendance a ON a.booking_id = b.id
		WHERE b.id = ?"
	);
	$stmt->bind_param('i', $bookingId);
	$stmt->execute();
	$info = $stmt->get_result()->fetch_assoc();

	if (!$info) {
		http_response_code(404);
		exit('Booking not found');
	}
	if ((int)$info['attended'] !== 1) {
		set_flash('error', 'Attendance is not marked as attended for this booking.');
		redirect('/admin/mark_attendance.php');
	}

	// Create certificate if not exists
	$stmt = $conn->prepare('SELECT certificate_code FROM certificates WHERE booking_id = ?');
	$stmt->bind_param('i', $bookingId);
	$stmt->execute();
	$existing = $stmt->get_result()->fetch_assoc();

	if ($existing) {
		$code = (string)$existing['certificate_code'];
		set_flash('success', 'Certificate already issued.');
		redirect('/certificate/generate_pdf.php?code=' . urlencode($code));
	}

	$code = generate_certificate_code();
	$adminId = admin_id();
	$stmt = $conn->prepare('INSERT INTO certificates (booking_id, certificate_code, issued_by_admin_id) VALUES (?,?,?)');
	$stmt->bind_param('isi', $bookingId, $code, $adminId);
	$stmt->execute();

	set_flash('success', 'Certificate issued.');
	redirect('/certificate/generate_pdf.php?code=' . urlencode($code));
}

// List eligible bookings: confirmed + attended + no certificate
$sql = "
	SELECT
		b.id AS booking_id,
		b.booking_ref,
		u.name AS user_name,
		u.email AS user_email,
		e.title AS event_title,
		e.start_datetime AS event_start
	FROM bookings b
	INNER JOIN users u ON u.id = b.user_id
	INNER JOIN events e ON e.id = b.event_id
	INNER JOIN attendance a ON a.booking_id = b.id AND a.attended = 1
	LEFT JOIN certificates c ON c.booking_id = b.id
	WHERE b.status = 'CONFIRMED' AND c.id IS NULL
	ORDER BY e.start_datetime DESC, u.name ASC
";
$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Issue Certificates</h1>

<div class="card">
	<table class="table">
		<thead>
		<tr>
			<th>Ref</th>
			<th>User</th>
			<th>Event</th>
			<th>Event Start</th>
			<th>Action</th>
		</tr>
		</thead>
		<tbody>
		<?php if (!$rows): ?>
			<tr><td colspan="5" class="muted">No eligible bookings found (need attended + no certificate).</td></tr>
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
					<td>
						<a class="btn" href="<?= e(url('/admin/generate_certificate.php?booking_id=' . (int)$r['booking_id'])) ?>">Issue</a>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

