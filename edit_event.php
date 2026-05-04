<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

function normalize_datetime_local(?string $value): ?string
{
	$value = trim((string)$value);
	if ($value === '') {
		return null;
	}
	$value = str_replace('T', ' ', $value);
	if (strlen($value) === 16) {
		$value .= ':00';
	}
	return $value;
}

function to_datetime_local(?string $value): string
{
	if (!$value) {
		return '';
	}
	// Converts "YYYY-MM-DD HH:MM:SS" => "YYYY-MM-DDTHH:MM"
	return str_replace(' ', 'T', substr($value, 0, 16));
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
	http_response_code(400);
	exit('Missing event id');
}

$conn = db();
$stmt = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) {
	http_response_code(404);
	exit('Event not found');
}

$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	if (!csrf_verify($_POST['_csrf'] ?? null)) {
		$error = 'Invalid CSRF token.';
	} else {
		$title = trim((string)($_POST['title'] ?? ''));
		$description = trim((string)($_POST['description'] ?? ''));
		$location = trim((string)($_POST['location'] ?? ''));
		$start = normalize_datetime_local($_POST['start_datetime'] ?? null);
		$end = normalize_datetime_local($_POST['end_datetime'] ?? null);
		$capacityRaw = trim((string)($_POST['capacity'] ?? ''));
		$capacity = $capacityRaw === '' ? null : max(0, (int)$capacityRaw);
		$price = (float)($_POST['price'] ?? 0);
		$isActive = isset($_POST['is_active']) ? 1 : 0;

		if ($title === '' || !$start) {
			$error = 'Title and start date/time are required.';
		} else {
			$stmt = $conn->prepare('UPDATE events SET title=?, description=?, location=?, start_datetime=?, end_datetime=?, capacity=?, price=?, is_active=? WHERE id=?');
			$stmt->bind_param(
				'sssssidii',
				$title,
				$description,
				$location,
				$start,
				$end,
				$capacity,
				$price,
				$isActive,
				$id
			);
			$stmt->execute();
			set_flash('success', 'Event updated.');
			redirect('/admin/manage_events.php');
		}
	}
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Edit Event</h1>

<?php if ($error): ?>
	<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
	<form method="post" action="">
		<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
		<div class="field">
			<label>Title</label>
			<input class="input" type="text" name="title" value="<?= e($event['title']) ?>" required>
		</div>
		<div class="field">
			<label>Description</label>
			<textarea class="input" name="description" rows="4"><?= e($event['description']) ?></textarea>
		</div>
		<div class="field">
			<label>Location</label>
			<input class="input" type="text" name="location" value="<?= e($event['location']) ?>">
		</div>
		<div class="field">
			<label>Start (date & time)</label>
			<input class="input" type="datetime-local" name="start_datetime" value="<?= e(to_datetime_local($event['start_datetime'])) ?>" required>
		</div>
		<div class="field">
			<label>End (date & time)</label>
			<input class="input" type="datetime-local" name="end_datetime" value="<?= e(to_datetime_local($event['end_datetime'])) ?>">
		</div>
		<div class="field">
			<label>Capacity</label>
			<input class="input" type="number" name="capacity" min="0" step="1" value="<?= e($event['capacity'] === null ? '' : (string)$event['capacity']) ?>" placeholder="Leave empty for unlimited">
		</div>
		<div class="field">
			<label>Price</label>
			<input class="input" type="number" name="price" min="0" step="0.01" value="<?= e((string)$event['price']) ?>">
		</div>
		<div class="field">
			<label class="checkbox">
				<input type="checkbox" name="is_active" <?= ((int)$event['is_active'] === 1) ? 'checked' : '' ?>> Active
			</label>
		</div>
		<div class="page-row">
			<button class="btn" type="submit">Save</button>
			<a class="btn btn-secondary" href="<?= e(url('/admin/manage_events.php')) ?>">Cancel</a>
		</div>
	</form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

