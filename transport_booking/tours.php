<?php
require_once 'config/database.php';
$db = new Database();

// Get all active tours
$tours = $db->conn->query("SELECT * FROM tours WHERE status = 'active' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages - BusBook Pro</title>
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
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #0f766e;
            text-align: center;
            margin-bottom: 10px;
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
            color: #0f766e;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            background: rgba(15, 118, 110, 0.1);
        }

        nav a:hover {
            background: #0f766e;
            color: white;
            transform: translateY(-2px);
        }

        .page-title {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
        }

        .tours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .tour-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .tour-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .tour-image {
            height: 200px;
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
        }

        .tour-content {
            padding: 25px;
        }

        .tour-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .tour-name {
            font-size: 22px;
            font-weight: 700;
            color: #0f766e;
        }

        .tour-price {
            font-size: 24px;
            font-weight: 700;
            color: #0f766e;
        }

        .tour-duration {
            background: rgba(15, 118, 110, 0.1);
            color: #0f766e;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .tour-details {
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #666;
        }

        .detail-item i {
            margin-right: 10px;
            color: #0f766e;
            font-size: 18px;
        }

        .destinations {
            background: rgba(15, 118, 110, 0.05);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }

        .destinations strong {
            color: #0f766e;
            display: block;
            margin-bottom: 8px;
        }

        .inclusions {
            background: rgba(15, 118, 110, 0.05);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 14px;
        }

        .inclusions strong {
            color: #0f766e;
            display: block;
            margin-bottom: 8px;
        }

        .tour-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .slots-available {
            color: #666;
            font-size: 14px;
        }

        .slots-available .available {
            color: #10b981;
            font-weight: 600;
        }

        .slots-available .limited {
            color: #f59e0b;
            font-weight: 600;
        }

        .book-btn {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 118, 110, 0.3);
        }

        .book-btn.disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .no-tours {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            grid-column: 1 / -1;
        }

        .tour-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #0f766e;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .tours-grid {
                grid-template-columns: 1fr;
            }
            
            .tour-card {
                padding: 15px;
            }
            
            nav {
                flex-direction: column;
                align-items: center;
            }
            
            .tour-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">🏖️ BusBook Tours</div>
            <nav>
                <a href="index.php">Home</a>
                <a href="routes.php">Bus Routes</a>
                <a href="tours.php">Tour Packages</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </header>

        <h1 class="page-title">Amazing Tour Packages</h1>

        <div class="tours-grid">
            <?php if($tours->num_rows > 0): ?>
                <?php while($tour = $tours->fetch_assoc()): ?>
                    <div class="tour-card">
                        <div class="tour-image">
                            🏖️
                        </div>
                        
                        <div class="tour-content">
                            <div class="tour-header">
                                <div class="tour-name"><?php echo htmlspecialchars($tour['tour_name']); ?></div>
                                <div class="tour-price">$<?php echo $tour['price']; ?></div>
                            </div>
                            
                            <div class="tour-duration">⏱️ <?php echo $tour['duration_days']; ?> Days</div>
                            
                            <div class="tour-details">
                                <div class="detail-item">
                                    <i>📍</i>
                                    <strong>Destinations:</strong>
                                </div>
                                <div class="destinations">
                                    <?php echo htmlspecialchars($tour['destinations']); ?>
                                </div>
                                
                                <div class="detail-item">
                                    <i>🎁</i>
                                    <strong>Inclusions:</strong>
                                </div>
                                <div class="inclusions">
                                    <?php echo htmlspecialchars($tour['inclusions']); ?>
                                </div>
                            </div>
                            
                            <div class="tour-footer">
                                <div class="slots-available">
                                    <span class="<?php echo ($tour['available_slots'] > 5) ? 'available' : 'limited'; ?>">
                                        <?php echo $tour['available_slots']; ?> slots available
                                    </span>
                                </div>
                                
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <?php if($tour['available_slots'] > 0): ?>
                                        <a href="book_tour.php?id=<?php echo $tour['id']; ?>" class="book-btn">Book Now</a>
                                    <?php else: ?>
                                        <button class="book-btn disabled" disabled>Sold Out</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="book-btn">Login to Book</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-tours">
                    <h3>No tour packages available at the moment</h3>
                    <p>Check back later for amazing tour offers!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>