<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT firstname, lastname, profile_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();

$stmt = $pdo->prepare("SELECT b.*, rt.type_name, rt.id as room_type_id 
                     FROM bookings b 
                     JOIN rooms r ON b.room_id = r.id
                     JOIN room_types rt ON r.room_type_id = rt.id
                     WHERE b.user_id = ? 
                     ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];
    $check_stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
    $check_stmt->execute([$cancel_id, $user_id]);
    if ($check_stmt->fetch()) {
        $cancel_stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $cancel_stmt->execute([$cancel_id]);

        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Booking Cancelled', 'Your reservation has been successfully cancelled. We hope to see you again soon.')");
        $notif_stmt->execute([$user_id]);

        header("Location: index.php?msg=cancelled");
        exit();
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php
    $is_admin_path = false;
    include dirname(__DIR__) . '/admin/includes/sidebar.php';
    ?>

    <div class="flex-grow-1 p-5">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <div class="d-flex align-items-center">
                <img src="<?php echo '../assets/img/' . ($user_info['profile_image'] ? 'avatars/' . $user_info['profile_image'] : 'room_placeholder.jpg'); ?>"
                    alt="Guest Profile" class="rounded-circle border border-gold me-4 shadow"
                    style="width: 80px; height: 80px; object-fit: cover;">
                <div>
                    <h1 class="display-4 gold-text mb-0">Welcome,
                        <?php echo explode(' ', $_SESSION['full_name'])[0]; ?>
                    </h1>
                    <p class="lead text-muted ms-1">Manage your luxury stay and reservations.</p>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?>
            <div class="kingsman-alert success mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle fs-4 me-3"></i>
                    <div>Booking successfully cancelled. Your records have been updated.</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mb-5">
            <div class="col-12">
                <?php
                $priority_booking = null;
                foreach ($bookings as $b) {
                    if ($b['status'] == 'checked_in') {
                        $priority_booking = $b;
                        $booking_label = "CURRENT STAY";
                        $booking_theme = "success";
                        break;
                    } elseif ($b['status'] == 'confirmed') {
                        $priority_booking = $b;
                        $booking_label = "UPCOMING STAY";
                        $booking_theme = "gold";
                    }
                }
                if ($priority_booking):
                    ?>
                    <div
                        class="card kingsman-card p-4 border-<?php echo $booking_theme == 'gold' ? 'gold' : 'success'; ?> glass-panel">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <div class="spinner-grow text-<?php echo $booking_theme == 'gold' ? 'warning' : 'success'; ?>"
                                    role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="small fw-bold mt-2 mb-0"><?php echo $booking_label; ?></p>
                            </div>
                            <div class="col-md-7">
                                <h4 class="gold-text mb-1">Reservation:
                                    <?php echo htmlspecialchars($priority_booking['booking_reference']); ?>
                                </h4>
                                <p class="small text-muted mb-0">Location: Kingsman Hotel | Suite:
                                    <?php echo htmlspecialchars($priority_booking['type_name'] ?? 'Luxury Suite'); ?>
                                </p>
                                <p class="small mb-0">Stay Period:
                                    <?php echo date('M d', strtotime($priority_booking['check_in_date'])); ?> —
                                    <?php echo date('M d, Y', strtotime($priority_booking['check_out_date'])); ?>
                                </p>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="room_details.php?id=<?php echo $priority_booking['room_type_id'] ?? 1; ?>"
                                    class="btn btn-kingsman btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card kingsman-card p-4 text-center glass-panel">
                        <p class="mb-0 text-muted small"><i class="bi bi-calendar-x me-2"></i> No active or upcoming
                            reservations detected. <a href="../index.php#rooms"
                                class="gold-text text-decoration-none fw-bold">Explore Our Suites</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="card kingsman-card p-4 mb-4">
                    <h4 class="gold-text mb-3">Profile Details</h4>
                    <?php
                    $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $u_stmt->execute([$user_id]);
                    $user = $u_stmt->fetch();
                    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
                    $profile_link = ($current_dir == 'user') ? 'settings.php' : 'user/settings.php';
                    ?>
                    <p class="mb-1 small text-muted">Identity (Email)</p>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="mb-1 small text-muted">Primary Contact (Phone)</p>
                    <p><?php echo htmlspecialchars($user['phone']); ?></p>
                    <a href="<?php echo $profile_link; ?>" class="btn btn-kingsman btn-sm w-100">Update Profile</a>
                </div>
                <div class="card kingsman-card p-4 h-100 glass-panel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="gold-text mb-0"><i class="bi bi-bell-fill me-2"></i> Guest Notifications</h4>
                        <span class="badge bg-gold text-dark">LATEST</span>
                    </div>
                    <div class="notification-feed" style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                        <?php
                        $notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                        $notif_stmt->execute([$user_id]);
                        $notifications = $notif_stmt->fetchAll();

                        if (empty($notifications)): ?>
                            <div class="text-center py-5 text-muted small">
                                <i class="bi bi-info-circle d-block fs-3 mb-2"></i>
                                Notifications clear. No new updates.
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="mb-4 pb-3 border-bottom border-secondary opacity-75">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span
                                            class="gold-text fw-bold text-uppercase"><?php echo htmlspecialchars($notif['title']); ?></span>
                                        <span
                                            class="text-muted"><?php echo date('H:i', strtotime($notif['created_at'])); ?></span>
                                    </div>
                                    <p class="small mb-0 text-white"><?php echo htmlspecialchars($notif['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <h3 class="mb-4">Your Bookings</h3>
                <?php if (empty($bookings)): ?>
                    <div class="card kingsman-card p-5 text-center">
                        <p class="">You have no active bookings with us.</p>
                        <a href="index.php#rooms" class="btn btn-kingsman mx-auto">Book Your Next Stay</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-kingsman">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td class="gold-text fw-bold">
                                            <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['check_in_date']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['check_out_date']); ?>
                                        </td>
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
                                            <span class="badge <?php echo $badge_class; ?> text-white font-weight-bold">
                                                <?php echo strtoupper($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                                <a href="index.php?cancel_id=<?php echo $booking['id']; ?>"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Cancel this reservation? This action cannot be undone.')">
                                                    Cancel
                                                </a>
                                            <?php else: ?>
                                                <a href="print_receipt.php?id=<?php echo $booking['id']; ?>" target="_blank"
                                                    class="btn btn-outline-gold btn-sm">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <div class="row mt-5">
                    <div class="col-12">
                        <h3 class="gold-text mb-4"><i class="bi bi-clock-history me-2"></i> Reservation History</h3>
                        <div class="card kingsman-card glass-panel p-0 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0" style="background: transparent;">
                                    <thead>
                                        <tr class="text-muted small text-uppercase">
                                            <th class="ps-4">Reference</th>
                                            <th>Location / Suite</th>
                                            <th>Stay Period</th>
                                            <th>Status</th>
                                            <th class="pe-4 text-end">Document</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $has_history = false;
                                        foreach ($bookings as $booking):
                                            if (in_array($booking['status'], ['checked_out', 'cancelled'])):
                                                $has_history = true;
                                                ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold">
                                                        <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($booking['type_name']); ?></td>
                                                    <td class="small">
                                                        <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?> —
                                                        <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge rounded-pill bg-<?php echo ($booking['status'] == 'checked_out') ? 'secondary' : 'danger'; ?> opacity-75">
                                                            <?php echo strtoupper($booking['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="pe-4 text-end">
                                                        <a href="print_receipt.php?id=<?php echo $booking['id']; ?>"
                                                            target="_blank" class="btn btn-outline-gold btn-sm py-0 px-2"
                                                            style="font-size: 0.7rem;">Receipt</a>
                                                    </td>
                                                </tr>
                                                <?php
                                            endif;
                                        endforeach;
                                        if (!$has_history):
                                            ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">No historical data
                                                    found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>