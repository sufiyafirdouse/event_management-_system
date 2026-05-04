<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();
require_post_and_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
	http_response_code(400);
	exit('Missing event id');
}

$conn = db();
$stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

set_flash('success', 'Event deleted.');
redirect('/admin/manage_events.php');

