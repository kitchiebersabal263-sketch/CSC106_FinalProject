<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['farmer_id'])) {
    header('Location: farmer_login.php');
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

// Check if seller type exists
$colCheck = $conn->query("SHOW COLUMNS FROM farmers LIKE 'seller_type'");
$hasSellerType = $colCheck->num_rows > 0;

// Get farmer info including seller type
if ($hasSellerType) {
    $stmt = $conn->prepare("SELECT seller_type, verification_status FROM farmers WHERE id = ?");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $farmer_info = $result->fetch_assoc();
    $seller_type = $farmer_info['seller_type'] ?? 'farmer';
    $stmt->close();
} else {
    $seller_type = 'farmer';
}

// Get statistics
$stats = [];

// Total Products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['products'] = $result->fetch_assoc()['count'];
$stmt->close();

// Total Sold
$stmt = $conn->prepare("SELECT SUM(sold) as total FROM products WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['sold'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total Revenue
$stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE farmer_id = ? AND status = 'Delivered'");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['revenue'] = $result->fetch_assoc()['total'];
$stmt->close();

// Pending Orders
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE farmer_id = ? AND status = 'Pending'");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_orders'] = $result->fetch_assoc()['count'];
$stmt->close();

// Delivered Orders
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE farmer_id = ? AND status = 'Delivered'");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['completed_orders'] = $result->fetch_assoc()['count'];
$stmt->close();

// Average Order Value
$stmt = $conn->prepare("SELECT COALESCE(AVG(total), 0) as avg FROM orders WHERE farmer_id = ? AND status = 'Delivered'");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['avg_order_value'] = $result->fetch_assoc()['avg'];
$stmt->close();

// Top-Selling Item
$stmt = $conn->prepare("SELECT name, sold FROM products WHERE farmer_id = ? ORDER BY sold DESC LIMIT 1");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$top_item = $result->fetch_assoc();
$stats['top_item'] = $top_item ? $top_item['name'] : 'None';
$stmt->close();

// Recent Orders (last 3)
$stmt = $conn->prepare("SELECT o.*, p.name as product_name, p.category, b.name as buyer_name 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        JOIN buyers b ON o.buyer_id = b.id 
                        WHERE o.farmer_id = ? 
                        ORDER BY o.order_date DESC 
                        LIMIT 3");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$recent_orders = $stmt->get_result();
$stmt->close();

// Best sellers this month (city-wide) - top 5 by quantity sold this month
$month = date('n');
$year = date('Y');
$best_sellers = [];
$stmt = $conn->prepare("SELECT p.id, p.name, p.category, SUM(o.quantity) AS qty_sold
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE MONTH(o.order_date) = ? AND YEAR(o.order_date) = ?
    GROUP BY p.id
    ORDER BY qty_sold DESC
    LIMIT 5");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $best_sellers[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - Organic Marketplace</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="css/farmer_style.css">
</head>
<body>
    <div class="farmer-wrapper">
        <?php include 'includes/farmer_sidebar.php'; ?>
        <div class="farmer-content">
            <div class="page-header">
                <div class="header-content">
                    <div>
                        <h1>🧑‍🌾 Dashboard</h1>
                        <p class="welcome-text">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['farmer_name']); ?></strong>! Here's your overview</p>
                    </div>
                    <?php if ($hasSellerType): ?>
                        <div class="header-badge">
                            <?php echo get_seller_type_badge($seller_type, 'medium'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card clickable" onclick="window.location.href='my_products.php'">
                    <div class="stat-icon">📦</div>
                    <h3>Total Products</h3>
                    <div class="stat-value"><?php echo $stats['products']; ?></div>
                    <div class="stat-label">Listed products</div>
                    <div class="stat-link">Manage Products →</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <h3>Total Sold</h3>
                    <div class="stat-value"><?php echo $stats['sold']; ?></div>
                    <div class="stat-label">Units sold</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <h3>Total Revenue</h3>
                    <div class="stat-value">₱<?php echo number_format($stats['revenue'], 2); ?></div>
                    <div class="stat-label">From delivered orders</div>
                </div>
                <div class="stat-card clickable" onclick="window.location.href='orders.php?filter=pending'">
                    <div class="stat-icon">⏳</div>
                    <h3>Pending Orders</h3>
                    <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label">Awaiting action</div>
                    <div class="stat-link">View Orders →</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <h3>Delivered Orders</h3>
                    <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                    <div class="stat-label">Delivered orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <h3>Avg Order Value</h3>
                    <div class="stat-value">₱<?php echo number_format($stats['avg_order_value'], 2); ?></div>
                    <div class="stat-label">Per delivered order</div>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <?php if ($recent_orders->num_rows > 0): ?>
            <div class="recent-orders-section">
                <div class="section-header">
                    <h2>📦 Recent Orders</h2>
                    <p class="section-subtitle">Your latest order activity</p>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Buyer</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recent_orders->data_seek(0);
                            while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['product_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['category']); ?></td>
                                    <td><?php echo $order['quantity']; ?></td>
                                    <td><strong>₱<?php echo number_format($order['total'], 2); ?></strong></td>
                                    <td>
                                        <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="section-footer">
                    <a href="orders.php" class="btn btn-secondary">View All Orders</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Best Sellers This Month -->
            <?php if (count($best_sellers) > 0): ?>
            <div class="best-sellers-section">
                <div class="section-header">
                    <h2>🏆 Best Sellers This Month</h2>
                    <p class="section-subtitle">Top performing products this month</p>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Quantity Sold</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($best_sellers as $bs): ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge rank-<?php echo $rank; ?>">
                                            <?php echo $rank; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($bs['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($bs['category']); ?></td>
                                    <td><strong class="sold-count"><?php echo intval($bs['qty_sold']); ?></strong></td>
                                </tr>
                            <?php 
                            $rank++;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/farmer_script.js"></script>
</body>
</html>

