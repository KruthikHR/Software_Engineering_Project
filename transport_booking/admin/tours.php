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

// Add/Edit Tour
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tour_name = $_POST['tour_name'];
    $description = $_POST['description'];
    $duration_days = $_POST['duration_days'];
    $destinations = $_POST['destinations'];
    $inclusions = $_POST['inclusions'];
    $price = $_POST['price'];
    $max_persons = $_POST['max_persons'];
    $available_slots = $_POST['max_persons']; // Initially all slots available
    
    if ($id > 0) {
        // Update tour
        $stmt = $db->prepare("UPDATE tours SET tour_name=?, description=?, duration_days=?, destinations=?, inclusions=?, price=?, max_persons=?, available_slots=? WHERE id=?");
        $stmt->bind_param("ssissdiii", $tour_name, $description, $duration_days, $destinations, $inclusions, $price, $max_persons, $available_slots, $id);
        $message = "Tour updated successfully!";
    } else {
        // Add new tour
        $stmt = $db->prepare("INSERT INTO tours (tour_name, description, duration_days, destinations, inclusions, price, max_persons, available_slots) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissdii", $tour_name, $description, $duration_days, $destinations, $inclusions, $price, $max_persons, $available_slots);
        $message = "Tour added successfully!";
    }
    
    if ($stmt->execute()) {
        $success = $message;
    } else {
        $error = "Error saving tour!";
    }
}

// Delete tour
if ($action == 'delete' && $id > 0) {
    $stmt = $db->prepare("DELETE FROM tours WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Tour deleted successfully!";
    } else {
        $error = "Error deleting tour!";
    }
}

// Fetch tours
$tours = $db->conn->query("SELECT * FROM tours ORDER BY created_at DESC");

// Fetch single tour for edit
$edit_tour = null;
if ($action == 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM tours WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_tour = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tours - Admin Panel</title>
    <style>
        /* Same CSS as routes.php - just change colors */
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
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
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
            border-left-color: #0d9488;
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
            color: #0f766e;
        }

        .btn {
            padding: 10px 20px;
            background: #0f766e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #0d9488;
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
            background: #0f766e;
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
            border-color: #0f766e;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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
                <h2>🏖️ Tour Admin</h2>
                <p><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></p>
            </div>
            <div class="nav-links">
                <a href="index.php">📊 Dashboard</a>
                <a href="routes.php">🚌 Manage Routes</a>
                <a href="tours.php" class="active">🏖️ Manage Tours</a>
                <a href="bookings.php">📋 Manage Bookings</a>
                <a href="users.php">👥 Manage Users</a>
                <a href="../logout.php">🚪 Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><?php echo $edit_tour ? 'Edit Tour' : 'Manage Tour Packages'; ?></h1>
                <a href="?action=add" class="btn">+ Add New Tour</a>
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
                        <div class="form-group">
                            <label>Tour Name *</label>
                            <input type="text" name="tour_name" class="form-control" 
                                   value="<?php echo $edit_tour['tour_name'] ?? ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Description *</label>
                            <textarea name="description" class="form-control" required><?php echo $edit_tour['description'] ?? ''; ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Duration (Days) *</label>
                                <input type="number" name="duration_days" class="form-control" 
                                       value="<?php echo $edit_tour['duration_days'] ?? 3; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Price ($) *</label>
                                <input type="number" step="0.01" name="price" class="form-control" 
                                       value="<?php echo $edit_tour['price'] ?? ''; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Destinations *</label>
                            <textarea name="destinations" class="form-control" required><?php echo $edit_tour['destinations'] ?? ''; ?></textarea>
                            <small>Separate destinations with commas</small>
                        </div>

                        <div class="form-group">
                            <label>Inclusions *</label>
                            <textarea name="inclusions" class="form-control" required><?php echo $edit_tour['inclusions'] ?? ''; ?></textarea>
                            <small>Separate inclusions with commas</small>
                        </div>

                        <div class="form-group">
                            <label>Maximum Persons *</label>
                            <input type="number" name="max_persons" class="form-control" 
                                   value="<?php echo $edit_tour['max_persons'] ?? 20; ?>" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn"><?php echo $edit_tour ? 'Update Tour' : 'Add Tour'; ?></button>
                            <a href="tours.php" class="btn" style="background: #6b7280; margin-left: 10px;">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Tours List -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tour Name</th>
                                <th>Duration</th>
                                <th>Destinations</th>
                                <th>Price</th>
                                <th>Slots</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($tours->num_rows > 0): ?>
                                <?php while($tour = $tours->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $tour['id']; ?></td>
                                    <td><?php echo htmlspecialchars($tour['tour_name']); ?></td>
                                    <td><?php echo $tour['duration_days']; ?> days</td>
                                    <td><?php echo htmlspecialchars(substr($tour['destinations'], 0, 50)); ?>...</td>
                                    <td>$<?php echo $tour['price']; ?></td>
                                    <td><?php echo $tour['available_slots']; ?>/<?php echo $tour['max_persons']; ?></td>
                                    <td class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $tour['id']; ?>" class="btn-edit">Edit</a>
                                        <a href="?action=delete&id=<?php echo $tour['id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Delete this tour?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        No tours found. <a href="?action=add">Add your first tour package</a>
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