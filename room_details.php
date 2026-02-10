<?php
include 'includes/header.php';
require_once 'config/db.php';

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

$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$booking_url = isset($_SESSION['user_id']) ? "book_room.php?id=" . $room['id'] : "login.php";
?>

<div class="hero-section"
    style="min-height: 50vh; background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('assets/img/<?php echo htmlspecialchars($room['thumbnail_image']); ?>'); background-attachment: fixed; background-size: cover;">
    <div class="container text-center">
        <h1 class="display-2 text-uppercase mb-3">
            <?php echo htmlspecialchars($room['type_name']); ?>
        </h1>
        <p class="lead gold-text text-uppercase" style="letter-spacing: 5px;">Room Details</p>
    </div>
</div>

<div class="container py-5 mt-n5">
    <div class="row">
        <div class="col-md-7">
            <div class="card kingsman-card p-5 mb-4">
                <h3 class="mb-4">Description</h3>
                <p class="lead mb-4">
                    <?php echo htmlspecialchars($room['description']); ?>
                </p>
                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-people gold-text fs-4 me-3"></i>
                            <div>
                                <strong>
                                    <?php echo $room['max_capacity']; ?> Guests
                                </strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-shield-lock gold-text fs-4 me-3"></i>
                            <div>
                                <small class="text-muted d-block">Service Rating</small>
                                <strong>Exclusive / Private</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-wifi gold-text fs-4 me-3"></i>
                            <div>
                                <small class="text-muted d-block">Connectivity</small>
                                <strong>High-Speed / Secure</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-suit-spade gold-text fs-4 me-3"></i>
                            <div>
                                <small class="text-muted d-block">24/7 Priority</small>
                                <strong>Concierge Service</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-5 border-gold opacity-25">

                <h4 class="mb-4">Room Amenities</h4>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check2-circle gold-text me-2"></i> Soundproof walls and
                        reinforced security protocols.</li>
                    <li class="mb-2"><i class="bi bi-check2-circle gold-text me-2"></i> Private workspace with
                        high-speed internet.</li>
                    <li class="mb-2"><i class="bi bi-check2-circle gold-text me-2"></i> Premium in-room safe
                        (electronic access).</li>
                    <li class="mb-2"><i class="bi bi-check2-circle gold-text me-2"></i> State-of-the-art entertainment
                        system with
                        streaming services.</li>
                </ul>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card kingsman-card p-5 sticky-top" style="top: 100px;">
                <h3 class="gold-text mb-4">Reservation Cost</h3>
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <h2 class="display-4 mb-0">₱
                        <?php echo number_format($room['price_per_night'], 2); ?>
                    </h2>
                    to and from the theater of operations.</p>
                    <a href="<?php echo $booking_url; ?>" class="btn btn-kingsman w-100 py-3 mb-3">Initiate
                        Allocation</a>
                    <p class="text-center small text-muted"><i class="bi bi-lock me-1"></i> Data-encrypted booking
                        tunnel.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>