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
    <div class="container text-center">
        <h1 class="display-1 text-uppercase mb-4"><?php echo htmlspecialchars($settings['hero_title']); ?></h1>
        <p class="lead mb-5 text-uppercase" style="letter-spacing: 3px;">
            <?php echo htmlspecialchars($settings['hero_subtitle']); ?>
        </p>
        <a href="#rooms" class="btn btn-kingsman btn-lg">Explore Our Suites</a>
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
            while ($row = $stmt->fetch()):
                $is_available = $row['available_count'] > 0;
                ?>
                <div class="col-md-4 reveal">
                    <div class="card kingsman-card h-100">
                        <img src="assets/img/<?php echo htmlspecialchars($row['thumbnail_image']); ?>" class="card-img-top"
                            alt="<?php echo htmlspecialchars($row['type_name']); ?>"
                            style="height: 250px; object-fit: cover; background-color: #222;">
                        <div class="card-body text-center p-4">
                            <h3 class="h4 gold-text text-uppercase position-relative d-inline-block">
                                <a href="room_details.php?id=<?php echo $row['id']; ?>"
                                    class="text-decoration-none gold-text"><?php echo htmlspecialchars($row['type_name']); ?></a>
                                <?php if ($is_available): ?>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle"
                                        style="width: 10px; height: 10px;" title="Available"></span>
                                <?php else: ?>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"
                                        style="width: 10px; height: 10px;" title="Fully Booked"></span>
                                <?php endif; ?>
                            </h3>
                            <p class=" small"><?php echo htmlspecialchars($row['description']); ?></p>
                            <h4 class="mb-3">₱<?php echo number_format($row['price_per_night'], 2); ?> <small
                                    class=" fs-6">/ night</small></h4>
                            <div class="d-grid gap-2">
                                <?php if ($is_available): ?>
                                    <?php $booking_url = isset($_SESSION['user_id']) ? "book_room.php?id=" . $row['id'] : "login.php"; ?>
                                    <a href="<?php echo $booking_url; ?>" class="btn btn-kingsman">Book Now</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary text-uppercase" disabled style="letter-spacing: 2px;">Fully
                                        Booked</button>
                                <?php endif; ?>
                                <a href="room_details.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-outline-secondary btn-sm text-uppercase"
                                    style="letter-spacing: 2px;">View Details</a>
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
            <div class="col-md-3">
                <div class="text-center p-4">
                    <i class="bi bi-scissors gold-text display-4 mb-3 d-block"></i>
                    <h4 class="h5">Bespoke Tailoring</h4>
                    <p class=" small">Access to our world-class tailoring service directly from your suite.
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-4">
                    <i class="bi bi-shield-lock gold-text display-4 mb-3 d-block"></i>
                    <h4 class="h5">Absolute Privacy</h4>
                    <p class=" small">Our staff are trained in the highest protocols of guest confidentiality.
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-4">
                    <i class="bi bi-cup-hot gold-text display-4 mb-3 d-block"></i>
                    <h4 class="h5">The Executive Lounge</h4>
                    <p class=" small">Exclusive access to our high-speed connectivity hubs and premium bar.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-4">
                    <i class="bi bi-car-front gold-text display-4 mb-3 d-block"></i>
                    <h4 class="h5">Premium Concierge</h4>
                    <p class=" small">Our private fleet is available for all guest arrivals and excursions.</p>
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