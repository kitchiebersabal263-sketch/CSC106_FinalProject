<?php
session_start();
require_once 'database/db_connect.php';

// Get featured products (top 6 by sold count or newest)
$featured_products = $conn->query("SELECT p.*, f.name as farmer_name 
                                    FROM products p 
                                    JOIN farmers f ON p.farmer_id = f.id 
                                    WHERE p.quantity > 0 
                                    ORDER BY p.sold DESC, p.created_at DESC 
                                    LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organic Marketplace - Fresh Produce from Cabadbaran City</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-text">Organic Marketplace</span>
            </a>
            <button class="mobile-nav-toggle" aria-label="Toggle navigation">☰</button>
            <nav id="main-nav">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="buyer/buyer_login.php">Login as Buyer</a></li>
                    <li><a href="farmer/farmer_login.php">Login as Farmer</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Fresh Organic Produce from Cabadbaran City</h1>
            <p>Connecting local farmers with conscious buyers for the freshest, healthiest organic products</p>
            <a href="buyer/buyer_login.php" class="cta-button">Start Shopping</a>
        </div>
    </section>

    <!-- Purpose Section -->
    <section class="purpose-section">
        <div class="container">
            <h2 class="section-title">Our Mission</h2>
            <p class="section-subtitle">
                We bridge the gap between local farmers and health-conscious consumers, 
                bringing you the freshest organic produce directly from the farms of Cabadbaran City.
            </p>
            <div class="purpose-content">
                <div class="purpose-card">
                    <div class="purpose-icon">🧑‍🌾</div>
                    <h3>Support Local Farmers</h3>
                    <p>Empower local farmers by providing them with a direct platform to sell their organic produce and reach more customers.</p>
                </div>
                <div class="purpose-card">
                    <div class="purpose-icon">🛒</div>
                    <h3>Fresh & Organic</h3>
                    <p>Access the freshest organic vegetables, fruits, fish, cacao, and eggs directly from trusted local farmers.</p>
                </div>
                <div class="purpose-card">
                    <div class="purpose-icon">🤝</div>
                    <h3>Community Connection</h3>
                    <p>Build a sustainable community where farmers and buyers connect, fostering local economic growth and healthy living.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-section">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <p class="section-subtitle">Discover our most popular organic products from local farmers</p>
            <div class="products-grid">
                <?php if ($featured_products->num_rows > 0): ?>
                    <?php while ($product = $featured_products->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image-wrapper">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    🌿
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars(!empty($product['category']) ? $product['category'] : '—'); ?></div>
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                <?php if (!empty($product['description'])): ?>
                                    <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 120)); ?></p>
                                <?php endif; ?>
                                <div class="product-location">
                                    <span>📍</span>
                                    <span><?php echo htmlspecialchars($product['location']); ?></span>
                                </div>
                                <a href="buyer/buyer_login.php" class="view-btn"><span>View Details</span></a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No featured products available.</p>
                    <?php endif; ?>
                </div> <!-- END products-grid -->
            </div> <!-- END container -->
        </section> <!-- END featured-section -->

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>🌱 Organic Marketplace</h3>
                <p>Your trusted platform for fresh, organic produce from Cabadbaran City. Connecting local farmers with health-conscious consumers.</p>
                <div class="social-icons">
                    <a href="#" class="social-icon" aria-label="Facebook">📘</a>
                    <a href="#" class="social-icon" aria-label="Instagram">📷</a>
                    <a href="#" class="social-icon" aria-label="Twitter">🐦</a>
                    <a href="#" class="social-icon" aria-label="Email">✉️</a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <p><a href="index.php">Home</a></p>
                <p><a href="about.php">About Us</a></p>
                <p><a href="contact.php">Contact</a></p>
                <p><a href="buyer/buyer_register.php">Register as Buyer</a></p>
                <p><a href="farmer/farmer_register.php">Register as Farmer</a></p>
            </div>
            <div class="footer-section">
                <h3>Contact Information</h3>
                <p>📍 P-3, Brgy. 9 Poblacion, Cabadbaran, Philippines</p>
                <p>📞 +63 920 213 9278</p>
                <p>✉️ cityagriculturecbr@gmail.com</p>
                <p>🕒 Mon - Fri: 9:00 AM - 6:00 PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Organic Marketplace. All rights reserved.</p>
        </div>
    </footer>
    <script>
        // Mobile navigation toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
            const mainNav = document.getElementById('main-nav');
            
            if (mobileNavToggle && mainNav) {
                mobileNavToggle.addEventListener('click', function() {
                    mainNav.classList.toggle('mobile-open');
                });
            }
        });
    </script>
</body>
</html>