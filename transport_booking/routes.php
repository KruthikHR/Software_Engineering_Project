<?php
require_once 'config/database.php';
$db = new Database();

// Search and filter
$search = $_GET['search'] ?? '';
$origin = $_GET['origin'] ?? '';
$destination = $_GET['destination'] ?? '';

$query = "SELECT * FROM routes WHERE status = 'active'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (bus_name LIKE ? OR origin LIKE ? OR destination LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= "sss";
}

if (!empty($origin)) {
    $query .= " AND origin LIKE ?";
    $params[] = "%$origin%";
    $types .= "s";
}

if (!empty($destination)) {
    $query .= " AND destination LIKE ?";
    $params[] = "%$destination%";
    $types .= "s";
}

$query .= " ORDER BY departure_time ASC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$routes = $stmt->get_result();

// Get unique origins and destinations for filter dropdowns
$origins = $db->conn->query("SELECT DISTINCT origin FROM routes WHERE status = 'active' ORDER BY origin");
$destinations = $db->conn->query("SELECT DISTINCT destination FROM routes WHERE status = 'active' ORDER BY destination");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Routes - BusBook Pro</title>
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
            color: #4f46e5;
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

        .page-title {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
        }

        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: flex-end;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .routes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .route-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .route-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .bus-name {
            font-size: 20px;
            font-weight: 700;
            color: #4f46e5;
        }

        .bus-type {
            background: #4f46e5;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .route-details {
            margin-bottom: 20px;
        }

        .route-path {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 15px;
            background: rgba(79, 70, 229, 0.05);
            border-radius: 10px;
        }

        .origin, .destination {
            text-align: center;
        }

        .origin strong, .destination strong {
            display: block;
            font-size: 18px;
            color: #4f46e5;
            margin-bottom: 5px;
        }

        .arrow {
            font-size: 24px;
            color: #4f46e5;
        }

        .timing {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #666;
        }

        .price-availability {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .price {
            font-size: 24px;
            font-weight: 700;
            color: #4f46e5;
        }

        .availability {
            text-align: right;
        }

        .seats {
            font-size: 14px;
            color: #666;
        }

        .available {
            color: #10b981;
            font-weight: 600;
        }

        .limited {
            color: #f59e0b;
            font-weight: 600;
        }

        .book-btn {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
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
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .no-results {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .routes-grid {
                grid-template-columns: 1fr;
            }
            
            .route-card {
                padding: 20px;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            nav {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">🚌 BusBook Pro</div>
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

        <h1 class="page-title">Available Bus Routes</h1>

        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Bus name, origin, destination..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <label>From</label>
                    <select name="origin" class="form-control">
                        <option value="">All Origins</option>
                        <?php while($row = $origins->fetch_assoc()): ?>
                            <option value="<?php echo $row['origin']; ?>" <?php echo ($origin == $row['origin']) ? 'selected' : ''; ?>>
                                <?php echo $row['origin']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>To</label>
                    <select name="destination" class="form-control">
                        <option value="">All Destinations</option>
                        <?php while($row = $destinations->fetch_assoc()): ?>
                            <option value="<?php echo $row['destination']; ?>" <?php echo ($destination == $row['destination']) ? 'selected' : ''; ?>>
                                <?php echo $row['destination']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn">Filter Routes</button>
            </form>
        </div>

        <div class="routes-grid">
            <?php if($routes->num_rows > 0): ?>
                <?php while($route = $routes->fetch_assoc()): ?>
                    <div class="route-card">
                        <div class="route-header">
                            <div class="bus-name"><?php echo htmlspecialchars($route['bus_name']); ?></div>
                            <div class="bus-type"><?php echo $route['bus_type']; ?></div>
                        </div>
                        
                        <div class="route-details">
                            <div class="route-path">
                                <div class="origin">
                                    <strong><?php echo htmlspecialchars($route['origin']); ?></strong>
                                    <div><?php echo date('h:i A', strtotime($route['departure_time'])); ?></div>
                                </div>
                                <div class="arrow">→</div>
                                <div class="destination">
                                    <strong><?php echo htmlspecialchars($route['destination']); ?></strong>
                                    <div><?php echo date('h:i A', strtotime($route['arrival_time'])); ?></div>
                                </div>
                            </div>
                            
                            <div class="timing">
                                <div>Duration: <?php echo getDuration($route['departure_time'], $route['arrival_time']); ?></div>
                                <div>Date: <?php echo date('M d, Y', strtotime($route['departure_time'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="price-availability">
                            <div>
                                <div class="price">$<?php echo $route['price']; ?></div>
                                <div class="seats">per seat</div>
                            </div>
                            
                            <div class="availability">
                                <div class="seats <?php echo ($route['available_seats'] > 10) ? 'available' : 'limited'; ?>">
                                    <?php echo $route['available_seats']; ?> seats available
                                </div>
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="bookings.php?id=<?php echo $route['id']; ?>" class="book-btn">Book Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="book-btn">Login to Book</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <h3>No bus routes found</h3>
                    <p>Try adjusting your search filters or check back later for new routes.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
function getDuration($departure, $arrival) {
    $departure_time = new DateTime($departure);
    $arrival_time = new DateTime($arrival);
    $interval = $departure_time->diff($arrival_time);
    
    if ($interval->days > 0) {
        return $interval->format('%d days %h hours');
    } else {
        return $interval->format('%h hours %i minutes');
    }
}
$stmt->close();
?>