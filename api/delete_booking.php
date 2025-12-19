<?php
define('IS_API_CALL', true);
require_once '../config.php';
// allow session to check ownership
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

// Only allow POST from form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

if (!isset($pdo)) {
    http_response_code(500);
    echo "DB not initialized";
    exit;
}

$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
if ($bookingId <= 0) {
    header('Location: ../profile.php?error=invalid_id');
    exit;
}

try {
    // fetch booking and check ownership
    $stmt = $pdo->prepare('SELECT id, user_id FROM bookings WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $bookingId]);
    $b = $stmt->fetch();
    if (!$b) {
        header('Location: ../profile.php?error=not_found');
        exit;
    }

    $isOwner = (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$b['user_id']);
    $isAdmin = (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

    if (!$isOwner && !$isAdmin) {
        header('Location: ../profile.php?error=forbidden');
        exit;
    }

    // delete
    $dstmt = $pdo->prepare('DELETE FROM bookings WHERE id = :id');
    $dstmt->execute(['id' => $bookingId]);

    header('Location: ../profile.php?deleted=1');
    exit;

} catch (Exception $e) {
    error_log('Delete booking error: ' . $e->getMessage());
    header('Location: ../profile.php?error=exception');
    exit;
}

?>
