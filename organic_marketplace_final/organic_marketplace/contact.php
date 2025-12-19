<?php
session_start();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // In a real application, you would send an email or save to database here
        // For now, we'll just show a success message
        $success_message = 'Thank you for contacting us! We will get back to you soon.';
        
        // Clear form data
        $name = $email = $subject = $message = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Organic Marketplace</title>
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

    <!-- Contact Hero -->
    <section class="contact-hero">
        <h1>Get in Touch</h1>
        <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="contact-wrapper">
            <!-- Contact Form -->
            <div class="contact-form-container">
                <h2>Send Us a Message</h2>
                
                <?php if ($success_message): ?>
                    <div class="alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Your Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="General Inquiry" <?php echo (isset($subject) && $subject == 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="Product Question" <?php echo (isset($subject) && $subject == 'Product Question') ? 'selected' : ''; ?>>Product Question</option>
                            <option value="Farmer Registration" <?php echo (isset($subject) && $subject == 'Farmer Registration') ? 'selected' : ''; ?>>Farmer Registration</option>
                            <option value="Buyer Support" <?php echo (isset($subject) && $subject == 'Buyer Support') ? 'selected' : ''; ?>>Buyer Support</option>
                            <option value="Technical Issue" <?php echo (isset($subject) && $subject == 'Technical Issue') ? 'selected' : ''; ?>>Technical Issue</option>
                            <option value="Other" <?php echo (isset($subject) && $subject == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message *</label>
                        <textarea id="message" name="message" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>

            <!-- Contact Information -->
            <div>
                <div class="contact-info-container">
                    <h2>Contact Information</h2>
                    
                    <div class="contact-detail">
                        <div class="contact-detail-icon">📍</div>
                        <div class="contact-detail-content">
                            <h3>Address</h3>
                            <p>P-3, Brgy. 9 Poblacion, Cabadbaran <br>Philippines</p>
                        </div>
                    </div>
                    
                    <div class="contact-detail">
                        <div class="contact-detail-icon">📧</div>
                        <div class="contact-detail-content">
                            <h3>Email</h3>
                            <p>cityagriculturecbr@gmail.com<br>cityagriculturecbr@gmail.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-detail">
                        <div class="contact-detail-icon">📞</div>
                        <div class="contact-detail-content">
                            <h3>Phone</h3>
                            <p>+63 920 213 9278<br>Available Mon-Fri, 8AM-6PM</p>
                        </div>
                    </div>
                    
                    <div class="contact-detail">
                        <div class="contact-detail-icon">🕒</div>
                        <div class="contact-detail-content">
                            <h3>Business Hours</h3>
                            <p>Monday - Friday: 8:00 AM - 6:00 PM<br>Saturday: 9:00 AM - 2:00 PM<br>Sunday: Closed</p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <h3 style="color: var(--primary-green); margin-bottom: 15px;">Follow Us</h3>
                        <div class="social-icons">
                            <a href="#" class="social-icon" aria-label="Facebook" style="background: rgba(74, 124, 89, 0.1);">📘</a>
                            <a href="#" class="social-icon" aria-label="Instagram" style="background: rgba(74, 124, 89, 0.1);">📷</a>
                            <a href="#" class="social-icon" aria-label="Twitter" style="background: rgba(74, 124, 89, 0.1);">🐦</a>
                            <a href="#" class="social-icon" aria-label="Email" style="background: rgba(74, 124, 89, 0.1);">✉️</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="container" style="margin-top: 50px;">
            <h2 class="section-title" style="margin-bottom: 30px;">📍 Find Us</h2>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d15410.5!2d125.5420!3d9.1227!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zOS4xMjI3IMKwMTIzLjU0MjAg4oCXY9C-!5e0!3m2!1sen!2sph!4v0000000000000"
                    width="600"
                    height="450"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                    title="Cabadbaran City Location">
                </iframe>
            </div>
            <p style="text-align: center; margin-top: 20px; color: var(--gray-text);">
                    P-3, Brgy. 9 Poblacion, Cabadbaran
                    Philippines
            </p>
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
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Organic Marketplace. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
