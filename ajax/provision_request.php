<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $booking_id = $_POST['booking_id'] ?? null;
    $service_type = $_POST['service_type'] ?? '';
    $details = $_POST['details'] ?? '';

    if (!$booking_id || !$service_type) {
        echo json_encode(['status' => 'error', 'message' => 'Missing operational parameters.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO service_requests (booking_id, user_id, service_type, details, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$booking_id, $user_id, $service_type, $details]);

        // Add a notification for the request
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Provision Request', ?)");
        $notif_msg = "Request for " . strtoupper($service_type) . " received: " . $details;
        $notif_stmt->execute([$user_id, $notif_msg]);

        echo json_encode(['status' => 'success', 'message' => 'Operational support requested. Galahad is on it.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Deployment failure: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
}
?>