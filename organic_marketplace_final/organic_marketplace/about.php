<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Organic Marketplace Cabadbaran</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-text">Organic Marketplace</span>
            </a>
            <nav>
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

    <!-- Hero Banner -->
    <section class="about-hero">
        <div>
            <h1>About Organic Marketplace Cabadbaran</h1>
        </div>
    </section>

    <!-- Main Content -->
    <section class="purpose-section">
        <div class="container">
            <p class="section-subtitle" style="text-align: left; max-width: 100%; margin-bottom: 30px; font-size: 1.15em; line-height: 1.8;">
                Organic Marketplace Cabadbaran is a revolutionary digital platform that connects local farmers from Cabadbaran City, Agusan del Norte, with buyers and retailers who value fresh, organic, and locally-sourced produce. Our mission is to promote sustainable agriculture, support local farming communities, and make organic products more accessible to everyone.
            </p>
            <p class="section-subtitle" style="text-align: left; max-width: 100%; margin-bottom: 30px; font-size: 1.15em; line-height: 1.8;">
                Through our innovative online marketplace, farmers can easily list and sell their organic vegetables, fruits, fish, cacao, and eggs directly to consumers. This direct connection eliminates middlemen, ensures fair prices for farmers, and guarantees the freshest products for buyers. We are proud to support Cabadbaran's agricultural heritage while building a sustainable future for our community.
            </p>

            <!-- Mission, Vision, Values -->
            <div class="mission-vision-values">
                <div class="mvv-card">
                    <div class="mvv-icon">🎯</div>
                    <h3>Our Mission</h3>
                    <p>To empower local Cabadbaran farmers by providing them with an easy-to-use online platform to sell their organic produce directly to buyers and retailers, eliminating barriers and ensuring fair trade practices that benefit both farmers and consumers.</p>
                </div>
                <div class="mvv-card">
                    <div class="mvv-icon">👁️</div>
                    <h3>Our Vision</h3>
                    <p>To become the leading digital marketplace for organic produce in the Caraga region, empowering Cabadbaran's local agriculture industry and promoting sustainable farming practices that preserve our environment for future generations.</p>
                </div>
                <div class="mvv-card">
                    <div class="mvv-icon">💚</div>
                    <h3>Our Values</h3>
                    <p>We are committed to sustainability, trust, and community. We believe in supporting local farmers, promoting organic agriculture, building transparent relationships, and fostering a strong sense of community among all our stakeholders.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Proudly Made Section -->
    <section class="purpose-section" style="background: var(--light-beige);">
        <div class="container">
            <div class="proud-section">
                <h2>Proudly Made in Cabadbaran City</h2>
                <p style="color: var(--gray-text); font-size: 1.2em; max-width: 700px; margin: 0 auto;">
                    Supporting local farmers and celebrating the rich agricultural heritage of Cabadbaran City, Agusan del Norte
                </p>
                <div class="proud-icons">
                    <span class="proud-icon">🌾</span>
                    <span class="proud-icon">🌿</span>
                    <span class="proud-icon">🌱</span>
                    <span class="proud-icon">🍃</span>
                </div>
            </div>
        </div>
    </section>

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
                <p><a href="about.php">About</a></p>
                <p><a href="contact.php">Contact</a></p>
                <p><a href="buyer/buyer_register.php">Register as Buyer</a></p>
                <p><a href="farmer/farmer_register.php">Register as Farmer</a></p>
            </div>
            <div class="footer-section">
                <h3>Contact Information</h3>
                <p>📍 P-3, Brgy. 9 Poblacion, Cabadbaran, Philippines</p>
                <p>📞 +63 920 213 9278</p>
                <p>✉️ cityagriculturecbr@gmail.com</p>
                <p>🕒 Mon - Fri: 8:00 AM - 6:00 PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Organic Marketplace. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
