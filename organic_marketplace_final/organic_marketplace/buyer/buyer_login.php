<?php
session_start();
require_once '../database/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, name, email, password FROM buyers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $buyer = $result->fetch_assoc();
        if (password_verify($password, $buyer['password'])) {
            $_SESSION['buyer_id'] = $buyer['id'];
            $_SESSION['buyer_name'] = $buyer['name'];
            $_SESSION['buyer_email'] = $buyer['email'];
            header('Location: buyer_dashboard.php');
            exit();
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
    <title>Buyer Login - Organic Marketplace</title>
    <link rel="stylesheet" href="css/login_style.css">
</head>
<body>
    <div class="login-container">
        <h2>Buyer Login</h2>
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
            <a href="buyer_register.php">Don't have an account? Register</a> | 
            <a href="../index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>

