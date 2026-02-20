<?php
include 'includes/header.php';
require_once 'config/db.php';

$room_id = $_GET['id'] ?? null;
if (!$room_id) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT rt.*, 
                     (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id AND r.status = 'available') as available_count
                     FROM room_types rt WHERE rt.id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();
$is_available = $room['available_count'] > 0;

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
        <h1 class="display-1 text-uppercase mb-3 fw-bold" style="text-shadow: 2px 2px 10px rgba(0,0,0,0.8);">
            <?php echo htmlspecialchars($room['type_name']); ?>
        </h1>
        <p class="h4 gold-text text-uppercase mb-4"
            style="letter-spacing: 5px; text-shadow: 1px 1px 5px rgba(0,0,0,0.8);">Executive Suite Profile</p>
        <?php if ($is_available): ?>
            <span class="badge bg-success text-white p-3 px-5 shadow-lg fs-6 rounded-pill"
                style="letter-spacing: 2px; border: 1px solid rgba(255,255,255,0.2);">AVAILABLE FOR BOOKING</span>
        <?php else: ?>
            <span class="badge bg-danger text-white p-3 px-5 shadow-lg fs-6 rounded-pill"
                style="letter-spacing: 2px; border: 1px solid rgba(255,255,255,0.2);">CURRENTLY
                FULLY BOOKED</span>
        <?php endif; ?>
    </div>
</div>

<div class="container py-5 mt-n5 position-relative" style="z-index: 10;">
    <div class="row">
        <div class="col-md-7">
            <div class="card kingsman-card p-5 mb-4 glass-panel border-0 shadow-lg text-white">
                <h2 class="gold-text mb-4 text-uppercase fw-bold" style="letter-spacing: 2px;">Overview</h2>
                <p class="lead mb-5" style="line-height: 1.8; color: #e0e0e0;">
                    <?php echo htmlspecialchars($room['description']); ?>
                </p>
                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3 p-3 rounded"
                            style="background: rgba(255,255,255,0.05);">
                            <i class="bi bi-people gold-text fs-2 me-4"></i>
                            <div>
                                <small class="text-uppercase gold-text"
                                    style="font-size: 0.7rem; letter-spacing: 1px;">Capacity</small>
                                <strong class="d-block fs-5">
                                    <?php echo $room['max_capacity']; ?> Guests
                                </strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3 p-3 rounded"
                            style="background: rgba(255,255,255,0.05);">
                            <i class="bi bi-shield-lock gold-text fs-2 me-4"></i>
                            <div>
                                <small class="text-uppercase gold-text"
                                    style="font-size: 0.7rem; letter-spacing: 1px;">Service Rating</small>
                                <strong class="d-block fs-5">Exclusive / Private</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3 p-3 rounded"
                            style="background: rgba(255,255,255,0.05);">
                            <i class="bi bi-wifi gold-text fs-2 me-4"></i>
                            <div>
                                <small class="text-uppercase gold-text"
                                    style="font-size: 0.7rem; letter-spacing: 1px;">Connectivity</small>
                                <strong class="d-block fs-5">High-Speed Secure</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3 p-3 rounded"
                            style="background: rgba(255,255,255,0.05);">
                            <i class="bi bi-suit-spade gold-text fs-2 me-4"></i>
                            <div>
                                <small class="text-uppercase gold-text"
                                    style="font-size: 0.7rem; letter-spacing: 1px;">24/7 Priority</small>
                                <strong class="d-block fs-5">Concierge Service</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-5 border-gold opacity-25">

                <h3 class="gold-text mb-4 text-uppercase fw-bold" style="letter-spacing: 2px;">Suite Amenities</h3>
                <ul class="list-unstyled">
                    <li class="mb-3 fs-5 d-flex align-items-center"><i
                            class="bi bi-check2-circle gold-text me-3 fs-3"></i> <span
                            style="color: #e0e0e0;">Soundproof walls and
                            reinforced security protocols.</span></li>
                    <li class="mb-3 fs-5 d-flex align-items-center"><i
                            class="bi bi-check2-circle gold-text me-3 fs-3"></i> <span style="color: #e0e0e0;">Private
                            workspace with
                            high-speed internet.</span></li>
                    <li class="mb-3 fs-5 d-flex align-items-center"><i
                            class="bi bi-check2-circle gold-text me-3 fs-3"></i> <span style="color: #e0e0e0;">Premium
                            in-room safe
                            (electronic access).</span></li>
                    <li class="mb-3 fs-5 d-flex align-items-center"><i
                            class="bi bi-check2-circle gold-text me-3 fs-3"></i> <span
                            style="color: #e0e0e0;">State-of-the-art entertainment
                            system.</span></li>
                </ul>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card kingsman-card p-5 sticky-top glass-panel border-gold shadow-lg" style="top: 100px;">
                <h4 class="text-white mb-4 text-uppercase" style="letter-spacing: 2px;">Reservation Cost</h4>
                <div class="mb-5 border-bottom border-secondary pb-4">
                    <h2 class="display-3 gold-text fw-bold mb-0">
                        ₱<?php echo number_format($room['price_per_night'], 2); ?></h2>
                    <span class="text-muted text-uppercase" style="letter-spacing: 1px;">Per Night</span>
                </div>


                <div class="d-grid gap-3">
                    <?php if ($is_available): ?>
                        <a href="<?php echo $booking_url; ?>" class="btn btn-kingsman py-4 fs-5 text-uppercase fw-bold"
                            style="letter-spacing: 2px;">Initiate
                            Allocation</a>
                        <p class="text-center small text-muted mt-2"><i
                                class="bi bi-shield-check gold-text me-1 fs-5 align-middle"></i> 256-bit encrypted booking
                            tunnel.
                        </p>
                    <?php else: ?>
                        <button class="btn btn-secondary py-4 fs-5 text-uppercase fw-bold" disabled
                            style="letter-spacing: 2px;">Suite Fully Booked</button>
                        <p class="text-center small text-danger mt-2"><i class="bi bi-x-circle me-1"></i> No suites of this
                            category are currently available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Upgrade room details visuals */
    .glass-panel {
        background: rgba(20, 20, 20, 0.85) !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
    }
</style>

<?php include 'includes/footer.php'; ?>