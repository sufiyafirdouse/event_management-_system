<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

$conn = db();
$events = $conn->query('SELECT * FROM events ORDER BY start_datetime DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-row">
	<h1>Manage Events</h1>
	<a class="btn" href="<?= e(url('/admin/add_event.php')) ?>">Add Event</a>
</div>

<div class="card">
	<table class="table">
		<thead>
		<tr>
			<th>Title</th>
			<th>Start</th>
			<th>Price</th>
			<th>Capacity</th>
			<th>Active</th>
			<th>Actions</th>
		</tr>
		</thead>
		<tbody>
		<?php if (!$events): ?>
			<tr><td colspan="6" class="muted">No events yet.</td></tr>
		<?php else: ?>
			<?php foreach ($events as $event): ?>
				<tr>
					<td><?= e($event['title']) ?></td>
					<td><?= e($event['start_datetime']) ?></td>
					<td><?= e(number_format((float)$event['price'], 2)) ?></td>
					<td><?= e($event['capacity'] === null ? '—' : (string)$event['capacity']) ?></td>
					<td><?= e(((int)$event['is_active']) === 1 ? 'Yes' : 'No') ?></td>
					<td class="actions">
						<a class="btn btn-secondary" href="<?= e(url('/admin/edit_event.php?id=' . (int)$event['id'])) ?>">Edit</a>
						<form method="post" action="<?= e(url('/admin/delete_event.php')) ?>" style="display:inline;">
							<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
							<input type="hidden" name="id" value="<?= e((string)$event['id']) ?>">
							<button class="btn btn-danger" type="submit" onclick="return confirm('Delete this event?');">Delete</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

