<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['error' => 'Unauthorized']));
}

require_once dirname(dirname(__DIR__)) . '/config/db.php';

$type_id = $_GET['type_id'] ?? null;

if ($type_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, room_number FROM rooms WHERE room_type_id = ? AND status = 'available' ORDER BY room_number ASC");
        $stmt->execute([$type_id]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rooms);
    } catch (PDOException $e) {
        die(json_encode(['error' => $e->getMessage()]));
    }
} else {
    echo json_encode([]);
}
