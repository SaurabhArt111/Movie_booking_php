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

-- Insert sample users
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

-- Insert sample shows
INSERT INTO shows (movie_id, theater_id, show_date, show_time, price, available_seats) VALUES
(1, 1, '2026-02-12', '14:00:00', 350.00, 150),
(1, 1, '2026-02-12', '18:00:00', 300.00, 150),
(2, 2, '2026-02-12', '15:30:00', 280.00, 120),
(2, 2, '2026-02-12', '19:30:00', 320.00, 120),
(3, 3, '2026-02-12', '16:00:00', 270.00, 180),
(3, 3, '2026-02-12', '20:00:00', 310.00, 180);

-- Show tabels
SELECT * FROM users;
SELECT * FROM movies;
SELECT * FROM theaters;
SELECT * FROM shows;
SELECT * FROM bookings;
--admin@gmail.com
--saurabh123
