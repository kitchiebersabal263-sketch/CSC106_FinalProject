<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['farmer_id'])) {
    header('Location: farmer_login.php');
    exit();
}

$farmer_id = $_SESSION['farmer_id'];
$error = '';
$success = '';

// Load current data
$colCheck = $conn->query("SHOW COLUMNS FROM farmers LIKE 'seller_type'");
$hasSellerType = $colCheck->num_rows > 0;
// Check pickup_location column presence without altering schema
$colPickup = $conn->query("SHOW COLUMNS FROM farmers LIKE 'pickup_location'");
$hasPickupLocation = $colPickup->num_rows > 0;
$fields = $hasSellerType ? "name, email, phone, location" : "name, email, phone, location";
if ($hasPickupLocation) { $fields .= ", pickup_location"; }
if ($hasSellerType) { $fields .= ", seller_type, verification_status"; }
$stmt = $conn->prepare("SELECT {$fields} FROM farmers WHERE id = ?");
$stmt->bind_param('i', $farmer_id);
$stmt->execute();
$res = $stmt->get_result();
$farmer = $res->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $location = sanitize_input($_POST['location'] ?? '');
    $pickup_location = sanitize_input($_POST['pickup_location'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name)) {
        $error = 'Name cannot be empty.';
    } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            if ($hasPickupLocation) {
                $stmt = $conn->prepare("UPDATE farmers SET name = ?, phone = ?, location = ?, pickup_location = ?, password = ? WHERE id = ?");
                $stmt->bind_param('sssssi', $name, $phone, $location, $pickup_location, $hashed, $farmer_id);
            } else {
                $stmt = $conn->prepare("UPDATE farmers SET name = ?, phone = ?, location = ?, password = ? WHERE id = ?");
                $stmt->bind_param('ssssi', $name, $phone, $location, $hashed, $farmer_id);
            }
        } else {
            if ($hasPickupLocation) {
                $stmt = $conn->prepare("UPDATE farmers SET name = ?, phone = ?, location = ?, pickup_location = ? WHERE id = ?");
                $stmt->bind_param('ssssi', $name, $phone, $location, $pickup_location, $farmer_id);
            } else {
                $stmt = $conn->prepare("UPDATE farmers SET name = ?, phone = ?, location = ? WHERE id = ?");
                $stmt->bind_param('sssi', $name, $phone, $location, $farmer_id);
            }
        }
        if ($stmt->execute()) {
            $success = 'Profile updated successfully.';
            $_SESSION['farmer_name'] = $name;
            // reload current data
            $farmer['name'] = $name;
            $farmer['phone'] = $phone;
            $farmer['location'] = $location;
            if ($hasPickupLocation) { $farmer['pickup_location'] = $pickup_location; }
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
    <title>Profile - Farmer</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="css/farmer_style.css">
</head>
<body>
    <div class="farmer-wrapper">
        <?php include 'includes/farmer_sidebar.php'; ?>
        <div class="farmer-content">
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Update your contact and farm information</p>
            </div>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($hasSellerType && isset($farmer['seller_type'])): ?>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>Seller Type:</strong>
                        <div style="margin-top: 8px;">
                            <?php echo get_seller_type_badge($farmer['seller_type'], 'large'); ?>
                        </div>
                        <?php
                        $config = get_seller_type_config($farmer['seller_type']);
                        if ($config):
                        ?>
                            <p style="margin-top: 8px; color: #666; font-size: 0.9em;"><?php echo htmlspecialchars($config['description']); ?></p>
                            <p style="margin-top: 5px; color: #666; font-size: 0.85em;">
                                <strong>Allowed Categories:</strong> <?php echo implode(', ', $config['allowed_categories']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if (isset($farmer['verification_status'])): ?>
                            <?php
                            $status = $farmer['verification_status'] ?? 'pending';
                            $status_colors = ['pending' => '#ff9800', 'approved' => '#4caf50', 'rejected' => '#f44336'];
                            $status_labels = ['pending' => '⏳ Pending', 'approved' => '✅ Approved', 'rejected' => '❌ Rejected'];
                            ?>
                            <p style="margin-top: 10px;">
                                <strong>Verification Status:</strong>
                                <span style="background: <?php echo $status_colors[$status]; ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.85em; margin-left: 8px;">
                                    <?php echo $status_labels[$status] ?? $status; ?>
                                </span>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($farmer['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email (cannot change here)</label>
                        <input type="text" value="<?php echo htmlspecialchars($farmer['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($farmer['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Farm Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($farmer['location'] ?? ''); ?>">
                    </div>
                    <?php if ($hasPickupLocation): ?>
                    <div class="form-group">
                        <label>Pickup Location (where buyers should pick up orders)</label>
                        <input type="text" name="pickup_location" value="<?php echo htmlspecialchars($farmer['pickup_location'] ?? ''); ?>" placeholder="e.g., Public Market Gate 2, Barangay San Juan, 8AM–5PM">
                        <div class="helper-note">Shown to buyers who select pickup.</div>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="password">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </form>
            </div>
        </div>
    </div>
    <script src="js/farmer_script.js"></script>
</body>
</html>
