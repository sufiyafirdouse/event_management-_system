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
			$conn = db();
			$stmt = $conn->prepare('INSERT INTO events (title, description, location, start_datetime, end_datetime, capacity, price, is_active) VALUES (?,?,?,?,?,?,?,?)');
			// s s s s s i d i
			$stmt->bind_param(
				'sssssidi',
				$title,
				$description,
				$location,
				$start,
				$end,
				$capacity,
				$price,
				$isActive
			);
			$stmt->execute();
			set_flash('success', 'Event created.');
			redirect('/admin/manage_events.php');
		}
	}
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1>Add Event</h1>

<?php if ($error): ?>
	<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
	<form method="post" action="">
		<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

		<div class="field">
			<label>Title</label>
			<input class="input" type="text" name="title" required>
		</div>
		<div class="field">
			<label>Description</label>
			<textarea class="input" name="description" rows="4"></textarea>
		</div>
		<div class="field">
			<label>Location</label>
			<input class="input" type="text" name="location">
		</div>
		<div class="field">
			<label>Start (date & time)</label>
			<input class="input" type="datetime-local" name="start_datetime" required>
		</div>
		<div class="field">
			<label>End (date & time)</label>
			<input class="input" type="datetime-local" name="end_datetime">
		</div>
		<div class="field">
			<label>Capacity</label>
			<input class="input" type="number" name="capacity" min="0" step="1" placeholder="Leave empty for unlimited">
		</div>
		<div class="field">
			<label>Price</label>
			<input class="input" type="number" name="price" min="0" step="0.01" value="0">
		</div>
		<div class="field">
			<label class="checkbox">
				<input type="checkbox" name="is_active" checked> Active
			</label>
		</div>
		<div class="page-row">
			<button class="btn" type="submit">Create</button>
			<a class="btn btn-secondary" href="<?= e(url('/admin/manage_events.php')) ?>">Cancel</a>
		</div>
	</form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

