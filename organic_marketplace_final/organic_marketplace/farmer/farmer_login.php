<?php
session_start();
require_once '../database/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Check if verification_status column exists
    $colCheck = $conn->query("SHOW COLUMNS FROM farmers LIKE 'verification_status'");
    $hasVerification = $colCheck->num_rows > 0;
    
    if ($hasVerification) {
        $stmt = $conn->prepare("SELECT id, name, email, password, verification_status, rejection_reason FROM farmers WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM farmers WHERE email = ?");
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $farmer = $result->fetch_assoc();
        if (password_verify($password, $farmer['password'])) {
            // Check verification status if column exists
            if ($hasVerification) {
                if (!isset($farmer['verification_status']) || $farmer['verification_status'] == 'pending') {
                    $error = 'Your account is pending admin verification. Please wait for approval before logging in.';
                } elseif ($farmer['verification_status'] == 'rejected') {
                    $rejection_reason = !empty($farmer['rejection_reason']) ? ': ' . htmlspecialchars($farmer['rejection_reason']) : '';
                    $error = 'Your account has been rejected' . $rejection_reason . '. Please contact the Department of Agriculture for more information.';
                } elseif ($farmer['verification_status'] == 'approved') {
                    // Account approved - proceed with login
                    $_SESSION['farmer_id'] = $farmer['id'];
                    $_SESSION['farmer_name'] = $farmer['name'];
                    $_SESSION['farmer_email'] = $farmer['email'];
                    header('Location: farmer_dashboard.php');
                    exit();
                }
            } else {
                // Old schema - allow login without verification
                $_SESSION['farmer_id'] = $farmer['id'];
                $_SESSION['farmer_name'] = $farmer['name'];
                $_SESSION['farmer_email'] = $farmer['email'];
                header('Location: farmer_dashboard.php');
                exit();
            }
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Invalid email or password';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Login - Organic Marketplace</title>
    <link rel="stylesheet" href="css/login_style.css">
</head>
<body>
    <div class="login-container">
        <h2>🧑‍🌾 Farmer Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="links">
            <a href="farmer_register.php">Don't have an account? Register</a> | 
            <a href="../index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>

