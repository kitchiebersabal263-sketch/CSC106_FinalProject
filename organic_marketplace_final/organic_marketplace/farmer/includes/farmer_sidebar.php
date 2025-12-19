<?php
if (!isset($_SESSION['farmer_id'])) {
    header('Location: farmer_login.php');
    exit();
}

// Get seller type if available
$colCheck = $conn->query("SHOW COLUMNS FROM farmers LIKE 'seller_type'");
$hasSellerType = $colCheck->num_rows > 0;
$seller_type_display = '';
if ($hasSellerType) {
    $stmt = $conn->prepare("SELECT seller_type FROM farmers WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['farmer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $farmer_data = $result->fetch_assoc();
    $seller_type = $farmer_data['seller_type'] ?? 'farmer';
    $stmt->close();
    $seller_type_display = get_seller_type_badge($seller_type, 'small');
}
?>
<button class="mobile-menu-toggle" aria-label="Toggle menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>
<div class="mobile-overlay" id="mobile-overlay"></div>
<div class="farmer-sidebar" id="farmer-sidebar">
    <div class="sidebar-header">
        <h2><span class="brand">Seller Panel</span></h2>
        <p class="user-name"><?php echo htmlspecialchars($_SESSION['farmer_name']); ?></p>
        <?php if ($seller_type_display): ?>
            <div style="margin-top: 8px;">
                <?php echo $seller_type_display; ?>
            </div>
        <?php endif; ?>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="farmer_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'farmer_dashboard.php' ? 'active' : ''; ?>">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 10.5L12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V10.5z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="label">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="add_product.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'add_product.php' ? 'active' : ''; ?>">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="label">Add Product</span>
            </a>
        </li>
        <li>
            <a href="my_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_products.php' ? 'active' : ''; ?>">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="label">My Products</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="label">Profile</span>
            </a>
        </li>
        <li>
            <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="label">Orders</span>
            </a>
        </li>
        <li>
            <a href="farmer_logout.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="label">Logout</span>
            </a>
        </li>
    </ul>
</div>

