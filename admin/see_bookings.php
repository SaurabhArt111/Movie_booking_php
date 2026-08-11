<?php
require_once '../config.php';
requireAdmin($pdo);

/* Fetch bookings with correct real DB columns */
$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.seats_booked,
        b.total_amount,
        b.booking_status,
        b.booking_date,

        u.full_name AS user_name,
        u.email,

        m.title AS movie_title,
        t.name AS theater_name,

        CONCAT(s.show_date, ' ', s.show_time) AS show_datetime,

        (SELECT GROUP_CONCAT(seat_label ORDER BY seat_label SEPARATOR ', ')
           FROM booked_seats WHERE booking_id = b.id) AS seat_labels,

        -- computed watched status
        CASE
            WHEN CONCAT(s.show_date, ' ', s.show_time) < NOW()
                 AND b.booking_status = 'confirmed'
            THEN 'watched'
            ELSE b.booking_status
        END AS final_status

    FROM bookings b
    JOIN users u     ON b.user_id = u.id
    JOIN shows s    ON b.show_id = s.id
    JOIN movies m  ON s.movie_id = m.id
    JOIN theaters t ON s.theater_id = t.id

    ORDER BY b.booking_date DESC
");

$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Bookings - CinemaFlex</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3), transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(236, 72, 153, 0.3), transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.98);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: #2d3748;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 i {
            color: #667eea;
            font-size: 1.1em;
            animation: rotate 3s ease-in-out infinite;
        }

        @keyframes rotate {

            0%,
            100% {
                transform: rotate(0deg);
            }

            50% {
                transform: rotate(10deg);
            }
        }

        .empty {
            text-align: center;
            padding: 80px 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            margin: 40px 0;
        }

        .empty p {
            font-size: 1.3em;
            color: #64748b;
            font-weight: 500;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 30px 0;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        thead tr th {
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 18px 15px;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: 16px 15px;
            color: #475569;
            font-size: 0.95em;
        }

        td:first-child {
            font-weight: 700;
            color: #667eea;
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        /* Price Styles */
        .price {
            font-weight: 700;
            font-size: 1.1em;
            color: #059669;
        }

        /* Status Styles */
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85em;
            text-transform: capitalize;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .status.confirmed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .status.pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .status.cancelled {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .status.completed {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin-top: 20px;
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }

        .back-btn i {
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-5px);
        }

        .watched {
            background: #7c3aed;
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            table {
                font-size: 0.9em;
            }

            td,
            th {
                padding: 12px 10px;
            }
        }

        @media (max-width: 992px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 2em;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }

            .container {
                padding: 25px 15px;
                border-radius: 15px;
            }

            h1 {
                font-size: 1.6em;
                flex-direction: column;
                gap: 10px;
            }

            table {
                font-size: 0.85em;
            }

            td,
            th {
                padding: 10px 8px;
            }

            .back-btn {
                width: 100%;
                justify-content: center;
                padding: 12px 20px;
            }
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 1.4em;
            }

            .badge,
            .status {
                font-size: 0.75em;
                padding: 5px 10px;
            }

            .price {
                font-size: 1em;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <h1><i class="fas fa-ticket-alt"></i> All Movie Bookings</h1>

        <?php if (empty($bookings)): ?>
            <div class="empty">
                <p>No bookings found.</p>
            </div>
        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Movie</th>
                        <th>Theater</th>
                        <th>Show Time</th>
                        <th>Seats</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Booked At</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>#<?= $b['id'] ?></td>
                            <td><?= htmlspecialchars($b['user_name']) ?></td>
                            <td><?= htmlspecialchars($b['email']) ?></td>
                            <td><?= htmlspecialchars($b['movie_title']) ?></td>
                            <td><?= htmlspecialchars($b['theater_name']) ?></td>

                            <td>
                                <?= date('d M Y, h:i A', strtotime($b['show_datetime'])) ?>
                            </td>

                            <td>
                                <span class="badge"><?= $b['seats_booked'] ?></span>
                                <?php if (!empty($b['seat_labels'])): ?>
                                    <div style="font-size: 0.75rem; opacity: 0.7; margin-top: 4px;">
                                        <?= htmlspecialchars($b['seat_labels']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="price">
                                ₹<?= number_format($b['total_amount'], 2) ?>
                            </td>

                            <td>
                                <span class="status <?= $b['final_status'] ?>">
                                    <?= ucfirst($b['final_status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= date('d M Y, h:i A', strtotime($b['booking_date'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>

        <?php endif; ?>

        <a class="back-btn" href="dashboard.php">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

    </div>
</body>

</html>