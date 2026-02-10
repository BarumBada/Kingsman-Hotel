<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = $_GET['update_status'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);

    $b_stmt = $pdo->prepare("SELECT user_id, booking_reference FROM bookings WHERE id = ?");
    $b_stmt->execute([$id]);
    $booking = $b_stmt->fetch();

    $notif_msg = "Booking status for " . $booking['booking_reference'] . " has been updated to: " . strtoupper($new_status);
    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Booking Update', ?)");
    $notif_stmt->execute([$booking['user_id'], $notif_msg]);

    header("Location: bookings.php?msg=updated");
    exit();
}

$stmt = $pdo->query("SELECT b.*, u.firstname, u.lastname, rt.type_name 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.id 
                     JOIN rooms r ON b.room_id = r.id
                     JOIN room_types rt ON r.room_type_id = rt.id 
                     ORDER BY b.created_at DESC");
$bookings = $stmt->fetchAll();

$pending_reservations = 0;
$guests_on_property = 0;
foreach ($bookings as $b) {
    if ($b['status'] == 'confirmed')
        $pending_reservations++;
    if ($b['status'] == 'checked_in')
        $guests_on_property++;
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-5">Reservation Records</h1>
                <p class="text-muted">Awaiting Arrival: <span
                        class="gold-text fw-bold"><?php echo $pending_reservations; ?></span> | On-Property: <span
                        class="text-success fw-bold"><?php echo $guests_on_property; ?></span></p>
            </div>
            <a href="inventory.php" class="btn btn-outline-gold">Room Inventory</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="kingsman-alert success mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle fs-4 me-3"></i>
                    <div>Reservation status updated. Notification sent to guest.</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card kingsman-card glass-panel p-4 border-0 shadow-lg">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-4">Reference</th>
                            <th>Guest Identity</th>
                            <th>Suite Type</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td class="ps-4 gold-text fw-bold">
                                    <?php if ($booking['status'] == 'confirmed'): ?>
                                        <span class="pulse-red d-inline-block me-2"
                                            style="width: 8px; height: 8px; border-radius: 50%;"></span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($booking['type_name']); ?></td>
                                <td class="small"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                <td class="small"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'bg-gold';
                                    if ($booking['status'] == 'cancelled')
                                        $badge_class = 'bg-danger';
                                    if ($booking['status'] == 'checked_out')
                                        $badge_class = 'bg-secondary';
                                    if ($booking['status'] == 'checked_in')
                                        $badge_class = 'bg-success';
                                    ?>
                                    <span
                                        class="badge rounded-pill <?php echo $badge_class; ?> text-white font-weight-bold">
                                        <?php echo strtoupper($booking['status']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex justify-content-end align-items-center">
                                        <a href="print_receipt.php?id=<?php echo $booking['id']; ?>" target="_blank"
                                            class="btn text-white btn-outline-gold btn-sm me-2" title="Print Receipt">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <div class="dropdown">
                                            <button class="btn text-white btn-outline-gold btn-sm py-0 px-2 dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown" style="font-size: 0.7rem;">
                                                MANAGE
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-gold">
                                                <?php if ($booking['status'] == 'confirmed' || $booking['status'] == 'pending'): ?>
                                                    <li><a class="dropdown-item py-1 small"
                                                            href="bookings.php?update_status=checked_in&id=<?php echo $booking['id']; ?>">CHECK
                                                            GUEST IN</a></li>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] == 'checked_in'): ?>
                                                    <li><a class="dropdown-item py-1 small text-success"
                                                            href="bookings.php?update_status=checked_out&id=<?php echo $booking['id']; ?>">CHECK
                                                            GUEST OUT</a></li>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] != 'cancelled' && $booking['status'] != 'checked_out'): ?>
                                                    <li>
                                                        <hr class="dropdown-divider border-secondary opacity-25">
                                                    </li>
                                                    <li><a class="dropdown-item py-1 small text-danger"
                                                            href="bookings.php?update_status=cancelled&id=<?php echo $booking['id']; ?>">CANCEL
                                                            RESERVATION</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No reservations active at the moment.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>