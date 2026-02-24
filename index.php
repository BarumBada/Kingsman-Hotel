<?php
include 'includes/header.php';
require_once 'config/db.php';

$settings = [];
$stmt = $pdo->query("SELECT * FROM site_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<section class="hero-section">
    <div class="container text-center reveal">
        <h1 class="display-1 text-uppercase mb-4 fw-bold"
            style="letter-spacing: 5px; text-shadow: 0 4px 15px rgba(0,0,0,0.8);">
            <?php echo htmlspecialchars($settings['hero_title']); ?>
        </h1>
        <p class="lead mb-5 text-uppercase reveal-1" style="letter-spacing: 4px; opacity: 0.9;">
            <?php echo htmlspecialchars($settings['hero_subtitle']); ?>
        </p>
        <div class="reveal-2">
            <a href="#rooms" class="btn btn-kingsman btn-lg px-5">Explore Our Suites</a>
        </div>
    </div>
</section>

<section id="rooms" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4">Our Luxury Suites</h2>
            <div style="width: 100px; height: 3px; background-color: var(--primary-gold); margin: 20px auto;"></div>
        </div>

        <div class="row g-4">
            <?php
            require_once 'config/db.php';
            $stmt = $pdo->query("SELECT rt.*, 
                                 (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id AND r.status = 'available') as available_count
                                 FROM room_types rt WHERE rt.status = 'active'");
            $i = 1;
            while ($row = $stmt->fetch()):
                $is_available = $row['available_count'] > 0;
                $reveal_class = "reveal reveal-" . min($i, 5);
                $i++;
                ?>
                <div class="col-md-4 <?php echo $reveal_class; ?>">
                    <div class="card kingsman-card h-100 border-0 shadow-lg">
                        <div class="position-relative overflow-hidden">
                            <img src="assets/img/<?php echo htmlspecialchars($row['thumbnail_image']); ?>"
                                class="card-img-top" alt="<?php echo htmlspecialchars($row['type_name']); ?>"
                                style="height: 280px; object-fit: cover; background-color: #222;">
                            <div class="position-absolute top-0 end-0 p-3">
                                <?php if ($is_available): ?>
                                    <span class="badge bg-success bg-opacity-75 backdrop-blur px-3 py-2 small">AVAILABLE</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-75 backdrop-blur px-3 py-2 small">FULLY
                                        BOOKED</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body text-center p-4">
                            <h3 class="h4 gold-text text-uppercase mb-3" style="letter-spacing: 1px;">
                                <a href="room_details.php?id=<?php echo $row['id']; ?>"
                                    class="text-decoration-none gold-text"><?php echo htmlspecialchars($row['type_name']); ?></a>
                            </h3>
                            <p class="text-white-50 small mb-4"><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="mb-4">
                                <span class="text-muted small text-uppercase fw-bold d-block"
                                    style="font-size: 0.6rem; letter-spacing: 2px;">Rate per Night</span>
                                <span class="h3 fw-bold">₱<?php echo number_format($row['price_per_night'], 2); ?></span>
                            </div>
                            <div class="d-grid gap-2">
                                <?php if ($is_available): ?>
                                    <?php $booking_url = isset($_SESSION['user_id']) ? "book_room.php?id=" . $row['id'] : "login.php"; ?>
                                    <a href="<?php echo $booking_url; ?>" class="btn btn-kingsman py-2">Book Now</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary text-uppercase py-2" disabled
                                        style="letter-spacing: 2px;">Fully Occupied</button>
                                <?php endif; ?>
                                <a href="room_details.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-outline-secondary btn-sm text-uppercase py-1 border-0"
                                    style="letter-spacing: 2px; opacity: 0.7;">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4">Curated Experiences</h2>
            <p class="">More than hospitality. A lifestyle of privacy and excellence.</p>
            <div style="width: 80px; height: 2px; background-color: var(--primary-gold); margin: 20px auto;"></div>
        </div>
        <div class="row g-4">
            <div class="col-md-3 reveal reveal-1">
                <div class="text-center p-4 kingsman-card h-100">
                    <i class="bi bi-scissors gold-text display-4 mb-3 d-block"></i>
                    <h4 class="h5 text-uppercase mb-3" style="letter-spacing: 1px;">Bespoke Tailoring</h4>
                    <p class="text-white-50 small">Access to our world-class tailoring service directly from your suite.
                    </p>
                </div>
            </div>
            <div class="col-md-3 reveal reveal-2">
                <div class="text-center p-4 kingsman-card h-100">
                    <i class="bi bi-shield-lock gold-text display-4 mb-3 d-block"></i>
                    <h4 class="h5 text-uppercase mb-3" style="letter-spacing: 1px;">Absolute Privacy</h4>
                    <p class="text-white-50 small">Our staff are trained in the highest standards of guest
                        confidentiality.</p>
                </div>
            </div>
            <div class="col-md-3 reveal reveal-3">
                <div class="text-center p-4 kingsman-card h-100">
                    <i class="bi bi-cup-hot gold-text display-4 mb-3 d-block"></i>
                    <h4 class="h5 text-uppercase mb-3" style="letter-spacing: 1px;">Executive Lounge</h4>
                    <p class="text-white-50 small">Exclusive access to our high-speed connectivity hubs and premium bar.
                    </p>
                </div>
            </div>
            <div class="col-md-3 reveal reveal-4">
                <div class="text-center p-4 kingsman-card h-100">
                    <i class="bi bi-car-front gold-text display-4 mb-3 d-block"></i>
                    <h4 class="h5 text-uppercase mb-3" style="letter-spacing: 1px;">Premium Concierge</h4>
                    <p class="text-white-50 small">Our private fleet is available for all guest arrivals and excursions.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5"
    style="background: linear-gradient(rgba(10,10,10,0.9), rgba(10,10,10,0.9)), url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=1920'); background-attachment: fixed; background-size: cover;">
    <div class="container text-center py-5">
        <i class="bi bi-quote gold-text display-1"></i>
        <h3 class="font-italic mb-4 text-white" style="font-family: var(--font-heading);">"Experience the Art of Fine
            Hospitality. The Kingsman Hotel is where elegance meets precision."</h3>
        <p class="gold-text fw-bold text-uppercase" style="letter-spacing: 2px;">— Verified Guest —</p>
    </div>
</section>


<section class="py-5" style="background-color: var(--bg-dark-gray);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <h2 class="mb-4"><?php echo htmlspecialchars($settings['about_title']); ?></h2>
                <p class=" lead"><?php echo htmlspecialchars($settings['about_text']); ?></p>
                <div class="mt-4">
                    <div class="d-flex mb-3">
                        <span class="gold-text me-3"><i class="bi bi-shield-check"></i></span>
                        <p class="mb-0">Personalized service and absolute privacy.</p>
                    </div>
                    <div class="d-flex mb-3">
                        <span class="gold-text me-3"><i class="bi bi-clock"></i></span>
                        <p class="mb-0">24/7 Concierge and tailoring service.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border border-gold p-2">
                    <img src="<?php echo htmlspecialchars($settings['about_img']); ?>" alt="Bespoke Service"
                        class="img-fluid w-100"
                        style="height: 400px; object-fit: cover; border: 1px solid var(--primary-gold);">
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>