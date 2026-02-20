<?php
require_once 'config/db.php';

echo "Initializing Professional Hospitality Data Seeding...\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    $tables = ['notifications', 'payments', 'bookings', 'rooms', 'room_types', 'users', 'site_settings', 'contact_messages'];
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
        echo "Clearing $table...\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Seeding User Identities...\n";
    $pass = password_hash('password123', PASSWORD_DEFAULT);

    $pdo->prepare("INSERT INTO users (firstname, lastname, email, password, phone, role, account_status, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute(['Robert', 'Binarao', 'admin@kingsman.com', $pass, '09123456789', 'admin', 'active', 1]);
    $guests = [
        ['James', 'Robinson', 'james.r@gmail.com'],
        ['Sarah', 'Jenkins', 'sarah.j@hotmail.com'],
        ['Michael', 'Chen', 'm.chen@outlook.com'],
        ['Emily', 'Davis', 'emily.d@gmail.com'],
        ['Robert', 'Wilson', 'r.wilson@yahoo.com'],
        ['Linda', 'Martinez', 'linda.m@gmail.com'],
        ['David', 'Anderson', 'david.a@gmail.com'],
        ['Jennifer', 'Thomas', 'j.thomas@gmail.com'],
        ['Charles', 'Hernandez', 'charles.h@gmail.com'],
        ['Jessica', 'Moore', 'jessica.m@gmail.com']
    ];

    foreach ($guests as $guest) {
        $pdo->prepare("INSERT INTO users (firstname, lastname, email, password, phone, role, account_status, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$guest[0], $guest[1], $guest[2], $pass, '09' . rand(100000000, 999999999), 'user', 'active', 1]);
    }

    echo "Seeding Room Categories...\n";
    $room_types = [
        ['The Windsor Suite', 'Pure elegance. Perfect for the modern connoisseur of fine living.', 15500.00, 2, '/assets/img/arthur.jpg'],
        ['The Mayfair Quarters', 'Sharp, sophisticated, and incredibly comfortable for business elites.', 12000.00, 2, '/assets/img/galahad.jpg'],
        ['The Savoy Penthouse', 'High-tech amenities and panoramic city views from the highest point.', 45000.00, 4, '/assets/img/hero.jpg'],
        ['The Chelsea Deluxe', 'Experience refined luxury with a private balcony overlooking our botanical gardens.', 8500.00, 2, '/assets/img/merlin.jpg'],
        ['The Westminster Executive', 'Designed for the modern professional, offering panoramic city views and a high-end workstation.', 12500.00, 2, '/assets/img/hero.jpg'],
        ['The Kensington Family Suite', 'Spacious and inclusive. Two connected rooms with a shared lounge area for the perfect family getaway.', 18000.00, 5, '/assets/img/galahad.jpg']
    ];

    foreach ($room_types as $rt) {
        $pdo->prepare("INSERT INTO room_types (type_name, description, price_per_night, max_capacity, thumbnail_image) VALUES (?, ?, ?, ?, ?)")
            ->execute([$rt[0], $rt[1], $rt[2], $rt[3], $rt[4]]);
    }

    echo "Seeding Physical Rooms...\n";
    $rt_ids = $pdo->query("SELECT id FROM room_types")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rt_ids as $index => $rt_id) {
        $floor = $index + 1;
        for ($i = 1; $i <= 5; $i++) {
            $room_num = ($floor * 100) + $i;
            $statuses = ['available', 'available', 'available', 'cleaning', 'maintenance'];
            $status = $statuses[array_rand($statuses)];
            $pdo->prepare("INSERT INTO rooms (room_number, room_type_id, status) VALUES (?, ?, ?)")
                ->execute([$room_num, $rt_id, $status]);
        }
    }

    echo "Configuring Hospitality CMS...\n";
    $settings = [
        ['hero_title', 'The Art of Hospitality'],
        ['hero_subtitle', 'Experience the pinnacle of discrete luxury and personalized service at the Kingsman Hotel.'],
        ['about_title', 'Our Legacy of Excellence'],
        ['about_text', 'For over a century, the Kingsman has been the destination of choice for detail-oriented travelers. We combine classic elegance with modern operational efficiency.'],
        ['about_img', '/assets/img/arthur.jpg']
    ];

    foreach ($settings as $s) {
        $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)")
            ->execute([$s[0], $s[1]]);
    }

    echo "Hydrating Operational Flow (Bookings)...\n";
    $user_ids = $pdo->query("SELECT id FROM users WHERE role = 'user'")->fetchAll(PDO::FETCH_COLUMN);
    $room_ids = $pdo->query("SELECT id FROM rooms")->fetchAll(PDO::FETCH_COLUMN);

    for ($i = 0; $i < 25; $i++) {
        $user_id = $user_ids[array_rand($user_ids)];
        $room_id = $room_ids[array_rand($room_ids)];
        $ref = 'BK-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $status_pool = ['checked_out', 'checked_out', 'checked_out', 'checked_in', 'confirmed', 'pending', 'cancelled'];
        $status = $status_pool[array_rand($status_pool)];

        $day_diff = rand(1, 14);
        $check_in = date('Y-m-d', strtotime("-$day_diff days"));
        $check_out = date('Y-m-d', strtotime($check_in . " + " . rand(1, 5) . " days"));

        $pdo->prepare("INSERT INTO bookings (booking_reference, user_id, room_id, check_in_date, check_out_date, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$ref, $user_id, $room_id, $check_in, $check_out, rand(100, 1500), $status]);
    }

    echo "\nProfessional Seeding Complete. The Property is Fully Operational.\n";

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
?>