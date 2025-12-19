<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get statistics
$stats = [];

// Total Farmers
$result = $conn->query("SELECT COUNT(*) as count FROM farmers");
$stats['farmers'] = $result->fetch_assoc()['count'];

// Total Buyers
$result = $conn->query("SELECT COUNT(*) as count FROM buyers");
$stats['buyers'] = $result->fetch_assoc()['count'];

// Total Products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$stats['products'] = $result->fetch_assoc()['count'];

// Total Orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['orders'] = $result->fetch_assoc()['count'];

// Total Revenue
$result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE status = 'Delivered'");
$stats['revenue'] = $result->fetch_assoc()['total'];

// Pending Orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Active Products (with quantity > 0)
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity > 0");
$stats['active_products'] = $result->fetch_assoc()['count'];

// Delivered Orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Delivered'");
$stats['completed_orders'] = $result->fetch_assoc()['count'];

// Recent Orders (last 3)
$recent_orders = $conn->query("SELECT o.*, p.name as product_name, p.category, 
                                f.name as farmer_name, b.name as buyer_name 
                                FROM orders o 
                                JOIN products p ON o.product_id = p.id 
                                JOIN farmers f ON o.farmer_id = f.id 
                                JOIN buyers b ON o.buyer_id = b.id 
                                ORDER BY o.order_date DESC 
                                LIMIT 3");

// Top 5 Best-Selling Products
$top_products = $conn->query("SELECT p.name, p.category, p.sold, p.location as product_location, f.name as farmer_name 
                              FROM products p 
                              JOIN farmers f ON p.farmer_id = f.id 
                              WHERE p.sold > 0
                              ORDER BY p.sold DESC 
                              LIMIT 5");

$top_products_data = [];
if ($top_products->num_rows > 0) {
    $top_products->data_seek(0);
    while ($row = $top_products->fetch_assoc()) {
        $top_products_data[] = $row;
    }
}

// PIE CHART DATA - Order Status Distribution
$order_status_data = [];
$status_query = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $status_query->fetch_assoc()) {
    $order_status_data[] = [
        'label' => $row['status'],
        'count' => (int)$row['count']
    ];
}

// PIE CHART DATA - Product Category Distribution
$category_data = [];
$category_query = $conn->query("SELECT category, COUNT(*) as count FROM products GROUP BY category");
while ($row = $category_query->fetch_assoc()) {
    $category_data[] = [
        'label' => $row['category'],
        'count' => (int)$row['count']
    ];
}

// LINE CHART DATA - Sales Over Time (Last 12 Months)
$monthly_sales = [];
$sales_query = $conn->query("SELECT 
    DATE_FORMAT(order_date, '%Y-%m') as month,
    DATE_FORMAT(order_date, '%b %Y') as month_label,
    COUNT(*) as order_count,
    COALESCE(SUM(total), 0) as total_revenue,
    COALESCE(SUM(quantity), 0) as total_quantity
    FROM orders 
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month ASC");
while ($row = $sales_query->fetch_assoc()) {
    $monthly_sales[] = [
        'month' => $row['month'],
        'label' => $row['month_label'],
        'orders' => (int)$row['order_count'],
        'revenue' => (float)$row['total_revenue'],
        'quantity' => (int)$row['total_quantity']
    ];
}

// BAR CHART DATA - Top 10 Products by Sales
$top_products_chart = [];
$top_products_query = $conn->query("SELECT p.name, p.sold 
                                     FROM products p 
                                     WHERE p.sold > 0
                                     ORDER BY p.sold DESC 
                                     LIMIT 10");
while ($row = $top_products_query->fetch_assoc()) {
    $top_products_chart[] = [
        'name' => $row['name'],
        'sold' => (int)$row['sold']
    ];
}

// BAR CHART DATA - Top 10 Farmers by Revenue
$top_farmers_chart = [];
$top_farmers_query = $conn->query("SELECT 
    f.name as farmer_name,
    COALESCE(SUM(o.total), 0) as total_revenue,
    COUNT(o.id) as order_count
    FROM farmers f
    LEFT JOIN orders o ON f.id = o.farmer_id AND o.status = 'Delivered'
    GROUP BY f.id, f.name
    HAVING total_revenue > 0
    ORDER BY total_revenue DESC
    LIMIT 10");
while ($row = $top_farmers_query->fetch_assoc()) {
    $top_farmers_chart[] = [
        'name' => $row['farmer_name'],
        'revenue' => (float)$row['total_revenue'],
        'orders' => (int)$row['order_count']
    ];
}

$page_title = 'Admin Dashboard';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <div class="header-content">
        <div>
            <h1>Dashboard Overview</h1>
            <p class="welcome-text">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>! Here's your system summary.</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card clickable" onclick="window.location.href='manage_users.php'">
        <div class="stat-icon">🧑‍🌾</div>
        <h3>Total Farmers</h3>
        <div class="stat-value"><?php echo $stats['farmers']; ?></div>
        <div class="stat-label">Registered farmers</div>
        <div class="stat-link">View All →</div>
    </div>
    <div class="stat-card clickable" onclick="window.location.href='manage_users.php'">
        <div class="stat-icon">🛒</div>
        <h3>Total Buyers</h3>
        <div class="stat-value"><?php echo $stats['buyers']; ?></div>
        <div class="stat-label">Active buyers</div>
        <div class="stat-link">View All →</div>
    </div>
    <div class="stat-card clickable" onclick="window.location.href='manage_products.php'">
        <div class="stat-icon">📦</div>
        <h3>Total Products</h3>
        <div class="stat-value"><?php echo $stats['products']; ?></div>
        <div class="stat-label">Listed products</div>
        <div class="stat-link">Manage Products →</div>
    </div>
    <div class="stat-card clickable" onclick="window.location.href='manage_orders.php'">
        <div class="stat-icon">📋</div>
        <h3>Total Orders</h3>
        <div class="stat-value"><?php echo $stats['orders']; ?></div>
        <div class="stat-label">All orders</div>
        <div class="stat-link">View Orders →</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <h3>Total Revenue</h3>
        <div class="stat-value">₱<?php echo number_format($stats['revenue'], 2); ?></div>
        <div class="stat-label">From delivered orders</div>
    </div>
    <div class="stat-card clickable" onclick="window.location.href='manage_orders.php?filter=pending'">
        <div class="stat-icon">⏳</div>
        <h3>Pending Orders</h3>
        <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
        <div class="stat-label">Awaiting action</div>
        <div class="stat-link">View Orders →</div>
    </div>
    <div class="stat-card clickable" onclick="window.location.href='manage_products.php?filter=active'">
        <div class="stat-icon">✅</div>
        <h3>Active Products</h3>
        <div class="stat-value"><?php echo $stats['active_products']; ?></div>
        <div class="stat-label">In stock</div>
        <div class="stat-link">View Products →</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🎯</div>
        <h3>Delivered Orders</h3>
        <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
        <div class="stat-label">Delivered orders</div>
    </div>
</div>

<!-- Recent Orders Section -->
<?php if ($recent_orders->num_rows > 0): ?>
<div class="recent-orders-section">
    <div class="section-header">
        <h2>📦 Recent Orders</h2>
        <p class="section-subtitle">Latest order activity across the platform</p>
    </div>
    <div class="orders-list">
        <?php while ($order = $recent_orders->fetch_assoc()): ?>
            <div class="order-item">
                <div class="order-info">
                    <div class="order-product-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                    <div class="order-meta">
                        <span class="order-farmer">Farmer: <?php echo htmlspecialchars($order['farmer_name']); ?></span>
                        <span class="order-buyer">Buyer: <?php echo htmlspecialchars($order['buyer_name']); ?></span>
                        <span class="order-category"><?php echo htmlspecialchars($order['category']); ?></span>
                    </div>
                    <div class="order-details">
                        <span>Qty: <?php echo $order['quantity']; ?></span>
                        <span>•</span>
                        <span>Total: ₱<?php echo number_format($order['total'], 2); ?></span>
                        <span>•</span>
                        <span class="order-status status-<?php echo strtolower($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                    </div>
                    <div class="order-date"><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <div class="section-footer">
        <a href="manage_orders.php" class="btn btn-secondary">View All Orders</a>
    </div>
</div>
<?php endif; ?>

<!-- Charts Section -->
<div class="charts-section">
    <div class="charts-grid">
        <!-- Pie Chart - Order Status Distribution -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>📊 Order Status Distribution</h3>
                <p>Breakdown of orders by status</p>
            </div>
            <div class="chart-container">
                <canvas id="orderStatusPieChart"></canvas>
            </div>
        </div>

        <!-- Pie Chart - Product Category Distribution -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>🥬 Product Categories</h3>
                <p>Distribution of products by category</p>
            </div>
            <div class="chart-container">
                <canvas id="categoryPieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Line Chart - Sales Over Time -->
    <div class="chart-card full-width">
        <div class="chart-header">
            <h3>📈 Sales & Orders Trend (Last 12 Months)</h3>
            <p>Revenue and order volume over time</p>
        </div>
        <div class="chart-container">
            <canvas id="salesLineChart"></canvas>
        </div>
    </div>

    <!-- Bar Charts Grid -->
    <div class="charts-grid">
        <!-- Bar Chart - Top Products by Sales -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>🏆 Top Products by Sales</h3>
                <p>Best-selling products</p>
            </div>
            <div class="chart-container">
                <canvas id="topProductsBarChart"></canvas>
            </div>
        </div>

        <!-- Bar Chart - Top Farmers by Revenue -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>👨‍🌾 Top Farmers by Revenue</h3>
                <p>Highest earning farmers</p>
            </div>
            <div class="chart-container">
                <canvas id="topFarmersBarChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Best-Selling Products Table -->
<?php if (count($top_products_data) > 0): ?>
<div class="top-products-section">
    <div class="section-header">
        <h2>🏆 Top 5 Best-Selling Products</h2>
        <p class="section-subtitle">Most popular products by sales volume</p>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Farmer</th>
                    <th>Location</th>
                    <th>Units Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_products_data as $index => $product): ?>
                    <tr>
                        <td>
                            <span class="rank-badge rank-<?php echo $index + 1; ?>">
                                <?php echo $index + 1; ?>
                            </span>
                        </td>
                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td><?php echo htmlspecialchars($product['farmer_name']); ?></td>
                        <td><?php echo htmlspecialchars(!empty($product['product_location']) ? $product['product_location'] : '—'); ?></td>
                        <td><strong class="sold-count"><?php echo $product['sold']; ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">📊</div>
    <p>No sales data available yet. Products will appear here once they start selling.</p>
</div>
<?php endif; ?>

<!-- Chart Data for JavaScript -->
<script>
    // Chart data from PHP
    const orderStatusData = <?php echo json_encode($order_status_data); ?>;
    const categoryData = <?php echo json_encode($category_data); ?>;
    const monthlySalesData = <?php echo json_encode($monthly_sales); ?>;
    const topProductsData = <?php echo json_encode($top_products_chart); ?>;
    const topFarmersData = <?php echo json_encode($top_farmers_chart); ?>;
</script>
<script src="js/admin_dashboard_charts.js"></script>

<?php include 'includes/admin_footer.php'; ?>



