-- ============================================
-- Smart Parking Management System - Database
-- ============================================

CREATE DATABASE IF NOT EXISTS parking_system;
USE parking_system;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ADMIN TABLE
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL DEFAULT 'Administrator',
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (email: admin@parking.com, password: admin123)
INSERT INTO admin (name, email, password)
VALUES ('Administrator', 'admin@parking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE id=id;

-- LOCATIONS TABLE
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL,
    rate_per_hour INT DEFAULT 20,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SLOTS TABLE
CREATE TABLE IF NOT EXISTS slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    slot_number VARCHAR(20) NOT NULL,
    status ENUM('available','booked','maintenance') DEFAULT 'available',
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- VEHICLES TABLE
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    type VARCHAR(20) DEFAULT 'Car',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- PAYMENTS TABLE
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    method VARCHAR(20) DEFAULT 'UPI',
    reference_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- BOOKINGS TABLE
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    location_id INT NOT NULL,
    slot_id INT NOT NULL,
    hours INT NOT NULL,
    start_time DATETIME NULL DEFAULT NULL,
    amount INT NOT NULL,
    payment_id INT DEFAULT NULL,
    status ENUM('booked','cancelled','completed') DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (slot_id) REFERENCES slots(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);

-- CONTACT MESSAGES TABLE
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


INSERT INTO locations (location_name, rate_per_hour) VALUES
('Central Mall Parking', 30),
('Airport Terminal Parking', 50),
('Railway Station Parking', 20),
('City Center Parking', 25)
ON DUPLICATE KEY UPDATE id=id;

-- Slots for location 1
INSERT INTO slots (location_id, slot_number, status) VALUES
(1,'A1','available'),(1,'A2','available'),(1,'A3','available'),(1,'A4','available'),(1,'A5','available'),
(1,'B1','available'),(1,'B2','available'),(1,'B3','available'),(1,'B4','available'),(1,'B5','available'),
(1,'C1','available'),(1,'C2','available'),(1,'C3','available'),(1,'C4','available'),(1,'C5','available');

-- Slots for location 2
INSERT INTO slots (location_id, slot_number, status) VALUES
(2,'P1','available'),(2,'P2','available'),(2,'P3','available'),(2,'P4','available'),(2,'P5','available'),
(2,'P6','available'),(2,'P7','available'),(2,'P8','available'),(2,'P9','available'),(2,'P10','available');

-- Slots for location 3
INSERT INTO slots (location_id, slot_number, status) VALUES
(3,'R1','available'),(3,'R2','available'),(3,'R3','available'),(3,'R4','available'),(3,'R5','available');

-- Slots for location 4
INSERT INTO slots (location_id, slot_number, status) VALUES
(4,'CC1','available'),(4,'CC2','available'),(4,'CC3','available'),(4,'CC4','available'),(4,'CC5','available');
