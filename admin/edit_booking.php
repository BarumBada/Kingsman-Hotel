<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: bookings.php");
    exit();
}

// Fetch the booking details
$stmt = $pdo->prepare("SELECT b.*, u.firstname, u.lastname, u.email 
                       FROM bookings b 
                       JOIN users u ON b.user_id = u.id 
                       WHERE b.id = ? AND b.status = 'pending'");
$stmt->execute([$id]);
$booking = $stmt->fetch();

// If booking is not found or not pending, redirect back
if (!$booking) {
    header("Location: bookings.php?msg=invalid_edit");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_booking'])) {
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in_date'];
    $check_out = $_POST['check_out_date'];

    if (strtotime($check_in) >= strtotime($check_out)) {
        $error = "Check-out date must be after check-in date.";
    } else {
        // Calculate new total price
        $r_stmt = $pdo->prepare("SELECT rt.price_per_night FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
        $r_stmt->execute([$room_id]);
        $room = $r_stmt->fetch();

        $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
        $total_price = $room['price_per_night'] * $days;

        // Update the booking
        $update_stmt = $pdo->prepare("UPDATE bookings SET room_id = ?, check_in_date = ?, check_out_date = ?, total_price = ? WHERE id = ?");
        if ($update_stmt->execute([$room_id, $check_in, $check_out, $total_price, $id])) {

            // Notify user
            $notif_msg = "Your pending reservation (Reference: " . $booking['booking_reference'] . ") has been updated by administration.";
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Reservation Updated', ?)");
            $notif_stmt->execute([$booking['user_id'], $notif_msg]);

            header("Location: bookings.php?msg=updated");
            exit();
        } else {
            $error = "Failed to update reservation.";
        }
    }
}

// Fetch available rooms for the dropdown
$rooms_stmt = $pdo->query("SELECT r.id, r.room_number, rt.type_name, rt.price_per_night 
                           FROM rooms r 
                           JOIN room_types rt ON r.room_type_id = rt.id 
                           ORDER BY r.room_number ASC");
$rooms = $rooms_stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="mb-5">
            <h1 class="display-5">Edit Reservation</h1>
            <p class="text-muted">Modify details for Pending Reservation: <span class="gold-text fw-bold">
                    <?php echo htmlspecialchars($booking['booking_reference']); ?>
                </span></p>
        </div>

        <?php if ($error): ?>
            <div class="kingsman-alert error mb-4">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card kingsman-card p-5 glass-panel border-0 shadow-lg">
                    <h4 class="gold-text mb-4 border-bottom border-gold pb-2">Reservation Parameters</h4>

                    <div class="mb-4">
                        <label class="form-label text-muted small text-uppercase">Guest Identity</label>
                        <input type="text" class="form-control bg-dark text-white border-0"
                            value="<?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname'] . ' (' . $booking['email'] . ')'); ?>"
                            disabled>
                    </div>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label gold-text">Suite Assignment</label>
                            <select name="room_id" class="form-select bg-dark text-white border-secondary" required>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>" <?php echo ($room['id'] == $booking['room_id']) ? 'selected' : ''; ?>>
                                        Room
                                        <?php echo htmlspecialchars($room['room_number']); ?> -
                                        <?php echo htmlspecialchars($room['type_name']); ?> (₱
                                        <?php echo number_format($room['price_per_night'], 0); ?>/night)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row mb-5">
                            <div class="col-md-6">
                                <label class="form-label gold-text">Arrival Date</label>
                                <input type="date" name="check_in_date"
                                    class="form-control bg-dark text-white border-secondary"
                                    value="<?php echo $booking['check_in_date']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label gold-text">Departure Date</label>
                                <input type="date" name="check_out_date"
                                    class="form-control bg-dark text-white border-secondary"
                                    value="<?php echo $booking['check_out_date']; ?>" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="bookings.php" class="btn btn-outline-secondary me-3 px-4">Cancel</a>
                            <button type="submit" name="update_booking" class="btn btn-kingsman px-5"><i
                                    class="bi bi-save me-2"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>