<?php
require_once '../config/database.php';
$db = new Database();

// Check admin access
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle add/edit/delete
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$message = '';

// Add/Edit Route
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bus_name = $_POST['bus_name'];
    $bus_type = $_POST['bus_type'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];
    $total_seats = $_POST['total_seats'];
    $available_seats = $_POST['total_seats']; // Initially all seats available
    
    if ($id > 0) {
        // Update route
        $stmt = $db->prepare("UPDATE routes SET bus_name=?, bus_type=?, origin=?, destination=?, departure_time=?, arrival_time=?, price=?, total_seats=?, available_seats=? WHERE id=?");
        $stmt->bind_param("ssssssdiii", $bus_name, $bus_type, $origin, $destination, $departure_time, $arrival_time, $price, $total_seats, $available_seats, $id);
        $message = "Route updated successfully!";
    } else {
        // Add new route
        $stmt = $db->prepare("INSERT INTO routes (bus_name, bus_type, origin, destination, departure_time, arrival_time, price, total_seats, available_seats) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdii", $bus_name, $bus_type, $origin, $destination, $departure_time, $arrival_time, $price, $total_seats, $available_seats);
        $message = "Route added successfully!";
    }
    
    if ($stmt->execute()) {
        $success = $message;
    } else {
        $error = "Error saving route!";
    }
}

// Delete route
if ($action == 'delete' && $id > 0) {
    $stmt = $db->prepare("DELETE FROM routes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Route deleted successfully!";
    } else {
        $error = "Error deleting route!";
    }
}

// Fetch routes
$routes = $db->conn->query("SELECT * FROM routes ORDER BY created_at DESC");

// Fetch single route for edit
$edit_route = null;
if ($action == 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM routes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_route = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f5f5;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px 0;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 14px;
            opacity: 0.8;
        }

        .nav-links {
            padding: 20px 0;
        }

        .nav-links a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #4f46e5;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .header h1 {
            color: #4f46e5;
        }

        .btn {
            padding: 10px 20px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #4338ca;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f9fafb;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
        }

        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🚌 BusBook Admin</h2>
                <p><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></p>
            </div>
            <div class="nav-links">
                <a href="index.php">📊 Dashboard</a>
                <a href="routes.php" class="active">🚌 Manage Routes</a>
                <a href="tours.php">🏖️ Manage Tours</a>
                <a href="bookings.php">📋 Manage Bookings</a>
                <a href="users.php">👥 Manage Users</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><?php echo $edit_route ? 'Edit Route' : 'Manage Bus Routes'; ?></h1>
                <a href="?action=add" class="btn">+ Add New Route</a>
            </div>

            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($action == 'add' || $action == 'edit'): ?>
                <!-- Add/Edit Form -->
                <div class="form-container">
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Bus Name *</label>
                                <input type="text" name="bus_name" class="form-control" 
                                       value="<?php echo $edit_route['bus_name'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Bus Type *</label>
                                <select name="bus_type" class="form-control" required>
                                    <option value="Standard" <?php echo ($edit_route['bus_type'] ?? '') == 'Standard' ? 'selected' : ''; ?>>Standard</option>
                                    <option value="Deluxe" <?php echo ($edit_route['bus_type'] ?? '') == 'Deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                                    <option value="AC" <?php echo ($edit_route['bus_type'] ?? '') == 'AC' ? 'selected' : ''; ?>>AC</option>
                                    <option value="Sleeper" <?php echo ($edit_route['bus_type'] ?? '') == 'Sleeper' ? 'selected' : ''; ?>>Sleeper</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Origin *</label>
                                <input type="text" name="origin" class="form-control" 
                                       value="<?php echo $edit_route['origin'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Destination *</label>
                                <input type="text" name="destination" class="form-control" 
                                       value="<?php echo $edit_route['destination'] ?? ''; ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Departure Time *</label>
                                <input type="datetime-local" name="departure_time" class="form-control" 
                                       value="<?php echo isset($edit_route['departure_time']) ? date('Y-m-d\TH:i', strtotime($edit_route['departure_time'])) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Arrival Time *</label>
                                <input type="datetime-local" name="arrival_time" class="form-control" 
                                       value="<?php echo isset($edit_route['arrival_time']) ? date('Y-m-d\TH:i', strtotime($edit_route['arrival_time'])) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Price ($) *</label>
                                <input type="number" step="0.01" name="price" class="form-control" 
                                       value="<?php echo $edit_route['price'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Total Seats *</label>
                                <input type="number" name="total_seats" class="form-control" 
                                       value="<?php echo $edit_route['total_seats'] ?? 40; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn"><?php echo $edit_route ? 'Update Route' : 'Add Route'; ?></button>
                            <a href="routes.php" class="btn" style="background: #6b7280; margin-left: 10px;">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Routes List -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bus Name</th>
                                <th>Route</th>
                                <th>Departure</th>
                                <th>Price</th>
                                <th>Seats</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($routes->num_rows > 0): ?>
                                <?php while($route = $routes->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $route['id']; ?></td>
                                    <td><?php echo htmlspecialchars($route['bus_name']); ?> (<?php echo $route['bus_type']; ?>)</td>
                                    <td><?php echo htmlspecialchars($route['origin']); ?> → <?php echo htmlspecialchars($route['destination']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($route['departure_time'])); ?></td>
                                    <td>$<?php echo $route['price']; ?></td>
                                    <td><?php echo $route['available_seats']; ?>/<?php echo $route['total_seats']; ?></td>
                                    <td class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $route['id']; ?>" class="btn-edit">Edit</a>
                                        <a href="?action=delete&id=<?php echo $route['id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Delete this route?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        No routes found. <a href="?action=add">Add your first route</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>