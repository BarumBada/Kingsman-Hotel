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

// Trust Grade Calculation
$completed_stays = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'checked_out'");
$completed_stays->execute([$user_id]);
$stay_count = $completed_stays->fetchColumn();

if ($stay_count >= 10) {
    $trust_rank = "ARTHUR";
    $rank_color = "#DAA520"; // Gold
    $rank_icon = "bi-crown-fill";
} elseif ($stay_count >= 5) {
    $trust_rank = "GALAHAD";
    $rank_color = "#DAA520";
    $rank_icon = "bi-shield-shaded";
} elseif ($stay_count >= 2) {
    $trust_rank = "LANCELOT";
    $rank_color = "#c0c0c0"; // Silver
    $rank_icon = "bi-shield-check";
} else {
    $trust_rank = "RECRUIT";
    $rank_color = "#a9a9a9"; // Gray
    $rank_icon = "bi-person-badge";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $cancel_id = $_POST['booking_id'];
    $reason = trim($_POST['cancellation_reason']);

    $check_stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
    $check_stmt->execute([$cancel_id, $user_id]);

    if ($check_stmt->fetch()) {
        $cancel_stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = ? WHERE id = ?");
        $cancel_stmt->execute([$reason, $cancel_id]);

        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Booking Cancelled', ?)");
        $notif_msg = "Your reservation has been cancelled. Reason: " . ($reason ?: "No reason provided.");
        $notif_stmt->execute([$user_id, $notif_msg]);

        header("Location: index.php?msg=cancelled");
        exit();
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex" style="min-height: 100vh;">
    <?php
    $is_admin_path = false;
    include dirname(__DIR__) . '/admin/includes/sidebar.php';
    ?>

    <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <div class="p-4 p-lg-5 flex-grow-1">

            <!-- Welcome Header -->
            <div class="d-flex align-items-center mb-4 flex-wrap gap-3">
                <img src="<?php echo 'assets/img/' . ($user_info['profile_image'] ? 'avatars/' . $user_info['profile_image'] : 'room_placeholder.jpg'); ?>"
                    alt="Guest" class="rounded-circle border border-gold shadow"
                    style="width: 64px; height: 64px; object-fit: cover;">
                <div class="flex-grow-1">
                    <h2 class="gold-text mb-0" style="letter-spacing: 1px;">Guest Dashboard:
                        <?php echo explode(' ', $_SESSION['full_name'])[0]; ?>
                    </h2>
                    <p class="text-muted small mb-0 mt-1">Manage your luxury stay and reservations.</p>
                </div>
                <a href="index.php#rooms" class="btn btn-kingsman btn-sm px-4">
                    <i class="bi bi-plus-circle me-2"></i>New Booking
                </a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?>
                <div class="kingsman-alert success mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle fs-4 me-3"></i>
                        <div>Booking successfully cancelled. Your records have been updated.</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Priority Booking Banner -->
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
                } elseif ($b['status'] == 'pending' && !$priority_booking) {
                    $priority_booking = $b;
                    $booking_label = "PENDING APPROVAL";
                    $booking_theme = "warning";
                }
            }
            if ($priority_booking):
                $theme_color = ($booking_theme == 'gold') ? '#DAA520' : (($booking_theme == 'warning') ? '#f39c12' : '#2ecc71');
                ?>
                <div class="card glass-panel p-4 mb-4"
                    style="border-left: 4px solid <?php echo $theme_color; ?>; border-radius: 6px;">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                            <div
                                style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $theme_color; ?>; box-shadow: 0 0 8px <?php echo $theme_color; ?>; animation: pulseRed 2s infinite;">
                            </div>
                            <div>
                                <span
                                    class="badge bg-<?php echo ($booking_theme == 'gold') ? 'warning' : $booking_theme; ?> bg-opacity-25 text-<?php echo ($booking_theme == 'gold') ? 'warning' : $booking_theme; ?> small mb-1"><?php echo $booking_label; ?></span>
                                <h5 class="gold-text mb-0">
                                    <?php echo htmlspecialchars($priority_booking['booking_reference']); ?>
                                </h5>
                                <p class="text-muted small mb-0">
                                    <?php echo htmlspecialchars($priority_booking['type_name'] ?? 'Luxury Suite'); ?> •
                                    <?php echo date('M d', strtotime($priority_booking['check_in_date'])); ?> —
                                    <?php echo date('M d, Y', strtotime($priority_booking['check_out_date'])); ?>
                                </p>
                            </div>
                        </div>
                        <a href="room_details.php?id=<?php echo $priority_booking['room_type_id'] ?? 1; ?>"
                            class="btn btn-kingsman btn-sm px-3">View Details</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card glass-panel p-4 mb-4 text-center" style="border-radius: 6px;">
                    <p class="mb-0 text-muted small">
                        <i class="bi bi-calendar-x me-2"></i> No active reservations.
                        <a href="index.php#rooms" class="gold-text text-decoration-none fw-bold">Explore Our Suites</a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Left Column: Profile + Notifications -->
                <div class="col-lg-4">
                    <!-- Profile Card -->
                    <div class="card glass-panel p-4 mb-4" style="border-radius: 6px;">
                        <h5 class="gold-text mb-3" style="font-size: 0.9rem; letter-spacing: 1px;">
                            <i class="bi bi-person-vcard me-2"></i>My Profile
                        </h5>
                        <?php
                        $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $u_stmt->execute([$user_id]);
                        $user = $u_stmt->fetch();
                        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
                        $profile_link = ($current_dir == 'user') ? 'settings.php' : 'user/settings.php';
                        ?>
                        <div class="mb-3">
                            <p class="text-muted small mb-0"
                                style="font-size: 0.65rem; letter-spacing: 1px; text-transform: uppercase;">Email</p>
                            <p class="small mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="mb-3">
                            <p class="text-muted small mb-0"
                                style="font-size: 0.65rem; letter-spacing: 1px; text-transform: uppercase;">Phone</p>
                            <p class="small mb-0"><?php echo htmlspecialchars($user['phone']); ?></p>
                        </div>
                        <a href="<?php echo $profile_link; ?>" class="btn btn-outline-gold btn-sm w-100 border-0 small">
                            <i class="bi bi-pencil me-1"></i> Edit Profile
                        </a>
                    </div>

                    <!-- Kingsman Trust Grade -->
                    <div class="card glass-panel p-4 mb-4"
                        style="border-radius: 6px; border-bottom: 3px solid <?php echo $rank_color; ?>;">
                        <h5 class="gold-text mb-3" style="font-size: 0.9rem; letter-spacing: 1px;">
                            <i class="bi bi-award me-2"></i>Kingsman Trust Grade
                        </h5>
                        <div class="text-center py-2">
                            <i class="bi <?php echo $rank_icon; ?> display-4 mb-2 d-block"
                                style="color: <?php echo $rank_color; ?>; opacity: 0.8;"></i>
                            <h4 class="mb-1 fw-bold" style="letter-spacing: 2px; color: <?php echo $rank_color; ?>;">
                                <?php echo $trust_rank; ?>
                            </h4>
                            <p class="text-muted small mb-3">Service Commendations: <?php echo $stay_count; ?></p>
                            <div class="progress bg-dark" style="height: 4px;">
                                <?php
                                $next_goal = ($stay_count < 2) ? 2 : (($stay_count < 5) ? 5 : 10);
                                $progress = ($stay_count / $next_goal) * 100;
                                ?>
                                <div class="progress-bar"
                                    style="width: <?php echo $progress; ?>%; background-color: var(--primary-gold);">
                                </div>
                            </div>
                            <p class="text-muted mt-2" style="font-size: 0.6rem; letter-spacing: 1px;">
                                <?php echo ($trust_rank == 'ARTHUR') ? 'MAXIMUM RANK ATTAINED' : 'NEXT STATUS LEVEL AT ' . $next_goal . ' STAYS'; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Activity Feed -->
                    <div class="card glass-panel p-4" style="border-radius: 6px; background: rgba(5,5,5,0.8);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="gold-text mb-0" style="font-size: 0.9rem; letter-spacing: 1px;">
                                <i class="bi bi-terminal me-2"></i>Recent Activities
                            </h5>
                            <span class="badge bg-gold bg-opacity-25 text-warning small animate-pulse">SECURE</span>
                        </div>
                        <div
                            style="max-height: 300px; overflow-y: auto; font-family: 'Courier New', Courier, monospace;">
                            <?php
                            $notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                            $notif_stmt->execute([$user_id]);
                            $notifications = $notif_stmt->fetchAll();

                            if (empty($notifications)): ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="bi bi-check-circle d-block fs-3 mb-2 opacity-50"></i>
                                    All caught up. No new notifications.
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <div class="mb-3 pb-3 border-bottom border-secondary border-opacity-10">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="gold-text fw-bold"
                                                style="font-size: 0.7rem; text-transform: uppercase;"><?php echo htmlspecialchars($notif['title']); ?></span>
                                            <span class="text-muted"
                                                style="font-size: 0.65rem;"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></span>
                                        </div>
                                        <p class="small mb-0 text-white-50"><?php echo htmlspecialchars($notif['message']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Bookings -->
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="gold-text mb-0"><i class="bi bi-journal-bookmark me-2"></i>My Bookings</h4>
                    </div>

                    <!-- Support Provisioning (Only if checked in) -->
                    <?php if ($priority_booking && $priority_booking['status'] == 'checked_in'): ?>
                        <div class="card glass-panel border-gold p-4 mb-4 reveal shadow-lg overflow-hidden"
                            style="border-radius: 6px;">
                            <div class="position-absolute top-0 end-0 p-3 opacity-25">
                                <i class="bi bi-headset display-1"></i>
                            </div>
                            <div class="row align-items-center g-4 position-relative">
                                <div class="col-md-7">
                                    <h5 class="gold-text mb-2"><i class="bi bi-gear-wide-connected me-2"></i>Support
                                        Provisioning</h5>
                                    <p class="text-white-50 small mb-0">Active deployment detected in
                                        <strong><?php echo $priority_booking['booking_reference']; ?></strong>. Request
                                        tactical assistance below.
                                    </p>
                                </div>
                                <div class="col-md-5">
                                    <div class="d-flex gap-2 justify-content-md-end">
                                        <button class="btn btn-kingsman btn-sm px-3" onclick="requestSupport('tailoring')">
                                            <i class="bi bi-scissors me-1"></i> Tailoring
                                        </button>
                                        <button class="btn btn-kingsman btn-sm px-3" onclick="requestSupport('transport')">
                                            <i class="bi bi-car-front me-1"></i> Transport
                                        </button>
                                        <button class="btn btn-outline-gold btn-sm px-3 border-0"
                                            onclick="requestSupport('armor')">
                                            <i class="bi bi-shield-lock me-1"></i> Armor Check
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($bookings)): ?>
                        <div class="card glass-panel p-5 text-center" style="border-radius: 6px;">
                            <i class="bi bi-calendar-x d-block fs-1 text-muted opacity-50 mb-3"></i>
                            <p class="text-muted">No bookings yet.</p>
                            <a href="index.php#rooms" class="btn btn-kingsman btn-sm mx-auto">Book Your First Stay</a>
                        </div>
                    <?php else: ?>
                        <div class="card glass-panel border-0 p-0 overflow-hidden mb-4" style="border-radius: 6px;">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0" style="background: transparent;">
                                    <thead>
                                        <tr class="text-muted"
                                            style="font-size: 0.7rem; letter-spacing: 1.5px; text-transform: uppercase;">
                                            <th class="ps-4 py-3">Reference</th>
                                            <th class="py-3">Suite</th>
                                            <th class="py-3">Check-in</th>
                                            <th class="py-3">Check-out</th>
                                            <th class="py-3">Status</th>
                                            <th class="text-end pe-4 py-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking):
                                            $status_colors = [
                                                'confirmed' => 'success',
                                                'pending' => 'warning',
                                                'checked_in' => 'info',
                                                'checked_out' => 'secondary',
                                                'cancelled' => 'danger'
                                            ];
                                            $b_color = $status_colors[$booking['status']] ?? 'secondary';
                                            ?>
                                            <tr>
                                                <td class="gold-text fw-bold ps-4 py-3">
                                                    <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                                </td>
                                                <td class="py-3 small"><?php echo htmlspecialchars($booking['type_name']); ?>
                                                </td>
                                                <td class="py-3 small">
                                                    <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?>
                                                </td>
                                                <td class="py-3 small">
                                                    <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                                                </td>
                                                <td class="py-3">
                                                    <span
                                                        class="badge rounded-pill bg-<?php echo $b_color; ?> bg-opacity-25 text-<?php echo $b_color; ?> small">
                                                        <?php echo strtoupper(str_replace('_', ' ', $booking['status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4 py-3">
                                                    <div class="d-flex justify-content-end gap-1">
                                                        <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                                            <button type="button"
                                                                class="btn btn-outline-danger btn-sm border-0 px-2"
                                                                data-bs-toggle="modal" data-bs-target="#cancelModal"
                                                                data-booking-id="<?php echo $booking['id']; ?>"
                                                                data-booking-ref="<?php echo $booking['booking_reference']; ?>"
                                                                title="Cancel">
                                                                <i class="bi bi-x-circle fs-6"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <a href="user/print_receipt.php?id=<?php echo $booking['id']; ?>"
                                                            target="_blank" class="btn btn-outline-gold btn-sm border-0 px-2"
                                                            title="Print Receipt">
                                                            <i class="bi bi-printer fs-6"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Booking History -->
                    <div class="mt-4">
                        <h5 class="gold-text mb-3"><i class="bi bi-clock-history me-2"></i>Booking History</h5>
                        <div class="card glass-panel border-0 p-0 overflow-hidden" style="border-radius: 6px;">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0" style="background: transparent;">
                                    <thead>
                                        <tr class="text-muted"
                                            style="font-size: 0.7rem; letter-spacing: 1.5px; text-transform: uppercase;">
                                            <th class="ps-4 py-3">Reference</th>
                                            <th class="py-3">Suite</th>
                                            <th class="py-3">Stay Period</th>
                                            <th class="py-3">Status</th>
                                            <th class="pe-4 text-end py-3">Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $has_history = false;
                                        foreach ($bookings as $booking):
                                            if (in_array($booking['status'], ['checked_out', 'cancelled'])):
                                                $has_history = true;
                                                $h_color = ($booking['status'] == 'checked_out') ? 'secondary' : 'danger';
                                                ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold py-3">
                                                        <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                                    </td>
                                                    <td class="py-3 small">
                                                        <?php echo htmlspecialchars($booking['type_name']); ?>
                                                    </td>
                                                    <td class="py-3 small">
                                                        <?php echo date('M d', strtotime($booking['check_in_date'])); ?> —
                                                        <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                                                    </td>
                                                    <td class="py-3">
                                                        <span
                                                            class="badge rounded-pill bg-<?php echo $h_color; ?> bg-opacity-25 text-<?php echo $h_color; ?> small">
                                                            <?php echo strtoupper(str_replace('_', ' ', $booking['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td class="pe-4 text-end py-3">
                                                        <a href="user/print_receipt.php?id=<?php echo $booking['id']; ?>"
                                                            target="_blank" class="btn btn-outline-gold btn-sm border-0 px-2">
                                                            <i class="bi bi-printer fs-6"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                            endif;
                                        endforeach;
                                        if (!$has_history):
                                            ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted small">No completed stays
                                                    yet.</td>
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
        <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
    </div>
</div>

<!-- Cancellation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-gold shadow-lg"
            style="background: rgba(10, 10, 10, 0.97); backdrop-filter: blur(20px); border-radius: 8px;">
            <div class="modal-header border-secondary border-opacity-10 pb-3">
                <h6 class="modal-title gold-text mb-0" style="letter-spacing: 2px; font-size: 0.8rem;">CANCEL
                    BOOKING</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="index.php" method="POST">
                <div class="modal-body py-4">
                    <p class="text-white-50 small mb-3">Are you sure you want to cancel booking <span id="cancelRef"
                            class="gold-text fw-bold"></span>?</p>
                    <input type="hidden" name="booking_id" id="cancelBookingId">
                    <input type="hidden" name="action" value="cancel_booking">
                    <div class="mb-0">
                        <label for="cancellation_reason" class="form-label text-muted"
                            style="font-size: 0.65rem; letter-spacing: 1.5px; text-transform: uppercase;">Reason</label>
                        <textarea class="form-control" name="cancellation_reason" id="cancellation_reason" rows="3"
                            placeholder="Why are you cancelling?" required style="font-size: 0.85rem;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-10 pt-3">
                    <button type="button" class="btn btn-sm px-3 text-muted" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-outline-danger btn-sm px-4">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var cancelModal = document.getElementById('cancelModal');
        if (cancelModal) {
            cancelModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var bookingId = button.getAttribute('data-booking-id');
                var bookingRef = button.getAttribute('data-booking-ref');

                var idInput = cancelModal.querySelector('#cancelBookingId');
                var refSpan = cancelModal.querySelector('#cancelRef');

                if (idInput) idInput.value = bookingId;
                if (refSpan) refSpan.textContent = '[' + bookingRef + ']';
            });
        }
    });

    function requestSupport(type) {
        const bookingId = "<?php echo $priority_booking['id'] ?? 0; ?>";
        if (!bookingId) return;

        Swal.fire({
            title: 'Operational Support',
            text: `Confirm request for Kingsman ${type.toUpperCase()}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'PROCEED',
            cancelButtonText: 'ABORT',
            background: '#1a1a1a',
            color: '#cda434',
            confirmButtonColor: '#cda434'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('booking_id', bookingId);
                formData.append('service_type', type);
                formData.append('details', `Automated request for guest in suite ${bookingId}.`);

                fetch('ajax/provision_request.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Deployed', data.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            Swal.fire('Failed', data.message, 'error');
                        }
                    });
            }
        });
    }
</script>