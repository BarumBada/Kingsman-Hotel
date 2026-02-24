<?php
include 'includes/header.php';
require_once 'config/db.php';
require_once 'includes/mail_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$room_id = $_GET['id'] ?? null;
if (!$room_id) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: index.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $user_id = $_SESSION['user_id'];
    $ref = 'KGM-' . strtoupper(substr(uniqid(), -6));

    $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    if ($days <= 0)
        $days = 1;
    $total_price = $room['price_per_night'] * $days;
    try {
        $pdo->beginTransaction();

        $room_stmt = $pdo->prepare("SELECT r.id FROM rooms r
    WHERE r.room_type_id = ?
    AND r.id NOT IN (
    SELECT room_id FROM bookings
    WHERE NOT (check_out_date <= ? OR check_in_date>= ?)
        AND status != 'cancelled'
        ) LIMIT 1");
        $room_stmt->execute([$room_id, $check_in, $check_out]);
        $available_room = $room_stmt->fetch();

        if (!$available_room) {
            throw new Exception("System Error: No suites of this type are available for the selected dates.");
        }

        $assigned_room_id = $available_room['id'];


        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, booking_reference, check_in_date, check_out_date,
        total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $assigned_room_id, $ref, $check_in, $check_out, $total_price]);
        $booking_id = $pdo->lastInsertId();


        $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method) VALUES (?, ?, 'Secured
        Channel')");
        $stmt->execute([$booking_id, $total_price]);


        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Booking Submitted',
        ?)");
        $notif_msg = "Your reservation for the " . $room['type_name'] . " has been submitted and is pending approval. Reference: " . $ref;
        $stmt->execute([$user_id, $notif_msg]);


        $email_body = "<h3>Reservation Submitted</h3>
        <p>Dear Guest,</p>
        <p>Your reservation for the <strong>" . $room['type_name'] . "</strong> has been submitted and is <strong>pending approval</strong>.</p>
        <p><strong>Reference:</strong> " . $ref . "</p>
        <p><strong>Check-in:</strong> " . $check_in . "</p>
        <p><strong>Check-out:</strong> " . $check_out . "</p>
        <p>You will be notified once our team confirms your reservation.</p>";
        $branded_html = get_branded_template("Reservation Submitted", $email_body);
        send_kingsman_mail($_SESSION['user_email'], "Reservation Submitted - " . $ref, $branded_html);

        $pdo->commit();
        $message = "Reservation Submitted! Your reference is: " . $ref . ". Please wait for admin confirmation.";
        $messageType = "success";
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        $message = "Reservation Failed: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($message): ?>
                <div class="kingsman-alert <?php echo $messageType; ?> mb-4">
                    <div class="d-flex align-items-center">
                        <i
                            class="bi <?php echo $messageType == 'success' ? 'bi-shield-check' : 'bi-exclamation-octagon'; ?> fs-4 me-3"></i>
                        <div>
                            <?php echo $message; ?>
                        </div>
                    </div>
                    <?php if ($messageType == 'success'): ?>
                        <div class="mt-3">
                            <a href="dashboard.php" class="btn btn-kingsman btn-sm">Go to Dashboard</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="card kingsman-card overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-5">
                        <img src="assets/img/<?php echo htmlspecialchars($room['thumbnail_image']); ?>"
                            class="img-fluid h-100" style="object-fit: cover;">
                    </div>
                    <div class="col-md-7 p-4">
                        <h2 class="gold-text mb-3">Reservation Details:
                            <?php echo htmlspecialchars($room['type_name']); ?>
                        </h2>
                        <p class="text-muted">
                            <?php echo htmlspecialchars($room['description']); ?>
                        </p>
                        <h4 class="mb-4">₱
                            <?php echo number_format($room['price_per_night'], 2); ?> <small class="text-muted fs-6">/
                                night</small>
                        </h4>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Check-in Date</label>
                                    <input type="date" name="check_in" class="form-control" required
                                        min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Check-out Date</label>
                                    <input type="date" name="check_out" class="form-control" required
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                            </div>
                            <button type="submit" name="confirm_booking"
                                class="btn btn-kingsman w-100 py-3 mt-3">Confirm Booking</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>