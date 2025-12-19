<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['buyer_id'])) {
    header('Location: buyer_login.php');
    exit();
}

$buyer_id = $_SESSION['buyer_id'];
$error = '';
$success = '';

// Load current data
$stmt = $conn->prepare("SELECT name, email, phone, address FROM buyers WHERE id = ?");
$stmt->bind_param('i', $buyer_id);
$stmt->execute();
$res = $stmt->get_result();
$buyer = $res->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name)) {
        $error = 'Name cannot be empty.';
    } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE buyers SET name = ?, phone = ?, address = ?, password = ? WHERE id = ?");
            $stmt->bind_param('ssssi', $name, $phone, $address, $hashed, $buyer_id);
        } else {
            $stmt = $conn->prepare("UPDATE buyers SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->bind_param('sssi', $name, $phone, $address, $buyer_id);
        }
        if ($stmt->execute()) {
            $success = 'Profile updated successfully.';
            $_SESSION['buyer_name'] = $name;
            // reload current data
            $buyer['name'] = $name;
            $buyer['phone'] = $phone;
            $buyer['address'] = $address;
        } else {
            $error = 'Failed to update profile: ' . $conn->error;
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
    <title>Profile - Buyer</title>
    <link rel="stylesheet" href="css/buyer_style.css">
    <link rel="stylesheet" href="css/profile_style.css">
</head>
<body>
    <div class="buyer-wrapper">
        <?php include 'includes/buyer_sidebar.php'; ?>
        <div class="buyer-content">
            <div class="profile-form-container">
                <div class="profile-header">
                    <h1>My Profile</h1>
                    <p>Update your contact and delivery information</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-field">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($buyer['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-field">
                            <label>Email</label>
                            <input type="text" value="<?php echo htmlspecialchars($buyer['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($buyer['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-field">
                            <label>New Password (leave blank to keep current)</label>
                            <input type="password" name="password">
                        </div>
                    </div>

                    <div class="form-row full-width">
                        <div class="form-field">
                            <label>Delivery Address</label>
                            <textarea name="address" placeholder="Street, House No."><?php echo htmlspecialchars($buyer['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">SAVE PROFILE</button>
                </form>
            </div>
        </div>
    </div>
    <script src="js/buyer_script.js"></script>
</body>
</html>