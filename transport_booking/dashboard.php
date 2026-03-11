<?php
require_once 'config/database.php';
$db = new Database();
redirectIfNotLoggedIn();

// Get user's bookings
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT b.id, b.booking_type, b.travel_date, b.seats_booked, b.total_price, b.payment_status, r.bus_name, r.origin, r.destination, t.tour_name
                      FROM bookings b
                      LEFT JOIN routes r ON b.route_id = r.id
                      LEFT JOIN tours t ON b.tour_id = t.id
                      WHERE b.user_id = ?
                      ORDER BY b.booking_date DESC
                      LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BusBook Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #4f46e5;
            text-align: center;
            margin-bottom: 10px;
        }

        .welcome {
            text-align: center;
            color: #666;
            font-size: 16px;
        }

        .welcome span {
            color: #4f46e5;
            font-weight: 600;
        }

        nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }

        nav a {
            text-decoration: none;
            color: #4f46e5;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            background: rgba(79, 70, 229, 0.1);
        }

        nav a:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-2px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title {
            color: white;
            margin: 40px 0 20px;
            font-size: 24px;
        }

        .bookings-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #4f46e5;
            color: white;
            padding: 15px;
            text-align: left;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: rgba(79, 70, 229, 0.05);
        }

        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status.confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .status.cancelled {
            background: #fee;
            color: #dc2626;
        }

        .status.completed {
            background: #dbeafe;
            color: #1e40af;
        }
        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .action-card:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                align-items: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">🚌 BusBook Pro</div>
            <div class="welcome">Welcome back, <span><?php echo $_SESSION['full_name']; ?></span>!</div>
            <nav>
                <a href="index.php">Home</a>
                <a href="routes.php">Book Bus</a>
                <a href="tours.php">Book Tour</a>
                <a href="bookings.php">My Bookings</a>
                <a href="profile.php">My Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-number">5</div>
                <div class="stat-label">Total Bookings</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number">4</div>
                <div class="stat-label">Confirmed</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-number">$450</div>
                <div class="stat-label">Total Spent</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number">Gold</div>
                <div class="stat-label">Member Level</div>
            </div>
        </div>

        <h2 class="section-title">Recent Bookings</h2>
        <div class="bookings-table">
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Seats/Slots</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($booking = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $booking['id']; ?></td>
                        <td>
                            <?php 
                            if($booking['booking_type'] == 'transport') {
                                echo $booking['bus_name'] . ' (' . $booking['origin'] . ' - ' . $booking['destination'] . ')';
                            } else {
                                echo $booking['tour_name'];
                            }
                            ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></td>
                        <td><?php echo $booking['seats_booked']; ?></td>
                        <td>$<?php echo $booking['total_price']; ?></td>
                        <td>
                            <?php $status = $booking['payment_status'] ?? 'pending'; ?>
                            <span class="status <?php echo $status; ?>">
                               <?php echo ucfirst($status); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="quick-actions">
            <a href="routes.php" class="action-card">
                <div class="action-icon">🚌</div>
                <h3>Book a Bus</h3>
                <p>Find and book bus tickets</p>
            </a>
            
            <a href="tours.php" class="action-card">
                <div class="action-icon">🏖️</div>
                <h3>Book a Tour</h3>
                <p>Explore tour packages</p>
            </a>
            
            <a href="bookings.php" class="action-card">
                <div class="action-icon">📋</div>
                <h3>My Bookings</h3>
                <p>View all bookings</p>
            </a>
            
            <a href="profile.php" class="action-card">
                <div class="action-icon">👤</div>
                <h3>My Profile</h3>
                <p>Update your information</p>
            </a>
        </div>
    </div>
</body>
</html>
<?php $stmt->close(); ?>