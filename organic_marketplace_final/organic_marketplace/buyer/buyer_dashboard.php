<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['buyer_id'])) {
    header('Location: buyer_login.php');
    exit();
}

$buyer_id = $_SESSION['buyer_id'];

// Get buyer statistics
$stats = [];

// Total Orders
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE buyer_id = ?");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_orders'] = $result->fetch_assoc()['count'];
$stmt->close();

// Cart Items
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE buyer_id = ?");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['cart_items'] = $result->fetch_assoc()['count'];
$stmt->close();

// Total Spent
$stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE buyer_id = ? AND status = 'Delivered'");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_spent'] = $result->fetch_assoc()['total'];
$stmt->close();

// Pending Orders
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE buyer_id = ? AND status = 'Pending'");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_orders'] = $result->fetch_assoc()['count'];
$stmt->close();

// Get best sellers (top 10) with full product details for carousel
$best_sellers = $conn->query("SELECT p.*, f.name as farmer_name 
                              FROM products p 
                              JOIN farmers f ON p.farmer_id = f.id 
                              WHERE p.sold > 0 AND p.quantity > 0
                              ORDER BY p.sold DESC 
                              LIMIT 10");

// Products to display on dashboard (supports search q)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$products_query = "SELECT p.*, f.name as farmer_name FROM products p JOIN farmers f ON p.farmer_id = f.id WHERE p.quantity > 0";
if ($q !== '') {
    $safe_q = $conn->real_escape_string($q);
    $products_query .= " AND (p.name LIKE '%" . $safe_q . "%' OR p.description LIKE '%" . $safe_q . "%' OR p.category LIKE '%" . $safe_q . "%')";
}
$products_query .= " ORDER BY p.created_at DESC";
$products = $conn->query($products_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - Organic Marketplace</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="css/buyer_style.css">
    <style>
        /* Carousel Styles */
        .carousel-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .carousel-wrapper {
            display: flex;
            gap: 20px;
            will-change: transform;
        }
        
        .carousel-item {
            min-width: 280px;
            flex: 0 0 280px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(46,125,50,0.08);
        }
        
        .carousel-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .carousel-item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f5f5f5;
        }
        
        .carousel-item-content {
            padding: 15px;
        }
        
        .carousel-item-name {
            font-weight: 600;
            font-size: 1em;
            margin-bottom: 8px;
            color: #333;
        }
        
        .carousel-item-category {
            color: #666;
            font-size: 0.85em;
            margin-bottom: 8px;
        }
        
        .carousel-item-price {
            font-size: 1.2em;
            font-weight: 700;
            color: #4a7c59;
            margin-bottom: 8px;
        }
        
        .carousel-item-sold {
            font-size: 0.85em;
            color: #999;
            margin-bottom: 10px;
        }
        
        .carousel-item-link {
            display: inline-block;
            width: 100%;
            text-align: center;
            padding: 8px;
            background: #4a7c59;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9em;
            transition: background 0.3s;
        }
        
        .carousel-item-link:hover {
            background: #3d6549;
        }
        
    </style>
</head>
<body>
    <div class="buyer-wrapper">
        <?php include 'includes/buyer_sidebar.php'; ?>
        <div class="buyer-content">
            <div class="container">
                <header class="page-header">
                    <div class="header-content">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['buyer_name']); ?></h1>
                        <p class="welcome-text">Discover fresh organic products from local farmers — curated for you.</p>
                    </div>
                    <form class="search-form" method="get" action="buyer_dashboard.php" role="search">
                        <div class="search-input-wrapper">
                            <input type="search" name="q" class="search-input" placeholder="Search products, categories or farmers" value="<?php echo htmlspecialchars($q, ENT_QUOTES); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary search-btn">Search</button>
                    </form>
                </header>

                <!-- Statistics Cards Container -->
                <div class="stats-container">
                    <div class="stats-grid">
                        <div class="stat-card clickable" onclick="window.location.href='orders.php'">
                            <div class="stat-icon">📋</div>
                            <h3>Total Orders</h3>
                            <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                            <div class="stat-label">All time orders</div>
                            <div class="stat-link">View Orders →</div>
                        </div>
                        <div class="stat-card clickable" onclick="window.location.href='my_cart.php'">
                            <div class="stat-icon">🛒</div>
                            <h3>Cart Items</h3>
                            <div class="stat-value"><?php echo $stats['cart_items']; ?></div>
                            <div class="stat-label">Items in cart</div>
                            <div class="stat-link">View Cart →</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">💰</div>
                            <h3>Total Spent</h3>
                            <div class="stat-value">₱<?php echo number_format($stats['total_spent'], 2); ?></div>
                            <div class="stat-label">Completed orders</div>
                        </div>
                        <div class="stat-card clickable" onclick="window.location.href='orders.php?filter=pending'">
                            <div class="stat-icon">⏳</div>
                            <h3>Pending Orders</h3>
                            <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                            <div class="stat-label">Awaiting delivery</div>
                            <div class="stat-link">View Orders →</div>
                        </div>
                    </div>
                </div>

            <!-- Best Sellers Carousel Section -->
            <?php if ($best_sellers->num_rows > 0): ?>
            <div class="best-sellers-section">
                <div class="section-header">
                    <h2>🏆 Best-Selling Products</h2>
                    <p class="section-subtitle">Most popular products by sales volume</p>
                </div>
                <div class="carousel-container">
                    <div class="carousel-wrapper" id="bestSellersCarousel">
                        <?php 
                        $best_sellers->data_seek(0);
                        while ($product = $best_sellers->fetch_assoc()): ?>
                            <div class="carousel-item">
                                <?php if (!empty($product['image']) && file_exists('../' . $product['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="carousel-item-image"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="carousel-item-image" style="display: none; align-items: center; justify-content: center; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #4a7c59; font-weight: 500;">🖼️ No Image</div>
                                <?php else: ?>
                                    <div class="carousel-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #4a7c59; font-weight: 500;">🖼️ No Image</div>
                                <?php endif; ?>
                                <div class="carousel-item-content">
                                    <div class="carousel-item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="carousel-item-category"><?php echo htmlspecialchars($product['category'] ?? '—'); ?></div>
                                    <div class="carousel-item-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                    <div class="carousel-item-sold">⭐ <?php echo $product['sold']; ?> sold</div>
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="carousel-item-link">View Details</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Products Section -->
            <?php if ($products && $products->num_rows > 0): ?>
            <div class="products-section">
                <div class="section-header">
                    <h2>🛍️ Available Products</h2>
                    <p class="section-subtitle">Browse our fresh organic products</p>
                </div>
                <div class="products-grid">
                    <?php 
                    $products->data_seek(0);
                    while ($product = $products->fetch_assoc()): ?>
                        <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>">
                            <?php if (!empty($product['image']) && file_exists('../' . $product['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="product-image" style="display: none; align-items: center; justify-content: center; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #4a7c59; font-weight: 500;">🖼️ No Image Available</div>
                            <?php else: ?>
                                <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #4a7c59; font-weight: 500;">🖼️ No Image Available</div>
                            <?php endif; ?>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-category"><?php echo htmlspecialchars(!empty($product['category']) ? $product['category'] : '—'); ?></div>
                                <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-location">📍 <?php echo htmlspecialchars($product['location']); ?></div>
                                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <p>No products found</p>
            </div>
            <?php endif; ?>
            </div> <!-- .container -->
        </div>
    </div>
    <script src="js/buyer_script.js"></script>
    <script>
        // Continuous cylinder-style carousel - smooth infinite scrolling
        (function() {
            const carousel = document.getElementById('bestSellersCarousel');
            const container = carousel?.parentElement;
            
            if (!carousel || !container) return;
            
            const items = Array.from(carousel.querySelectorAll('.carousel-item'));
            if (items.length === 0) return;
            
            const totalItems = items.length;
            const itemWidth = 280;
            const gap = 20;
            const itemTotalWidth = itemWidth + gap;
            
            // Clone items multiple times for seamless infinite loop
            // Clone enough sets to ensure smooth continuous scrolling (at least 2 full sets)
            for (let i = 0; i < 2; i++) {
                items.forEach(item => {
                    const clone = item.cloneNode(true);
                    carousel.appendChild(clone);
                });
            }
            
            // Calculate total width of one set of original items
            const totalWidth = totalItems * itemTotalWidth;
            
            // Continuous scrolling variables
            let scrollPosition = 0;
            let isPaused = false;
            let animationFrameId;
            const scrollSpeed = 1.5; // pixels per frame - visible circulation speed
            
            // Update carousel position continuously
            function animate() {
                if (!isPaused) {
                    scrollPosition += scrollSpeed;
                    
                    // Reset position seamlessly when we've scrolled through one complete set
                    // This creates the infinite loop effect
                    if (scrollPosition >= totalWidth) {
                        scrollPosition = scrollPosition % totalWidth;
                    }
                    
                    carousel.style.transform = `translateX(-${scrollPosition}px)`;
                }
                
                animationFrameId = requestAnimationFrame(animate);
            }
            
            // Pause on hover
            container.addEventListener('mouseenter', () => {
                isPaused = true;
            });
            
            container.addEventListener('mouseleave', () => {
                isPaused = false;
            });
            
            // Start continuous animation immediately
            // Use setTimeout to ensure DOM is ready and styles are applied
            setTimeout(() => {
                animate();
            }, 100);
        })();
    </script>
</body>
</html>

