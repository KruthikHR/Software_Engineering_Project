<?php
require_once 'config/database.php';
$db = new Database();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // SPECIAL CASE: Admin login with hardcoded credentials
        if ($username == 'admin' && $password == 'admin123') {
            // Check if admin exists in database
            $stmt = $db->prepare("SELECT id FROM users WHERE username = 'admin' AND role = 'admin'");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
            } else {
                // Create admin if doesn't exist
                $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES ('admin', 'admin@busbook.com', ?, 'System Administrator', 'admin')");
                $stmt->bind_param("s", $hashed_password);
                $stmt->execute();
                $user_id = $db->conn->insert_id;
            }
            
            // Login as admin
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            $_SESSION['full_name'] = 'System Administrator';
            
            header("Location: admin/index.php");
            exit();
        }
        
        // Regular user login
        $stmt = $db->prepare("SELECT id, username, password_hash, role, full_name FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                if ($user['role'] == 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BusBook Pro</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .logo {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .links {
            text-align: center;
            margin-top: 25px;
        }

        .links a {
            color: #4f46e5;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .demo-credentials {
            background: #f8f9fa;
            border: 2px dashed #4f46e5;
            border-radius: 12px;
            padding: 15px;
            margin-top: 25px;
            text-align: center;
        }

        .demo-credentials h4 {
            color: #4f46e5;
            margin-bottom: 10px;
        }

        .demo-credentials p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .credential-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            margin: 10px 0;
            text-align: left;
        }

        .credential-box strong {
            color: #4f46e5;
            display: block;
            margin-bottom: 5px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo {
                font-size: 28px;
            }
            
            .demo-credentials {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🚌 BusBook Pro</div>
        <div class="subtitle">Login to your account</div>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="links">
            <a href="register.php">Create Account</a> | 
            <a href="index.php">Back to Home</a>
        </div>

        <div class="demo-credentials">
            <h4>📋 Login Credentials</h4>
            
            <div class="credential-box">
                <strong>Admin Access:</strong>
                <div>Username: <code>admin</code></div>
                <div>Password: <code>admin123</code></div>
            </div>
            
            <div class="credential-box">
                <strong>Regular User:</strong>
                <div>Register new account or use:</div>
                <div>Username: <code>user123</code></div>
                <div>Password: <code>pass123</code></div>
            </div>
            
            <p style="margin-top: 10px; font-size: 12px; color: #888;">
                Admin account will be auto-created if it doesn't exist
            </p>
        </div>
    </div>
</body>
</html>