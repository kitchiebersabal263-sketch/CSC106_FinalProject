<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get category-based sales analytics
$category_sales = @$conn->query("
    SELECT 
        p.category,
        COUNT(DISTINCT p.id) as product_count,
        COALESCE(SUM(p.sold), 0) as total_sold,
        COALESCE(SUM(p.sold * p.price), 0) as total_revenue,
        COALESCE(AVG(p.price), 0) as avg_price
    FROM products p
    GROUP BY p.category
    ORDER BY total_sold DESC
");

if (!$category_sales) {
    $category_sales = $conn->query("SELECT NULL as category, 0 as product_count, 0 as total_sold, 0 as total_revenue, 0 as avg_price WHERE 1=0");
}

// Categorize by product type groups
$organic_crops = ['Vegetables', 'Fruits', 'Cacao', 'Spices'];
$eggs_poultry = ['Eggs'];
$fishery = ['Fish'];

$organic_total = 0;
$eggs_total = 0;
$fishery_total = 0;
$organic_revenue = 0;
$eggs_revenue = 0;
$fishery_revenue = 0;

$category_data = [];
while ($row = $category_sales->fetch_assoc()) {
    $category_data[] = $row;
    if (in_array($row['category'], $organic_crops)) {
        $organic_total += $row['total_sold'];
        $organic_revenue += $row['total_revenue'];
    } elseif (in_array($row['category'], $eggs_poultry)) {
        $eggs_total += $row['total_sold'];
        $eggs_revenue += $row['total_revenue'];
    } elseif (in_array($row['category'], $fishery)) {
        $fishery_total += $row['total_sold'];
        $fishery_revenue += $row['total_revenue'];
    }
}

// Buyer demographics - check if columns exist first
$colCheck = $conn->query("SHOW COLUMNS FROM buyers LIKE 'barangay'");
$hasBarangay = $colCheck->num_rows > 0;

if ($hasBarangay) {
    $buyer_demographics = @$conn->query("
        SELECT 
            COUNT(*) as total_buyers,
            COUNT(DISTINCT CASE WHEN barangay IS NOT NULL AND barangay != '' THEN barangay END) as unique_barangays,
            COUNT(CASE WHEN age_group = '18-25' THEN 1 END) as age_18_25,
            COUNT(CASE WHEN age_group = '26-35' THEN 1 END) as age_26_35,
            COUNT(CASE WHEN age_group = '36-45' THEN 1 END) as age_36_45,
            COUNT(CASE WHEN age_group = '46-55' THEN 1 END) as age_46_55,
            COUNT(CASE WHEN age_group = '56+' THEN 1 END) as age_56_plus
        FROM buyers
    ");
    if (!$buyer_demographics) {
        $buyer_demographics = @$conn->query("SELECT COUNT(*) as total_buyers, 0 as unique_barangays, 0 as age_18_25, 0 as age_26_35, 0 as age_36_45, 0 as age_46_55, 0 as age_56_plus FROM buyers");
    }
} else {
    $buyer_demographics = @$conn->query("
        SELECT 
            COUNT(*) as total_buyers,
            0 as unique_barangays,
            0 as age_18_25,
            0 as age_26_35,
            0 as age_36_45,
            0 as age_46_55,
            0 as age_56_plus
        FROM buyers
    ");
}

if (!$buyer_demographics) {
    // Fallback if query fails
    $demo = ['total_buyers' => 0, 'unique_barangays' => 0, 'age_18_25' => 0, 'age_26_35' => 0, 'age_36_45' => 0, 'age_46_55' => 0, 'age_56_plus' => 0];
} else {
    $demo = $buyer_demographics->fetch_assoc();
    if (!$demo) {
        $demo = ['total_buyers' => 0, 'unique_barangays' => 0, 'age_18_25' => 0, 'age_26_35' => 0, 'age_36_45' => 0, 'age_46_55' => 0, 'age_56_plus' => 0];
    }
}

// Top barangays by buyers
if ($hasBarangay) {
    $top_barangays = $conn->query("
        SELECT 
            barangay,
            COUNT(*) as buyer_count
        FROM buyers
        WHERE barangay IS NOT NULL AND barangay != ''
        GROUP BY barangay
        ORDER BY buyer_count DESC
        LIMIT 10
    ");
} else {
    // Return empty result set
    $top_barangays = $conn->query("SELECT NULL as barangay, 0 as buyer_count WHERE 1=0");
}

// Sales by month (last 12 months)
$monthly_sales = @$conn->query("
    SELECT 
        DATE_FORMAT(order_date, '%Y-%m') as month,
        COUNT(*) as order_count,
        COALESCE(SUM(total), 0) as total_revenue,
        COALESCE(SUM(quantity), 0) as total_quantity
    FROM orders
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month DESC
");

if (!$monthly_sales) {
    $monthly_sales = $conn->query("SELECT NULL as month, 0 as order_count, 0 as total_revenue, 0 as total_quantity WHERE 1=0");
}

// Payment method usage - check if column exists
$colCheckPayment = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
$hasPaymentMethod = $colCheckPayment->num_rows > 0;

if ($hasPaymentMethod) {
    $payment_stats = @$conn->query("
        SELECT 
            payment_method,
            COUNT(*) as usage_count,
            COALESCE(SUM(total), 0) as total_amount
        FROM orders
        WHERE payment_method IS NOT NULL
        GROUP BY payment_method
        ORDER BY usage_count DESC
    ");
    if (!$payment_stats) {
        $payment_stats = $conn->query("SELECT NULL as payment_method, 0 as usage_count, 0 as total_amount WHERE 1=0");
    }
} else {
    $payment_stats = $conn->query("SELECT NULL as payment_method, 0 as usage_count, 0 as total_amount WHERE 1=0");
}

// Delivery vs Pickup - check if column exists
$colCheckDelivery = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_type'");
$hasDeliveryType = $colCheckDelivery->num_rows > 0;

if ($hasDeliveryType) {
    $delivery_stats = @$conn->query("
        SELECT 
            COALESCE(delivery_type, 'delivery') as delivery_type,
            COUNT(*) as order_count
        FROM orders
        GROUP BY COALESCE(delivery_type, 'delivery')
    ");
    if (!$delivery_stats) {
        $delivery_stats = $conn->query("SELECT 'delivery' as delivery_type, COUNT(*) as order_count FROM orders");
    }
} else {
    // Default to all delivery
    $delivery_stats = @$conn->query("SELECT 'delivery' as delivery_type, COUNT(*) as order_count FROM orders");
    if (!$delivery_stats) {
        $delivery_stats = $conn->query("SELECT 'delivery' as delivery_type, 0 as order_count");
    }
}

$page_title = 'Analytics & Reports';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <h1>📈 Analytics & Reports</h1>
    <p>Comprehensive analytics for Cabadbaran City Department of Agriculture</p>
</div>

<!-- Category-Based Sales Analytics -->
<div class="chart-section">
    <div class="chart-header">
        <h2>📊 Sales by Product Category</h2>
        <p>Sales volume breakdown by category groups</p>
    </div>

    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-icon">🌾</div>
            <h3>Organic Crops</h3>
            <div class="stat-value"><?php echo number_format($organic_total); ?></div>
            <div class="stat-label">Units Sold</div>
            <div style="margin-top: 10px; color: #4a7c59; font-weight: 600;">
                Revenue: ₱<?php echo number_format($organic_revenue, 2); ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🥚</div>
            <h3>Eggs/Poultry</h3>
            <div class="stat-value"><?php echo number_format($eggs_total); ?></div>
            <div class="stat-label">Units Sold</div>
            <div style="margin-top: 10px; color: #4a7c59; font-weight: 600;">
                Revenue: ₱<?php echo number_format($eggs_revenue, 2); ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🐟</div>
            <h3>Fishery</h3>
            <div class="stat-value"><?php echo number_format($fishery_total); ?></div>
            <div class="stat-label">Units Sold</div>
            <div style="margin-top: 10px; color: #4a7c59; font-weight: 600;">
                Revenue: ₱<?php echo number_format($fishery_revenue, 2); ?>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h3 style="margin-bottom: 15px;">Detailed Category Breakdown</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Products</th>
                    <th>Units Sold</th>
                    <th>Total Revenue</th>
                    <th>Avg Price</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($category_data) > 0): ?>
                    <?php foreach ($category_data as $cat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cat['category']); ?></strong></td>
                            <td><?php echo $cat['product_count']; ?></td>
                            <td><?php echo number_format($cat['total_sold']); ?></td>
                            <td>₱<?php echo number_format($cat['total_revenue'], 2); ?></td>
                            <td>₱<?php echo number_format($cat['avg_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #666;">No sales data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Buyer Demographics -->
<div class="chart-section" style="margin-top: 30px;">
    <div class="chart-header">
        <h2>👥 Buyer Demographics</h2>
        <p>Buyer distribution and characteristics</p>
    </div>

    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <h3>Total Buyers</h3>
            <div class="stat-value"><?php echo number_format($demo['total_buyers']); ?></div>
            <div class="stat-label">Registered buyers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📍</div>
            <h3>Barangays</h3>
            <div class="stat-value"><?php echo number_format($demo['unique_barangays']); ?></div>
            <div class="stat-label">Unique locations</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        <div class="table-container">
            <h3 style="margin-bottom: 15px;">Age Group Distribution</h3>
            <table>
                <thead>
                    <tr>
                        <th>Age Group</th>
                        <th>Buyers</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $age_groups = [
                        '18-25' => $demo['age_18_25'],
                        '26-35' => $demo['age_26_35'],
                        '36-45' => $demo['age_36_45'],
                        '46-55' => $demo['age_46_55'],
                        '56+' => $demo['age_56_plus']
                    ];
                    $total_with_age = array_sum($age_groups);
                    ?>
                    <?php foreach ($age_groups as $age => $count): ?>
                        <tr>
                            <td><strong><?php echo $age; ?></strong></td>
                            <td><?php echo number_format($count); ?></td>
                            <td><?php echo $total_with_age > 0 ? number_format(($count / $total_with_age) * 100, 1) : 0; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 15px;">Top Barangays by Buyers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Barangay</th>
                        <th>Buyers</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_barangays->num_rows > 0): ?>
                        <?php while ($barangay = $top_barangays->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($barangay['barangay']); ?></strong></td>
                                <td><?php echo number_format($barangay['buyer_count']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; padding: 40px; color: #666;">No barangay data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Monthly Sales Trends -->
<div class="chart-section" style="margin-top: 30px;">
    <div class="chart-header">
        <h2>📅 Monthly Sales Trends (Last 12 Months)</h2>
        <p>Sales performance over time</p>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Orders</th>
                    <th>Total Revenue</th>
                    <th>Units Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($monthly_sales->num_rows > 0): ?>
                    <?php while ($month = $monthly_sales->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></strong></td>
                            <td><?php echo number_format($month['order_count']); ?></td>
                            <td>₱<?php echo number_format($month['total_revenue'], 2); ?></td>
                            <td><?php echo number_format($month['total_quantity']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #666;">No monthly sales data available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment & Delivery Statistics -->
<div class="chart-section" style="margin-top: 30px;">
    <div class="chart-header">
        <h2>💳 Payment & Delivery Statistics</h2>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <div class="table-container">
            <h3 style="margin-bottom: 15px;">Payment Method Usage</h3>
            <table>
                <thead>
                    <tr>
                        <th>Payment Method</th>
                        <th>Orders</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payment_stats->num_rows > 0): ?>
                        <?php while ($payment = $payment_stats->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_method']))); ?></strong></td>
                                <td><?php echo number_format($payment['usage_count']); ?></td>
                                <td>₱<?php echo number_format($payment['total_amount'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: #666;">No payment data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 15px;">Delivery vs Pickup</h3>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Orders</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($delivery_stats->num_rows > 0): ?>
                        <?php while ($delivery = $delivery_stats->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo ucfirst($delivery['delivery_type']); ?></strong></td>
                                <td><?php echo number_format($delivery['order_count']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; padding: 40px; color: #666;">No delivery data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Report Generation -->
<div class="chart-section" style="margin-top: 30px;">
    <div class="chart-header">
        <h2>📄 Generate Reports</h2>
        <p>Export analytics data for monitoring and policy decisions</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <a href="reports.php?type=category_sales&format=csv" class="stat-card clickable" style="text-decoration: none; color: inherit;">
            <div class="stat-icon">📊</div>
            <h3>Category Sales Report</h3>
            <p style="margin-top: 10px; color: #666;">Export sales by category (CSV)</p>
        </a>
        <a href="reports.php?type=buyer_demographics&format=csv" class="stat-card clickable" style="text-decoration: none; color: inherit;">
            <div class="stat-icon">👥</div>
            <h3>Buyer Demographics</h3>
            <p style="margin-top: 10px; color: #666;">Export buyer statistics (CSV)</p>
        </a>
        <a href="reports.php?type=monthly_sales&format=csv" class="stat-card clickable" style="text-decoration: none; color: inherit;">
            <div class="stat-icon">📅</div>
            <h3>Monthly Sales Report</h3>
            <p style="margin-top: 10px; color: #666;">Export monthly trends (CSV)</p>
        </a>
        <a href="reports.php?type=order_details&format=csv" class="stat-card clickable" style="text-decoration: none; color: inherit;">
            <div class="stat-icon">🛒</div>
            <h3>Order Details Report</h3>
            <p style="margin-top: 10px; color: #666;">All order details (CSV)</p>
        </a>
        <a href="reports.php?type=product_inventory&format=csv" class="stat-card clickable" style="text-decoration: none; color: inherit;">
            <div class="stat-icon">📦</div>
            <h3>Product Inventory Report</h3>
            <p style="margin-top: 10px; color: #666;">Product inventory status (CSV)</p>
        </a>
        <a href="reports.php?type=seller_performance&format=csv" class="stat-card clickable" style="text-decoration: none; color: inherit;">
            <div class="stat-icon">🧑‍🌾</div>
            <h3>Seller Performance Report</h3>
            <p style="margin-top: 10px; color: #666;">Seller statistics (CSV)</p>
        </a>
        <a href="reports.php?type=full_report&format=csv" class="stat-card clickable" style="text-decoration: none; color: inherit;">
            <div class="stat-icon">📋</div>
            <h3>Full Comprehensive Report</h3>
            <p style="margin-top: 10px; color: #666;">Complete analytics report (CSV)</p>
        </a>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

