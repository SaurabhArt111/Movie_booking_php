-- Movie Booking Data
-- To import: mysql -u root -p < movie_booking.sql
-- (the line above is an instruction for your terminal, not SQL — it must not
-- appear unescaped as the first line of this file, or the import fails
-- immediately with a syntax error before anything else runs)

-- Movie Booking Data
mysql -u root -p


-- Recreate Database
DROP DATABASE IF EXISTS movie_booking;
CREATE DATABASE movie_booking;
USE movie_booking;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movies table
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(100),
    duration INT,
    rating DECIMAL(2,1),
    release_date DATE,
    poster_url VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Theaters table
CREATE TABLE theaters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    total_seats INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Shows table
CREATE TABLE shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    theater_id INT NOT NULL,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    total_seats INT NOT NULL,
    available_seats INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (theater_id) REFERENCES theaters(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    show_id INT,
    seats_booked INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
);

-- Booked seats table (NEW)
-- Records exactly which seat labels (e.g. "A1", "B7") are taken for a given
-- show, so the seat map can show real availability instead of just a count.
-- The UNIQUE constraint is what actually prevents two customers from ever
-- being sold the same seat, even if two bookings are submitted at the same
-- instant — the database itself rejects the second INSERT.
CREATE TABLE booked_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT NOT NULL,
    seat_label VARCHAR(10) NOT NULL,
    booking_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_show_seat (show_id, seat_label),
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Login attempts table (NEW)
-- Backs the login-throttling in config.php: after several failed attempts
-- for the same account+IP within the cooldown window, further attempts are
-- blocked for a while instead of being checked immediately (slows down
-- password-guessing).
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(191) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, attempted_at)
);

-- Insert sample users
-- Login: admin@gmail.com / saurabh123
INSERT INTO users (username, email, password, full_name, phone, role)
VALUES ('admin', 'admin@gmail.com','$2y$10$kwZiAbY.dE1n/jp9tVUVBOCGpVp4RjE522fEvwT.Vr2xhR7GksKd6', 'Admin', '1234567890', 'admin');

-- Insert sample movies
INSERT INTO movies (title, description, genre, duration, rating, release_date, poster_url) VALUES
('Avatar: The Way of Water', 'Jake Sully lives with his family.', 'Action/Adventure', 192, 7.8, '2022-12-16', 'uploads/poster list/Avatar2.jpg'),
('Spider-Man: No Way Home', 'Spider-Man seeks help from Doctor Strange.', 'Action/Adventure', 148, 8.2, '2021-12-17', 'uploads/poster list/Spiderman.jpg'),
('Top Gun: Maverick', 'Maverick is still pushing the envelope.', 'Action/Drama', 131, 8.3, '2022-05-27', 'uploads/poster list/TopGun.jpg');

-- Insert sample theaters
INSERT INTO theaters (name, location, total_seats) VALUES
('PVR Cinemas', 'City Mall, Downtown', 150),
('INOX Theater', 'Metro Plaza, Central', 120),
('Cineplex Multiplex', 'Grand Square, Uptown', 180);

-- Insert sample shows (total_seats added — the original file omitted this
-- required column, which made the INSERT fail on import). Dates are relative
-- to today so the sample shows are never stuck in the past.
INSERT INTO shows (movie_id, theater_id, show_date, show_time, price, total_seats, available_seats) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', 350.00, 150, 150),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '18:00:00', 300.00, 150, 150),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:30:00', 280.00, 120, 120),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '19:30:00', 320.00, 120, 120),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '16:00:00', 270.00, 180, 180),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '20:00:00', 310.00, 180, 180);

-- Show tables
SELECT * FROM users;
SELECT * FROM movies;
SELECT * FROM theaters;
SELECT * FROM shows;
SELECT * FROM bookings;

--admin@gmail.com
--saurabh123
