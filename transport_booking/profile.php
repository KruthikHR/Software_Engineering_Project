<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $db->prepare("
    SELECT username, email, full_name 
    FROM users 
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 40px;
        }

        .profile-box {
            background: white;
            padding: 30px;
            max-width: 500px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: #4f46e5;
            margin-bottom: 20px;
        }

        p {
            margin: 10px 0;
            font-size: 16px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: white;
            background: #4f46e5;
            padding: 10px 15px;
            border-radius: 5px;
        }

        .back-btn:hover {
            background: #4338ca;
        }

    </style>
</head>

<body>

    <div class="profile-box">

        <h2>My Profile</h2>

        <p>
            <strong>Full Name:</strong>
            <?php echo htmlspecialchars($user['full_name']); ?>
        </p>

        <p>
            <strong>Username:</strong>
            <?php echo htmlspecialchars($user['username']); ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?php echo htmlspecialchars($user['email']); ?>
        </p>

        <a href="dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>

    </div>

</body>

</html>