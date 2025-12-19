<?php
session_start();

// Include database connection (also includes sanitize_input())
require_once '../database/db_connect.php';

// Check if database connection is established
if (!isset($conn) || $conn->connect_error) {
    die("Database connection error. Please check your database configuration.");
}

$error = '';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid CSRF token. Please refresh the page.';
    } else {
        // Prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        
        if (!$stmt) {
            $error = 'Database query error. Please try again.';
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                $stored_password = $admin['password'];
                
                // Use password_verify() for hashed passwords
                if (password_verify($password, $stored_password)) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];

                    // Redirect to dashboard
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Organic Marketplace</title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body class="login-page">
<div class="login-container">
    <div class="login-box">
        <h2>👨‍💼 Admin Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="" id="adminLoginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="username" placeholder="Enter username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        <p style="text-align: center; margin-top: 20px;">
            <a href="../index.php" style="color: #667eea;">← Back to Home</a>
        </p>
    </div>
</div>
</body>
</html>
