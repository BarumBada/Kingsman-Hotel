<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$base_path = ($current_dir == 'admin' || $current_dir == 'user') ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kingsman Hotel</title>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:wght@700;900&display=swap"
        rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/kingsman.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-kingsman sticky-top">
        <div class="container">
            <a class="navbar-brand gold-text fw-bold fs-3" href="<?php echo $base_path; ?>index.php">KINGSMAN</a>
            <button class="navbar-toggler border-gold" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>index.php">Welcome</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>index.php#rooms">Rooms</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>contact.php">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link gold-text fw-bold"
                                href="<?php echo $base_path; ?>dashboard.php">Dashboard</a></li>
                        <li class="nav-item ms-lg-3"><a class="btn btn-kingsman"
                                href="<?php echo $base_path; ?>logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $base_path; ?>login.php">Login</a></li>
                        <li class="nav-item ms-lg-3"><a class="btn btn-kingsman"
                                href="<?php echo $base_path; ?>register.php">Book Now</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>