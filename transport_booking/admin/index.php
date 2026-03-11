<?php
require_once '../config/database.php';
$db = new Database();
redirectIfNotAdmin();

// Get statistics
$total_users = $db->conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_routes = $db->conn->query("SELECT COUNT(*) as count FROM routes")->fetch_assoc()['count'];
$total_tours = $db->conn->query("SELECT COUNT(*) as count FROM tours")->fetch_assoc()['count'];
$total_bookings = $db->conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$revenue = $db->conn->query("SELECT SUM(total_price) as revenue FROM bookings WHERE payment_status = 'paid'")->fetch_assoc()['revenue'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BusBook Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .admin-info h1 {
            color: #4f46e5;
            margin-bottom: 5px;
        }

        .admin-info p {
            color: #666;
        }

        .admin-nav {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .admin-nav a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .admin-nav .nav-link {
            background: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
        }

        .admin-nav .nav-link:hover {
            background: #4f46e5;
            color: white;
        }

        .admin-nav .logout {
            background: #fee;
            color: #dc2626;
        }

        .admin-nav .logout:hover {
            background: #dc2626;
            color: white;
        }

        .stats-grid {
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 10px;
        }

        .stat-card .trend {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #10b981;
            font-weight: 600;
        }

        .dashboard-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section-card h2 {
            color: #4f46e5;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .action-btn {
            background: rgba(79, 70, 229, 0.1);
            border: none;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            color: #4f46e5;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .action-btn:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-2px);
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-table th {
            text-align: left;
            padding: 12px;
            color: #666;
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
        }

        .recent-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-paid {
            background: #dbeafe;
            color: #1e40af;
        }

        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                text-align: center;
            }
            
            .dashboard-sections {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <div class="admin-info">
                <h1>🚀 Admin Dashboard</h1>
                <p>Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
            </div>
            
            <div class="admin-nav">
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="routes.php" class="nav-link">Bus Routes</a>
                <a href="tours.php" class="nav-link">Tours</a>
                <a href="bookings.php" class="nav-link">Bookings</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="../logout.php" class="logout">Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $total_users; ?></div>
                <div class="trend">↗ 12% increase</div>
            </div>
            
            <div class="stat-card">
                <h3>Active Routes</h3>
                <div class="number"><?php echo $total_routes; ?></div>
                <div class="trend">↗ 8% increase</div>
            </div>
            
            <div class="stat-card">
                <h3>Tour Packages</h3>
                <div class="number"><?php echo $total_tours; ?></div>
                <div class="trend">↗ 15% increase</div>
            </div>
            
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="number">$<?php echo number_format($revenue ?: 0, 2); ?></div>
                <div class="trend">↗ 20% increase</div>
            </div>
            
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="number"><?php echo $total_bookings; ?></div>
                <div class="trend">↗ 18% increase</div>
            </div>
        </div>

        <div class="dashboard-sections">
            <div class="section-card">
                <h2>Quick Actions</h2>
                <div class="quick-actions">
                    <a href="routes.php?action=add" class="action-btn">Add New Route</a>
                    <a href="tours.php?action=add" class="action-btn">Add New Tour</a>
                    <a href="bookings.php" class="action-btn">View All Bookings</a>
                    <a href="users.php" class="action-btn">Manage Users</a>
                </div>
            </div>

            <div class="section-card">
                <h2>Recent Bookings</h2>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_bookings = $db->conn->query("SELECT b.*, u.username FROM bookings b JOIN users u ON b.user_id = u.id ORDER BY booking_date DESC LIMIT 5");
                        while($booking = $recent_bookings->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td><?php echo ucfirst($booking['booking_type']); ?></td>
                            <td>$<?php echo $booking['total_price']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['payment_status']; ?>">
                                    <?php echo ucfirst($booking['payment_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>