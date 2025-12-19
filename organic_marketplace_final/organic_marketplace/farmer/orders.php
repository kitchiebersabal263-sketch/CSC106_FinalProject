<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['farmer_id'])) {
    header('Location: farmer_login.php');
    exit();
}

$farmer_id = $_SESSION['farmer_id'];
$error = '';
$success = '';

// Handle actions: mark_delivered or mark_pending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    $action = $_POST['action'];
    $order_id = intval($_POST['order_id']);

    try {
        $conn->begin_transaction();
        // Lock order row
        $stmt = $conn->prepare("SELECT id, product_id, quantity, status, payment_status, farmer_id FROM orders WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $order = $res->fetch_assoc();
        $stmt->close();

        if (!$order) {
            throw new Exception('Order not found');
        }
        if (intval($order['farmer_id']) !== intval($farmer_id)) {
            throw new Exception('Permission denied');
        }

        if ($action === 'mark_delivered') {
            if ($order['status'] === 'Delivered') {
                // already delivered
                $conn->commit();
                $success = 'Order already delivered.';
            } else {
                // Update order status to Delivered
                $newStatus = 'Delivered';
                $newPaymentStatus = 'completed';
                $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
                $stmt->bind_param('ssi', $newStatus, $newPaymentStatus, $order_id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update order: ' . $conn->error);
                }
                $stmt->close();

                // Increase sold count on product
                $stmt = $conn->prepare("UPDATE products SET sold = sold + ? WHERE id = ?");
                $stmt->bind_param('ii', $order['quantity'], $order['product_id']);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update product sold count: ' . $conn->error);
                }
                $stmt->close();

                $conn->commit();
                $success = 'Order marked as delivered.';
            }
        } elseif ($action === 'mark_pending') {
            if ($order['status'] === 'Pending') {
                $conn->commit();
                $success = 'Order already pending.';
            } else {
                // Set back to pending and decrement sold
                $newStatus = 'Pending';
                $newPaymentStatus = 'pending';
                $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
                $stmt->bind_param('ssi', $newStatus, $newPaymentStatus, $order_id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update order: ' . $conn->error);
                }
                $stmt->close();

                // Decrease sold count on product (prevent negative values)
                $stmt = $conn->prepare("UPDATE products SET sold = GREATEST(0, sold - ?) WHERE id = ?");
                $stmt->bind_param('ii', $order['quantity'], $order['product_id']);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update product sold count: ' . $conn->error);
                }
                $stmt->close();

                $conn->commit();
                $success = 'Order status set to pending.';
            }
        } else {
            $conn->commit();
            $error = 'Unknown action.';
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Get search and sort parameters
$search = sanitize_input($_GET['search'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'order_date');
$order = sanitize_input($_GET['order'] ?? 'DESC');
$status_filter = sanitize_input($_GET['status'] ?? '');

// Validate sort columns
$allowed_sorts = ['order_date', 'id', 'product_name', 'buyer_name', 'quantity', 'total', 'status'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'order_date';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Validate status filter (only Pending/Delivered supported in UI)
$allowed_statuses = ['Pending', 'Delivered'];
if (!empty($status_filter) && !in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Build WHERE clause
$where = "o.farmer_id = ?";
$params = [$farmer_id];
$types = "i";

if (!empty($search)) {
    $where .= " AND (p.name LIKE ? OR b.name LIKE ? OR o.status LIKE ? OR o.id LIKE ?)";
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
} elseif ($sort === 'buyer_name') {
    $order_by .= "b.name $order";
} else {
    $order_by .= "o.$sort $order";
}

// Fetch orders for this farmer with search and sort
$query = "SELECT o.*, p.name as product_name, p.category as product_category, b.name as buyer_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          JOIN buyers b ON o.buyer_id = b.id 
          WHERE $where 
          $order_by";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Farmer Panel</title>
    <link rel="stylesheet" href="css/farmer_style.css">
</head>
<body>
    <div class="farmer-wrapper">
        <?php include 'includes/farmer_sidebar.php'; ?>
        <div class="farmer-content">
            <div class="page-header">
                <h1>🧾 Orders</h1>
                <p>Manage your orders and mark them as delivered when completed.</p>
            </div>

            <div class="orders-list">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

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
                            <option value="product_name" <?php echo $sort === 'product_name' ? 'selected' : ''; ?>>Product</option>
                            <option value="buyer_name" <?php echo $sort === 'buyer_name' ? 'selected' : ''; ?>>Buyer</option>
                            <option value="total" <?php echo $sort === 'total' ? 'selected' : ''; ?>>Total</option>
                            <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Status</option>
                        </select>
                        <select name="order" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 110px;">
                            <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Desc</option>
                            <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Asc</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px !important; font-size: 0.85em !important; white-space: nowrap !important; min-width: auto !important; width: auto !important;">Search</button>
                        <?php if (!empty($search) || !empty($status_filter)): ?>
                            <a href="orders.php" class="btn btn-secondary" style="padding: 6px 12px !important; font-size: 0.85em !important; text-decoration: none !important; white-space: nowrap !important; min-width: auto !important; width: auto !important;">✕</a>
                        <?php endif; ?>
                    </form>
                </div>

                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Buyer</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Delivery Address</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($o = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $o['id']; ?></td>
                                <td><?php echo htmlspecialchars($o['buyer_name']); ?></td>
                                <td><?php echo htmlspecialchars($o['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($o['product_category'] ?? ''); ?></td>
                                <td><?php echo intval($o['quantity']); ?></td>
                                <td>₱<?php echo number_format($o['total'],2); ?></td>
                                <td><?php echo htmlspecialchars($o['location']); ?></td>
                                <td><?php echo htmlspecialchars($o['payment_method'] ?? 'COD'); ?> (<?php echo htmlspecialchars($o['payment_status'] ?? 'pending'); ?>)</td>
                                <td><?php echo htmlspecialchars($o['status']); ?></td>
                                <td>
                                    <?php if ($o['status'] === 'Pending'): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <input type="hidden" name="action" value="mark_delivered">
                                            <button type="submit" class="btn btn-primary" style="padding: 4px 8px !important; font-size: 0.75em !important; white-space: nowrap !important; min-width: auto !important; width: auto !important; max-width: none !important;">Delivered</button>
                                        </form>
                                    <?php elseif ($o['status'] === 'Delivered'): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <input type="hidden" name="action" value="mark_pending">
                                            <button type="submit" class="btn btn-secondary" style="padding: 4px 8px !important; font-size: 0.75em !important; white-space: nowrap !important; min-width: auto !important; width: auto !important; max-width: none !important;">Pending</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="js/farmer_script.js"></script>
</body>
</html>
