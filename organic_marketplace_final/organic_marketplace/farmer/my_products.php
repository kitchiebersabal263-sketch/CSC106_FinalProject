<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['farmer_id'])) {
    header('Location: farmer_login.php');
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->bind_param("ii", $product_id, $farmer_id);
    $stmt->execute();
    $stmt->close();
    header('Location: my_products.php');
    exit();
}

// Get search and sort parameters
$search = sanitize_input($_GET['search'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'created_at');
$order = sanitize_input($_GET['order'] ?? 'DESC');

// Validate sort columns
$allowed_sorts = ['created_at', 'name', 'category', 'price', 'quantity', 'sold'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Build WHERE clause
$where = "farmer_id = ?";
$params = [$farmer_id];
$types = "i";

if (!empty($search)) {
    $where .= " AND (name LIKE ? OR category LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

// Build ORDER BY clause
$order_by = "ORDER BY $sort $order";

// Get farmer's products with search and sort
$query = "SELECT * FROM products WHERE $where $order_by";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Organic Marketplace</title>
    <link rel="stylesheet" href="css/farmer_style.css">
    <style>
        /* Fix table container and button layout */
        .table-container {
            width: 100%;
            overflow-x: auto;
            overflow-y: visible;
        }
        
        .products-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 12px 10px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .products-table td {
            height: auto;
            min-height: 50px;
        }
        
        .actions-cell {
            padding: 8px 6px !important;
            white-space: nowrap;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 6px;
            justify-content: center;
            align-items: center;
            flex-wrap: nowrap;
        }
        
        .btn-sm {
            padding: 4px 10px !important;
            font-size: 0.75em !important;
            min-width: 50px;
            max-width: 70px;
            white-space: nowrap;
            display: inline-block;
            text-align: center;
        }
        
        .products-table tbody tr {
            height: auto;
        }
        
        .products-table tbody tr td {
            vertical-align: middle;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                gap: 4px;
            }
            
            .btn-sm {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="farmer-wrapper">
        <?php include 'includes/farmer_sidebar.php'; ?>
        <div class="farmer-content">
            <div class="page-header">
                <h1>My Products</h1>
                <p>Manage your product listings</p>
            </div>

            <div style="margin-bottom: 15px; background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
                <form method="GET" action="" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search..." 
                           style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; width: 180px; font-size: 0.9em;">
                    <select name="sort" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 140px;">
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="category" <?php echo $sort === 'category' ? 'selected' : ''; ?>>Category</option>
                        <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price</option>
                        <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity</option>
                        <option value="sold" <?php echo $sort === 'sold' ? 'selected' : ''; ?>>Sold</option>
                    </select>
                    <select name="order" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 110px;">
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Desc</option>
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Asc</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 6px 12px !important; font-size: 0.85em !important; white-space: nowrap !important; min-width: auto !important; width: auto !important;">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="my_products.php" class="btn btn-secondary" style="padding: 6px 12px !important; font-size: 0.85em !important; text-decoration: none !important; white-space: nowrap !important; min-width: auto !important; width: auto !important;">✕</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container">
            <table class="products-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Product</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 12%;">Price</th>
                        <th style="width: 12%;">Quantity</th>
                        <th style="width: 10%;">Sold</th>
                        <th style="width: 26%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars(!empty($product['category']) ? $product['category'] : '—'); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['quantity']; ?></td>
                                <td><?php echo $product['sold']; ?></td>
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No products found. <a href="add_product.php">Add your first product</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
    <script src="js/farmer_script.js"></script>
</body>
</html>
