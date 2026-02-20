<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$root_path = '../';
$base_path = '';

if ($current_dir == 'kingsman') {
    $root_path = '';
}
?>
<div class="sidebar border-end border-gold p-4 shadow-lg"
    style="width: 280px; min-height: 100vh; background: linear-gradient(180deg, #111 0%, #050505 100%); flex-shrink: 0;">
    <div class="text-center mb-5 mt-2">
        <h4 class="gold-text mb-0 text-uppercase" style="letter-spacing: 4px; font-weight: 900;">KINGSMAN</h4>
        <div style="width: 40px; height: 2px; background-color: var(--primary-gold); margin: 10px auto;"></div>
        <p class="small text-muted text-uppercase"
            style="letter-spacing: 2px; font-size: 0.65rem; color: #666 !important;">
            <?php echo $is_admin ? 'Administration' : 'Guest Portal'; ?>
        </p>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a href="<?php echo $root_path; ?>dashboard.php"
                class="nav-link <?php echo ($current_page == 'dashboard.php' || $current_page == 'index.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        <?php if ($is_admin): ?>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/cms.php"
                    class="nav-link <?php echo ($current_page == 'cms.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-pencil-square me-2"></i> Edit Welcome Page
                </a>
            </li>

            <li class="nav-item mt-3 mb-1">
                <span class="text-muted small text-uppercase ps-3"
                    style="font-size: 0.6rem; letter-spacing: 1.5px;">Reservations</span>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/bookings.php"
                    class="nav-link <?php echo ($current_page == 'bookings.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-calendar-check me-2"></i> Bookings
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/create_reservation.php"
                    class="nav-link <?php echo ($current_page == 'create_reservation.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-plus-circle me-2"></i> New Reservation
                </a>
            </li>

            <li class="nav-item mt-3 mb-1">
                <span class="text-muted small text-uppercase ps-3" style="font-size: 0.6rem; letter-spacing: 1.5px;">User
                    Management</span>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/guests.php"
                    class="nav-link <?php echo ($current_page == 'guests.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-people me-2"></i> Guest Registry
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/admins.php"
                    class="nav-link <?php echo ($current_page == 'admins.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-shield-lock me-2"></i> Admin Agents
                </a>
            </li>

            <li class="nav-item mt-3 mb-1">
                <span class="text-muted small text-uppercase ps-3"
                    style="font-size: 0.6rem; letter-spacing: 1.5px;">Infrastructure</span>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/rooms.php"
                    class="nav-link <?php echo ($current_page == 'rooms.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-door-open me-2"></i> Suite Categories
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/inventory.php"
                    class="nav-link <?php echo ($current_page == 'inventory.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-grid-3x3-gap me-2"></i> Operational Inventory
                </a>
            </li>

            <li class="nav-item mt-3 mb-1">
                <span class="text-muted small text-uppercase ps-3"
                    style="font-size: 0.6rem; letter-spacing: 1.5px;">Intelligence</span>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/messages.php"
                    class="nav-link <?php echo ($current_page == 'messages.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-envelope-paper me-2"></i> Intel Messages
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>admin/reports.php"
                    class="nav-link <?php echo ($current_page == 'reports.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-bar-chart me-2"></i> Strategic Reports
                </a>
            </li>

            <li class="nav-item mt-4 pt-3 border-top border-secondary">
                <a href="<?php echo $root_path; ?>admin/profile.php"
                    class="nav-link <?php echo ($current_page == 'profile.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-person-gear me-2"></i> Personal Settings
                </a>
            </li>
        <?php else: ?>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>user/settings.php"
                    class="nav-link <?php echo ($current_page == 'settings.php') ? 'gold-text active fw-bold' : 'text-white'; ?>">
                    <i class="bi bi-person-gear me-2"></i> Profile Settings
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="<?php echo $root_path; ?>index.php#rooms" class="nav-link text-white">
                    <i class="bi bi-plus-circle me-2"></i> New Booking
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-item mt-4">
            <a href="<?php echo $root_path; ?>logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>