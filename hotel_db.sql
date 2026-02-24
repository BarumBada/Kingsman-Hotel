CREATE DATABASE IF NOT EXISTS hotel_db;
USE hotel_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    middlename VARCHAR(50) DEFAULT NULL,
    lastname VARCHAR(50) NOT NULL,
    suffixname VARCHAR(10) DEFAULT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    otp_code VARCHAR(6) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    account_status ENUM('active', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_night DECIMAL(10, 2) NOT NULL,
    max_capacity INT NOT NULL,
    status ENUM('active', 'archived') DEFAULT 'active',
    thumbnail_image VARCHAR(255) DEFAULT 'default_room.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type_id INT NOT NULL,
    status ENUM('available', 'maintenance') DEFAULT 'available',
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    subject VARCHAR(200),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO site_settings (setting_key, setting_value) VALUES 
('hero_title', 'The Art of Hospitality'), 
('hero_subtitle', 'Experience the pinnacle of discrete luxury and personalized service at the Kingsman Hotel.'), 
('about_title', 'Our Legacy of Excellence'), 
('about_text', 'For over a century, the Kingsman has been the destination of choice for detail-oriented travelers. We combine classic elegance with modern operational efficiency.'),
('about_img', '/assets/img/arthur.jpg');

-- Seed User Identities (Password: password123)
-- Admin: Robert Binarao (admin@kingsman.com)
INSERT INTO users (firstname, lastname, email, password, phone, role, account_status, is_verified) VALUES 
('Robert', 'Binarao', 'admin@kingsman.com', '$2y$10$QpG1fWREmGgU4Z2iYpP9O.F6uX8yqN9Y8wK5N2U7E1G6E2O3R4Y5S', '09123456789', 'admin', 'active', 1);

-- Seed Standard Guests
INSERT INTO users (firstname, lastname, email, password, phone, role, account_status, is_verified) VALUES 
('James', 'Robinson', 'james.r@gmail.com', '$2y$10$QpG1fWREmGgU4Z2iYpP9O.F6uX8yqN9Y8wK5N2U7E1G6E2O3R4Y5S', '09123456701', 'user', 'active', 1),
('Sarah', 'Jenkins', 'sarah.j@hotmail.com', '$2y$10$QpG1fWREmGgU4Z2iYpP9O.F6uX8yqN9Y8wK5N2U7E1G6E2O3R4Y5S', '09123456702', 'user', 'active', 1),
('Michael', 'Chen', 'm.chen@outlook.com', '$2y$10$QpG1fWREmGgU4Z2iYpP9O.F6uX8yqN9Y8wK5N2U7E1G6E2O3R4Y5S', '09123456703', 'user', 'active', 1);

-- Seed Room Categories
INSERT INTO room_types (type_name, description, price_per_night, max_capacity, thumbnail_image) VALUES 
('The Windsor Suite', 'Pure elegance. Perfect for the modern connoisseur of fine living.', 15500.00, 2, 'arthur.jpg'),
('The Mayfair Quarters', 'Sharp, sophisticated, and incredibly comfortable for business elites.', 12000.00, 2, 'galahad.jpg'),
('The Savoy Penthouse', 'High-tech amenities and panoramic city views from the highest point.', 45000.00, 4, 'hero.jpg'),
('The Chelsea Deluxe', 'Experience refined luxury with a private balcony overlooking our botanical gardens.', 8500.00, 2, 'merlin.jpg'),
('The Westminster Executive', 'Designed for the modern professional with panoramic city views and a high-end workstation.', 12500.00, 2, 'hero.jpg'),
('The Kensington Family Suite', 'Spacious and inclusive. Two connected rooms with a shared lounge area.', 18000.00, 5, 'galahad.jpg');

-- Seed Physical Rooms
INSERT INTO rooms (room_number, room_type_id, status) VALUES 
('101', 1, 'available'), ('102', 1, 'available'), ('103', 1, 'maintenance'),
('201', 2, 'available'), ('202', 2, 'occupied'), ('203', 2, 'available'),
('301', 3, 'available'), ('302', 3, 'available'),
('401', 4, 'available'), ('402', 4, 'occupied'),
('501', 5, 'available'), ('502', 5, 'available'),
('601', 6, 'available'), ('602', 6, 'available');
