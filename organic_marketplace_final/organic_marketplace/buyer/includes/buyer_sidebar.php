<?php
if (!isset($_SESSION['buyer_id'])) {
    header('Location: buyer_login.php');
    exit();
}
?>
<button class="mobile-menu-toggle" aria-label="Toggle menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>
<div class="mobile-overlay" id="mobile-overlay"></div>
<div class="buyer-sidebar" id="buyer-sidebar" role="navigation" aria-label="Buyer sidebar">
    <div class="sidebar-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="sidebar-avatar" aria-hidden="true" style="width:48px;height:48px;border-radius:50%;background:#fff3;display:flex;align-items:center;justify-content:center;color:#2d5016;font-weight:700;"><?php echo strtoupper(substr($_SESSION['buyer_name'],0,1)); ?></div>
            <div>
                <h2 style="margin:0;font-size:1.05em;"><span class="brand">Buyer Panel</span></h2>
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['buyer_name']); ?></p>
            </div>
        </div>
    </div>
    <nav>
    <ul class="sidebar-menu">
        <li>
            <a href="buyer_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'buyer_dashboard.php' ? 'active' : ''; ?>">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 10.5L12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V10.5z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="label">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="my_cart.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_cart.php' ? 'active' : ''; ?>">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M6 6h15l-1.4 7.2a2 2 0 0 1-2 1.6H9.4a2 2 0 0 1-2-1.6L6 6z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="20" r="1" fill="currentColor"/>
                        <circle cx="18" cy="20" r="1" fill="currentColor"/>
                    </svg>
                </span>
                <span class="label">My Cart</span>
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
                <span class="label">My Orders</span>
            </a>
        </li>
        <li>
            <a href="buyer_logout.php">
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
    </nav>
</div>

