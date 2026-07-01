<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$booking_id) {
    flash('No booking selected.', 'error');
    redirect('dashboard.php');
}

// Fetch booking details
$stmt = $pdo->prepare("
    SELECT b.*, s.show_date, s.show_time, s.id AS show_id
    FROM bookings b
    JOIN shows s ON b.show_id = s.id
    WHERE b.id = ? AND b.user_id = ? AND b.booking_status = 'confirmed'
    LIMIT 1
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    flash('Invalid booking or already cancelled.', 'error');
    redirect('dashboard.php');
}

// Check if the show is in the future
$show_timestamp = strtotime($booking['show_date'] . ' ' . $booking['show_time']);
if ($show_timestamp <= time()) {
    flash('You cannot cancel a past show.', 'error');
    redirect('dashboard.php');
}

// Cancel booking and free seats
try {
    $pdo->beginTransaction();

    // Mark booking as cancelled
    $updateBooking = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
    $updateBooking->execute([$booking_id]);

    // Free up seats in shows table
    $updateSeats = $pdo->prepare("UPDATE shows SET available_seats = available_seats + ? WHERE id = ?");
    $updateSeats->execute([$booking['seats_booked'], $booking['show_id']]);

    $pdo->commit();
    flash('Booking cancelled successfully.', 'success');
} catch (Exception $e) {
    $pdo->rollBack();
    flash('Cancellation failed: ' . $e->getMessage(), 'error');
}

redirect('dashboard.php');
