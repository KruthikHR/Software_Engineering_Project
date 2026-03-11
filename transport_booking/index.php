<?php
require_once 'config/database.php';
$db = new Database();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus & Tour Booking System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
            opacity: 0.1;
            z-index: -1;
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #4f46e5;
            text-align: center;
            margin-bottom: 10px;
        }

        .tagline {
            text-align: center;
            color: #666;
            font-size: 16px;
        }

        nav {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }

        nav a {
            text-decoration: none;
            color: #4f46e5;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        nav a:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-2px);
        }

        .hero {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .hero h1 {
            color: #4f46e5;
            font-size: 2.8rem;
            margin-bottom: 20px;
        }

        .hero p {
            color: #666;
            font-size: 1.2rem;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto 30px;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #4f46e5;
            border: 2px solid #4f46e5;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 40px;
            color: #4f46e5;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            color: #4f46e5;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        footer {
            margin-top: 50px;
            text-align: center;
            color: white;
            padding: 30px;
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .hero {
                padding: 20px;
            }
            
            .feature-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">🚌 BusBook Pro</div>
            <div class="tagline">Your Journey, Our Priority</div>
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

        <main>
            <section class="hero">
                <h1>Book Your Bus & Tour Packages</h1>
                <p>Experience comfortable bus rides and exciting tour packages with our premium booking service. Easy, fast, and reliable transportation solutions for your travel needs.</p>
                
                <div class="cta-buttons">
                    <a href="routes.php" class="btn btn-primary">Book a Bus Ticket</a>
                    <a href="tours.php" class="btn btn-secondary">Explore Tour Packages</a>
                </div>
            </section>

            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">🚌</div>
                    <h3>Comfortable Buses</h3>
                    <p>Travel in comfort with our modern fleet of buses equipped with all amenities for a pleasant journey.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🏖️</div>
                    <h3>Amazing Tours</h3>
                    <p>Discover exciting destinations with our carefully curated tour packages for all types of travelers.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Easy Booking</h3>
                    <p>Book your tickets and tours in just a few clicks with our user-friendly online platform.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>Safe & Secure</h3>
                    <p>Your safety and security are our top priorities. Travel with confidence and peace of mind.</p>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2024 BusBook Pro. All rights reserved.</p>
            <p>Contact: info@busbookpro.com | Phone: +1 (555) 123-4567</p>
        </footer>
    </div>
</body>
</html>