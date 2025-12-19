<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get search and sort parameters
$search = sanitize_input($_GET['search'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'order_date');
$order = sanitize_input($_GET['order'] ?? 'DESC');
$status_filter = sanitize_input($_GET['status'] ?? '');

// Validate sort columns
$allowed_sorts = ['id', 'order_date', 'total', 'quantity', 'status', 'product_name', 'buyer_name', 'farmer_name'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'order_date';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Validate status filter (UI supports Pending/Delivered)
$allowed_statuses = ['Pending', 'Delivered'];
if (!empty($status_filter) && !in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Build WHERE clause with search and status filter
$where = "1=1";
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $where .= " AND (o.id LIKE '%$search_escaped%' OR p.name LIKE '%$search_escaped%' OR b.name LIKE '%$search_escaped%' OR f.name LIKE '%$search_escaped%' OR o.status LIKE '%$search_escaped%')";
}

if (!empty($status_filter)) {
    $status_escaped = $conn->real_escape_string($status_filter);
    $where .= " AND o.status = '$status_escaped'";
}

// Build ORDER BY clause
$order_by = "ORDER BY ";
if ($sort === 'product_name') {
    $order_by .= "p.name $order";
} elseif ($sort === 'buyer_name') {
    $order_by .= "b.name $order";
} elseif ($sort === 'farmer_name') {
    $order_by .= "f.name $order";
} else {
    $order_by .= "o.$sort $order";
}

// Get all orders with search and sort
$orders = $conn->query("SELECT o.*, p.name as product_name, COALESCE(NULLIF(p.category, ''), '—') as product_category, b.name as buyer_name, f.name as farmer_name 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        JOIN buyers b ON o.buyer_id = b.id 
                        JOIN farmers f ON o.farmer_id = f.id 
                        WHERE $where
                        $order_by");

$page_title = 'Manage Orders';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <h1>Manage Orders</h1>
    <p><a href="admin_dashboard.php">← Back to Dashboard</a></p>
</div>

<div style="margin-bottom: 15px; background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
    <form method="GET" action="" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
               placeholder="Search..." 
               style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; width: 180px; font-size: 0.9em;">
        <select name="status" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 120px;">
            <option value="">All Status</option>
            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
        </select>
        <select name="sort" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 130px;">
            <option value="order_date" <?php echo $sort === 'order_date' ? 'selected' : ''; ?>>Date</option>
            <option value="id" <?php echo $sort === 'id' ? 'selected' : ''; ?>>Order ID</option>
            <option value="total" <?php echo $sort === 'total' ? 'selected' : ''; ?>>Total</option>
            <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity</option>
            <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Status</option>
            <option value="product_name" <?php echo $sort === 'product_name' ? 'selected' : ''; ?>>Product</option>
            <option value="buyer_name" <?php echo $sort === 'buyer_name' ? 'selected' : ''; ?>>Buyer</option>
            <option value="farmer_name" <?php echo $sort === 'farmer_name' ? 'selected' : ''; ?>>Farmer</option>
        </select>
        <select name="order" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 110px;">
            <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Desc</option>
            <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Asc</option>
        </select>
        <button type="submit" class="btn btn-primary" style="padding: 6px 12px !important; font-size: 0.85em !important; white-space: nowrap !important; min-width: auto !important; width: auto !important; max-width: none !important;">Search</button>
        <?php if (!empty($search) || !empty($status_filter)): ?>
            <a href="manage_orders.php" style="background: #999 !important; color: white !important; padding: 6px 12px !important; font-size: 0.85em !important; text-decoration: none !important; white-space: nowrap !important; border-radius: 5px !important; display: inline-block !important; min-width: auto !important; width: auto !important; max-width: none !important;">✕</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Category</th>
                <th>Buyer</th>
                <th>Farmer</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <?php
                        $category = $order['product_category'] ?? '';
                        if (empty($category) || $category === '—') {
                            // Defensive fallback: query product table directly
                            $stmtCat = $conn->prepare("SELECT category FROM products WHERE id = ?");
                            if ($stmtCat) {
                                $stmtCat->bind_param('i', $order['product_id']);
                                $stmtCat->execute();
                                $resCat = $stmtCat->get_result();
                                if ($resCat && $rowCat = $resCat->fetch_assoc()) {
                                    $category = $rowCat['category'] ?: '—';
                                }
                                $stmtCat->close();
                            }
                        }
                    ?>
                        <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td class="product-category-cell"><?php echo htmlspecialchars($category); ?></td>
                        <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['farmer_name']); ?></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>₱<?php echo number_format($order['total'], 2); ?></td>
                        <td>
                            <span style="padding: 5px 10px; border-radius: 3px; background: #f0f0f0;">
                                <?php echo $order['status']; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center;">No orders found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/admin_footer.php'; ?>

