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

    try {
        $pdo->beginTransaction();

        // Fetch booking details first to get the room_id
        $b_stmt = $pdo->prepare("SELECT b.*, u.email, u.firstname, rt.type_name, r.room_number 
                                 FROM bookings b 
                                 JOIN users u ON b.user_id = u.id 
                                 JOIN rooms r ON b.room_id = r.id
                                 JOIN room_types rt ON r.room_type_id = rt.id
                                 WHERE b.id = ?");
        $b_stmt->execute([$id]);
        $booking = $b_stmt->fetch();

        if (!$booking) {
            throw new Exception("Error: Reservation not found.");
        }

        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);

        // AUTO-UPDATE ROOM STATUS
        if ($new_status == 'checked_in') {
            $r_stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            $r_stmt->execute([$booking['room_id']]);
            $room_notif = " assigned to Room " . $booking['room_number'];
        } elseif ($new_status == 'checked_out') {
            $r_stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
            $r_stmt->execute([$booking['room_id']]);
            $room_notif = "";
        } else {
            $room_notif = "";
        }

        $notif_msg = "Booking status for " . $booking['booking_reference'] . " has been updated to: " . strtoupper($new_status) . $room_notif;
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Booking Update', ?)");
        $notif_stmt->execute([$booking['user_id'], $notif_msg]);

        // Send email when admin confirms a booking
        if ($new_status == 'confirmed' && $booking['email']) {
            require_once dirname(__DIR__) . '/includes/mail_helper.php';
            $email_body = "<h3>Reservation Confirmed!</h3>
            <p>Dear " . htmlspecialchars($booking['firstname']) . ",</p>
            <p>Great news! Your reservation for the <strong>" . htmlspecialchars($booking['type_name']) . "</strong> has been <strong>confirmed</strong> by our team.</p>
            <p><strong>Reference:</strong> " . $booking['booking_reference'] . "</p>
            <p><strong>Check-in:</strong> " . $booking['check_in_date'] . "</p>
            <p><strong>Check-out:</strong> " . $booking['check_out_date'] . "</p>
            <p>We look forward to welcoming you.</p>";
            $branded_html = get_branded_template("Reservation Confirmed", $email_body);
            send_kingsman_mail($booking['email'], "Reservation Confirmed - " . $booking['booking_reference'], $branded_html);
        }

        // Special email for check-in to include room number
        if ($new_status == 'checked_in' && $booking['email']) {
            require_once dirname(__DIR__) . '/includes/mail_helper.php';
            $email_body = "<h3>Check-in Successful</h3>
            <p>Dear " . htmlspecialchars($booking['firstname']) . ",</p>
            <p>Your check-in has been completed successfully.</p>
            <p><strong>Reference:</strong> " . $booking['booking_reference'] . "</p>
            <p><strong>Your Assigned Room:</strong> <span style='font-size: 1.2rem; color: #DAA520; font-weight: bold;'>ROOM " . $booking['room_number'] . "</span></p>
            <p><strong>Check-out Date:</strong> " . $booking['check_out_date'] . "</p>
            <p>Thank you for choosing Kingsman Hotel.</p>";
            $branded_html = get_branded_template("Check-in Successful", $email_body);
            send_kingsman_mail($booking['email'], "Check-in Successful - " . $booking['booking_reference'], $branded_html);
        }

        $pdo->commit();
        header("Location: bookings.php?msg=updated");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Handler for Check-In Process
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_check_in'])) {
    $booking_id = $_POST['booking_id'];
    $new_room_id = $_POST['room_id'];

    try {
        $pdo->beginTransaction();

        // 1. Update room assignment if changed
        $u_stmt = $pdo->prepare("UPDATE bookings SET room_id = ? WHERE id = ?");
        $u_stmt->execute([$new_room_id, $booking_id]);

        // 2. Fetch fresh details for notifications
        $b_stmt = $pdo->prepare("SELECT b.*, u.email, u.firstname, r.room_number 
                                 FROM bookings b 
                                 JOIN users u ON b.user_id = u.id 
                                 JOIN rooms r ON b.room_id = r.id
                                 WHERE b.id = ?");
        $b_stmt->execute([$booking_id]);
        $booking = $b_stmt->fetch();

        // 3. Update booking status to checked_in
        $s_stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
        $s_stmt->execute([$booking_id]);

        // 4. Mark room as occupied
        $r_stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
        $r_stmt->execute([$new_room_id]);

        // 5. Notifications
        $notif_msg = "Guest Checked-In: " . $booking['booking_reference'] . " assigned to Room " . $booking['room_number'];
        $n_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Booking Update', ?)");
        $n_stmt->execute([$booking['user_id'], $notif_msg]);

        if ($booking['email']) {
            require_once dirname(__DIR__) . '/includes/mail_helper.php';
            $email_body = "<h3>Check-in Successful</h3>
            <p>Dear " . htmlspecialchars($booking['firstname']) . ",</p>
            <p>Your check-in has been completed successfully.</p>
            <p><strong>Reference:</strong> " . $booking['booking_reference'] . "</p>
            <p><strong>Your Assigned Room:</strong> <span style='font-size: 1.2rem; color: #DAA520; font-weight: bold;'>ROOM " . $booking['room_number'] . "</span></p>
            <p><strong>Check-out Date:</strong> " . $booking['check_out_date'] . "</p>
            <p>Thank you for choosing Kingsman Hotel.</p>";
            $branded_html = get_branded_template("Check-in Successful", $email_body);
            send_kingsman_mail($booking['email'], "Check-in Successful - " . $booking['booking_reference'], $branded_html);
        }

        $pdo->commit();
        header("Location: bookings.php?msg=updated");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        $error = "Check-in Failed: " . $e->getMessage();
    }
}

// Handler for Room Reassignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reassign_room'])) {
    $booking_id = $_POST['booking_id'];
    $new_room_id = $_POST['room_id'];

    try {
        $stmt = $pdo->prepare("UPDATE bookings SET room_id = ? WHERE id = ?");
        $stmt->execute([$new_room_id, $booking_id]);
        header("Location: bookings.php?msg=updated");
        exit();
    } catch (PDOException $e) {
        $error = "Reassignment failed: " . $e->getMessage();
    }
}

include dirname(__DIR__) . '/includes/header.php';

$where_clause = "";
$params = [];
if (isset($_GET['status_filter']) && !empty($_GET['status_filter'])) {
    $where_clause = " WHERE b.status = ?";
    $params[] = $_GET['status_filter'];
}

$stmt = $pdo->prepare("SELECT b.*, u.firstname, u.lastname, rt.type_name, r.room_number, r.room_type_id 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.id 
                     JOIN rooms r ON b.room_id = r.id
                     JOIN room_types rt ON r.room_type_id = rt.id 
                     " . $where_clause . "
                     ORDER BY b.created_at DESC");
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="d-flex" style="min-height: 100vh;">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <div class="p-4 p-lg-5 flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="gold-text"><i class="bi bi-calendar-event me-2"></i>Booking Management</h2>
                <div class="d-flex gap-2">
                    <form action="" method="GET" class="d-flex gap-2">
                        <select name="status_filter" class="form-select form-select-sm bg-dark text-white border-gold"
                            onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked_in" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'checked_in') ? 'selected' : ''; ?>>Checked In</option>
                            <option value="checked_out" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'checked_out') ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="cancelled" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </form>
                    <a href="create_reservation.php" class="btn btn-kingsman btn-sm">+ Add Reservation</a>
                </div>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                <div class="kingsman-alert success mb-4">Booking updated successfully.</div>
            <?php endif; ?>

            <div class="card kingsman-card glass-panel p-0 overflow-hidden border-0 shadow-lg">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0" id="bookingsTable">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th class="ps-4">Reference</th>
                                <th>Guest Identity</th>
                                <th>Suite Category</th>
                                <th>Arrival</th>
                                <th>Departure</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td class="ps-4 fw-bold gold-text">
                                        <?php if ($booking['status'] == 'confirmed'): ?>
                                            <span class="pulse-red d-inline-block me-2"
                                                style="width: 8px; height: 8px; border-radius: 50%;"></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['type_name']); ?></td>
                                    <td class="small"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?>
                                    </td>
                                    <td class="small"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                                    </td>
                                    <td class="small fw-bold">
                                        <span
                                            class="text-muted">#</span><?php echo htmlspecialchars($booking['room_number']); ?>
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
                                        if ($booking['status'] == 'pending')
                                            $badge_class = 'bg-warning text-dark';
                                        ?>
                                        <span
                                            class="badge rounded-pill <?php echo $badge_class; ?> text-white font-weight-bold">
                                            <?php echo strtoupper($booking['status']); ?>
                                        </span>
                                        <?php if ($booking['status'] == 'cancelled' && !empty($booking['cancellation_reason'])): ?>
                                            <div class="mt-1 small text-muted italic"
                                                style="font-size: 0.7rem; max-width: 150px; line-height: 1.2;">
                                                <i class="bi bi-info-circle me-1"></i>
                                                <?php echo htmlspecialchars($booking['cancellation_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-1">
                                            <a href="print_receipt.php?id=<?php echo $booking['id']; ?>" target="_blank"
                                                class="btn btn-outline-gold text-white btn-sm border-0 px-2"
                                                title="Print Receipt">
                                                <i class="bi bi-printer fs-6"></i>
                                            </a>
                                            <?php if (!in_array($booking['status'], ['cancelled', 'checked_out'])): ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-gold text-white btn-sm border-0 px-2"
                                                        type="button" data-bs-toggle="dropdown" title="Actions">
                                                        <i class="bi bi-three-dots-vertical fs-6"></i>
                                                    </button>
                                                    <ul
                                                        class="dropdown-menu dropdown-menu-dark shadow-lg border-gold dropdown-menu-end">
                                                        <li class="dropdown-header small text-uppercase gold-text"
                                                            style="font-size: 0.6rem; letter-spacing: 1px;">Booking
                                                            Actions
                                                        </li>
                                                        <?php if ($booking['status'] == 'pending'): ?>
                                                            <li><a class="dropdown-item py-2 small d-flex align-items-center text-success fw-bold"
                                                                    href="bookings.php?update_status=confirmed&id=<?php echo $booking['id']; ?>"
                                                                    onclick="return confirm('Confirm this reservation?')">
                                                                    <i class="bi bi-check-circle-fill me-2"></i> CONFIRM RESERVATION
                                                                </a></li>
                                                        <?php endif; ?>
                                                        <?php if ($booking['status'] == 'confirmed'): ?>
                                                            <li>
                                                                <button
                                                                    class="dropdown-item py-2 small d-flex align-items-center text-success fw-bold"
                                                                    onclick='openCheckInModal(<?php echo $booking['id']; ?>, <?php echo $booking['room_type_id']; ?>, "<?php echo $booking['room_number']; ?>", <?php echo $booking['room_id']; ?>)'>
                                                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                                                    CHECK-IN
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button
                                                                    class="dropdown-item py-2 small d-flex align-items-center text-info"
                                                                    onclick='openReassignModal(<?php echo $booking['id']; ?>, <?php echo $booking['room_type_id']; ?>, "<?php echo $booking['room_number']; ?>")'>
                                                                    <i class="bi bi-arrow-left-right me-2"></i> REASSIGN ROOM
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($booking['status'] == 'pending'): ?>
                                                            <li><a class="dropdown-item py-2 small d-flex align-items-center text-info"
                                                                    href="edit_booking.php?id=<?php echo $booking['id']; ?>">
                                                                    <i class="bi bi-pencil-square me-2"></i> EDIT BOOKING
                                                                </a></li>
                                                        <?php endif; ?>
                                                        <?php if ($booking['status'] == 'checked_in'): ?>
                                                            <li><a class="dropdown-item py-2 small d-flex align-items-center text-warning"
                                                                    href="bookings.php?update_status=checked_out&id=<?php echo $booking['id']; ?>">
                                                                    <i class="bi bi-box-arrow-right me-2"></i> CHECK-OUT
                                                                </a></li>
                                                        <?php endif; ?>
                                                        <?php if ($booking['status'] != 'cancelled' && $booking['status'] != 'checked_out'): ?>
                                                            <li>
                                                                <hr class="dropdown-divider border-secondary opacity-25">
                                                            </li>
                                                            <li><a class="dropdown-item py-2 small d-flex align-items-center text-danger"
                                                                    href="bookings.php?update_status=cancelled&id=<?php echo $booking['id']; ?>">
                                                                    <i class="bi bi-x-circle me-2"></i> CANCEL BOOKING
                                                                </a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No reservations active at the
                                        moment.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
    </div>
</div>

<!-- Check-In Process Modal -->
<div class="modal fade" id="checkInModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content kingsman-card border-gold glass-panel shadow-lg">
            <div class="modal-header border-gold">
                <h5 class="modal-title gold-text small text-uppercase" style="letter-spacing: 2px;">
                    <i class="bi bi-shield-check me-2"></i> Check-In Guest
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body py-4 px-4 text-center">
                    <input type="hidden" name="booking_id" id="checkin_booking_id">
                    <i class="bi bi-key-fill gold-text display-1 mb-3 d-block opacity-75"></i>
                    <p class="text-muted small mb-1 text-uppercase">Room Assignment</p>
                    <h5 class="text-white mb-4">Confirm the room selection for this guest.</h5>

                    <div class="text-start mb-3">
                        <label class="form-label text-muted small text-uppercase fw-bold">Assigned Room</label>
                        <select name="room_id" id="checkin_rooms_select"
                            class="form-select bg-dark text-white border-gold" required>
                            <option value="">Loading Rooms...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-gold">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="complete_check_in" class="btn btn-kingsman btn-sm px-4">Confirm
                        Check-In</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="reassignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content kingsman-card border-gold glass-panel shadow-lg">
            <div class="modal-header border-gold">
                <h5 class="modal-title gold-text small text-uppercase" style="letter-spacing: 2px;">
                    <i class="bi bi-arrow-left-right me-2"></i> Room Reassignment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body py-4">
                    <input type="hidden" name="booking_id" id="reassign_booking_id">
                    <div class="mb-4 text-center">
                        <p class="text-muted small mb-1">CURRENT ROOM</p>
                        <h4 class="gold-text" id="current_room_display">ROOM ---</h4>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small text-uppercase">Select New Room</label>
                        <select name="room_id" id="available_rooms_select"
                            class="form-select bg-dark text-white border-gold" required>
                            <option value="">Loading Rooms...</option>
                        </select>
                        <div class="form-text text-muted small mt-2">
                            Only currently available rooms of the same category are displayed.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-gold">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reassign_room" class="btn btn-kingsman btn-sm px-4">Confirm
                        Reassignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS & CSS -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        $('#bookingsTable').DataTable({
            "order": [],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search records..."
            },
            "pageLength": 10,
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-end'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        // Add kingsman styling to datatable elements after initialization
        setTimeout(() => {
            $('.dataTables_filter input').addClass('form-control form-control-sm bg-dark text-white border-gold ms-2').removeClass('form-control-sm').css('display', 'inline-block').css('width', 'auto');
            $('.dataTables_length select').addClass('form-select form-select-sm bg-dark text-white border-gold mx-2').removeClass('form-select-sm').css('display', 'inline-block').css('width', 'auto');
        }, 100);
    });

    function openCheckInModal(bookingId, typeId, currentRoomNum, currentRoomId) {
        document.getElementById('checkin_booking_id').value = bookingId;
        const select = document.getElementById('checkin_rooms_select');
        select.innerHTML = '<option value="">SEARCHING ROOMS...</option>';

        const myModal = new bootstrap.Modal(document.getElementById('checkInModal'));
        myModal.show();

        fetch(`ajax/get_available_rooms.php?type_id=${typeId}`)
            .then(response => response.json())
            .then(data => {
                select.innerHTML = '';
                // Add currently assigned room first if not in data (should be in data though as it's available)
                let currentExists = data.find(r => r.id == currentRoomId);
                if (!currentExists) {
                    const option = document.createElement('option');
                    option.value = currentRoomId;
                    option.text = 'Room ' + currentRoomNum + ' (Currently Assigned)';
                    option.selected = true;
                    select.appendChild(option);
                }

                data.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.text = 'Room ' + room.room_number + (room.id == currentRoomId ? ' (Currently Assigned)' : '');
                    if (room.id == currentRoomId) option.selected = true;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Room Search Failed:', error);
                select.innerHTML = '<option value="">SEARCH ERROR</option>';
            });
    }

    function openReassignModal(bookingId, typeId, currentRoom) {
        document.getElementById('reassign_booking_id').value = bookingId;
        document.getElementById('current_room_display').innerText = 'ROOM ' + currentRoom;

        const select = document.getElementById('available_rooms_select');
        select.innerHTML = '<option value="">SEARCHING ROOMS...</option>';

        const myModal = new bootstrap.Modal(document.getElementById('reassignModal'));
        myModal.show();

        fetch(`ajax/get_available_rooms.php?type_id=${typeId}`)
            .then(response => response.json())
            .then(data => {
                select.innerHTML = '<option value="">-- SELECT NEW ROOM --</option>';
                if (data.length === 0) {
                    select.innerHTML = '<option value="">NO OTHER ROOMS AVAILABLE</option>';
                } else {
                    data.forEach(room => {
                        const option = document.createElement('option');
                        option.value = room.id;
                        option.text = 'Room ' + room.room_number;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Inventory Scan Failed:', error);
                select.innerHTML = '<option value="">SCAN ERROR</option>';
            });
    }
</script>

<style>
    .gold-text {
        color: var(--primary-gold) !important;
    }

    .border-gold {
        border-color: var(--primary-gold) !important;
    }

    .kingsman-alert {
        background: rgba(218, 165, 32, 0.1);
        border-left: 4px solid var(--primary-gold);
        padding: 15px;
        color: #fff;
        border-radius: 4px;
    }

    .kingsman-alert.success {
        background: rgba(46, 204, 113, 0.1);
        border-left-color: #2ecc71;
    }

    .pulse-red {
        background: #ff4d4d;
        box-shadow: 0 0 0 0 rgba(255, 77, 77, 0.7);
        animation: pulse_red 1.5s infinite;
    }

    @keyframes pulse_red {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(255, 77, 77, 0.7);
        }

        70% {
            transform: scale(1);
            box-shadow: 0 0 0 6px rgba(255, 77, 77, 0);
        }

        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(255, 77, 77, 0);
        }
    }

    /* DataTable Overrides */
    .page-item.active .page-link {
        background-color: var(--primary-gold) !important;
        border-color: var(--primary-gold) !important;
        color: #000 !important;
    }

    .page-link {
        background-color: #1a1a1a;
        border-color: #333;
        color: var(--primary-gold);
    }

    .page-link:hover {
        background-color: #333;
        color: #fff;
        border-color: var(--primary-gold);
    }

    .dataTables_info,
    .dataTables_length,
    .dataTables_filter {
        color: #aaa !important;
    }
</style>
</body>

</html>