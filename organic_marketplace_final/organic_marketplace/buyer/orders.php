<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['buyer_id'])) {
    header('Location: buyer_login.php');
    exit();
}

$buyer_id = $_SESSION['buyer_id'];

// Get search and sort parameters
$search = sanitize_input($_GET['search'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'order_date');
$order = sanitize_input($_GET['order'] ?? 'DESC');
$status_filter = sanitize_input($_GET['status'] ?? '');

// Validate sort columns
$allowed_sorts = ['order_date', 'product_name', 'category', 'quantity', 'price', 'total', 'status'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'order_date';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Validate status filter (UI supports Pending/Delivered)
$allowed_statuses = ['Pending', 'Delivered'];
if (!empty($status_filter) && !in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Build WHERE clause
$where = "o.buyer_id = ?";
$params = [$buyer_id];
$types = "i";

if (!empty($search)) {
    $where .= " AND (p.name LIKE ? OR p.category LIKE ? OR f.name LIKE ? OR o.status LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $where .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Build ORDER BY clause
$order_by = "ORDER BY ";
if ($sort === 'product_name') {
    $order_by .= "p.name $order";
} elseif ($sort === 'category') {
    $order_by .= "p.category $order";
} else {
    $order_by .= "o.$sort $order";
}

// Get orders with search and sort
$query = "SELECT o.*, p.name as product_name, p.category as product_category, f.name as farmer_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          JOIN farmers f ON o.farmer_id = f.id 
          WHERE $where 
          $order_by";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Organic Marketplace</title>
    <link rel="stylesheet" href="css/buyer_style.css">
    <style>
        .orders-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e8dcc6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        table th {
            background: #f9f9f9;
            font-weight: 600;
            color: #333;
        }
        table tr:hover {
            background: #f9f9f9;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="buyer-wrapper">
        <?php include 'includes/buyer_sidebar.php'; ?>
        <div class="buyer-content">
            <div class="page-header">
                <h1>My Orders</h1>
                <p>View your order history</p>
            </div>

            <div class="orders-container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">Order placed successfully!</div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by product, category, farmer, status..." 
                           style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; min-width: 250px;">
                    <select name="status" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                    <select name="sort" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="order_date" <?php echo $sort === 'order_date' ? 'selected' : ''; ?>>Sort by Date</option>
                        <option value="product_name" <?php echo $sort === 'product_name' ? 'selected' : ''; ?>>Sort by Product</option>
                        <option value="category" <?php echo $sort === 'category' ? 'selected' : ''; ?>>Sort by Category</option>
                        <option value="total" <?php echo $sort === 'total' ? 'selected' : ''; ?>>Sort by Total</option>
                        <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Sort by Status</option>
                    </select>
                    <select name="order" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer;">Search</button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        <a href="orders.php" class="btn" style="background: #999; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Date</th>
                        </tr>
                </thead>
                <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_category'] ?? ''); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>₱<?php echo number_format($order['price'], 2); ?></td>
                                <td>₱<?php echo number_format($order['total'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($order['location']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">No orders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
    <script src="js/buyer_script.js"></script>
</body>
</html>

