<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    $conn->begin_transaction();
    try {
        // Delete product images if table exists
        $tblCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
        if ($tblCheck && $tblCheck->num_rows > 0) {
            $stmt_img = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt_img->bind_param("i", $product_id);
            $stmt_img->execute();
            $stmt_img->close();
        }
        // Delete product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        $message = 'Product deleted successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Error deleting product: ' . $e->getMessage();
        $message_type = 'error';
    }
    header('Location: manage_products.php?msg=' . urlencode($message) . '&type=' . $message_type);
    exit();
}

// Handle edit product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_product') {
    $product_id = intval($_POST['product_id']);
    $name = sanitize_input($_POST['name'] ?? '');
    $category = sanitize_input($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $description = sanitize_input($_POST['description'] ?? '');
    $location = sanitize_input($_POST['location'] ?? '');
    $unit = sanitize_input($_POST['unit'] ?? 'kilo');
    
    if (empty($name) || $price <= 0 || $quantity < 0) {
        $message = 'Invalid product data.';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, quantity = ?, description = ?, location = ?, unit = ? WHERE id = ?");
        $stmt->bind_param("ssdissi", $name, $category, $price, $quantity, $description, $location, $unit, $product_id);
        if ($stmt->execute()) {
            $message = 'Product updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating product: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
    header('Location: manage_products.php?msg=' . urlencode($message) . '&type=' . $message_type);
    exit();
}

// Get search and sort parameters
$search = sanitize_input($_GET['search'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'created_at');
$order = sanitize_input($_GET['order'] ?? 'DESC');

// Validate sort columns
$allowed_sorts = ['id', 'name', 'category', 'price', 'quantity', 'sold', 'created_at', 'farmer_name'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Build query with search and sort
$where = "1=1";
if (!empty($search)) {
    $search_escaped = $conn->real_escape_string($search);
    $where .= " AND (p.name LIKE '%$search_escaped%' OR p.category LIKE '%$search_escaped%' OR f.name LIKE '%$search_escaped%' OR p.location LIKE '%$search_escaped%')";
}

$order_by = "ORDER BY ";
if ($sort === 'farmer_name') {
    $order_by .= "f.name $order";
} else {
    $order_by .= "p.$sort $order";
}

// Get all products with search and sort
$products = $conn->query("SELECT p.*, f.name as farmer_name 
                          FROM products p 
                          JOIN farmers f ON p.farmer_id = f.id 
                          WHERE $where
                          $order_by");

// Get product for editing if edit_id is set
$edit_product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();
    $stmt->close();
}

$page_title = 'Manage Products';
include 'includes/admin_header.php';
?>
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

<div class="page-header">
    <h1>Manage Products</h1>
    <p><a href="admin_dashboard.php">← Back to Dashboard</a></p>
</div>

<?php 
// Show message from GET parameter
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['type'] ?? 'success';
}
if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 15px;"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div style="margin-bottom: 15px; background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
    <form method="GET" action="" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
               placeholder="Search..." 
               style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; width: 180px; font-size: 0.9em;">
        <select name="sort" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 130px;">
            <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date</option>
            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
            <option value="category" <?php echo $sort === 'category' ? 'selected' : ''; ?>>Category</option>
            <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price</option>
            <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity</option>
            <option value="sold" <?php echo $sort === 'sold' ? 'selected' : ''; ?>>Sold</option>
            <option value="farmer_name" <?php echo $sort === 'farmer_name' ? 'selected' : ''; ?>>Farmer</option>
        </select>
        <select name="order" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 110px;">
            <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Desc</option>
            <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Asc</option>
        </select>
        <button type="submit" class="btn btn-primary" style="padding: 6px 12px !important; font-size: 0.85em !important; white-space: nowrap !important; min-width: auto !important; width: auto !important; max-width: none !important;">Search</button>
        <?php if (!empty($search)): ?>
            <a href="manage_products.php" class="btn btn-secondary" style="padding: 6px 12px !important; font-size: 0.85em !important; text-decoration: none !important; white-space: nowrap !important; min-width: auto !important; width: auto !important; max-width: none !important;">✕</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 6%;">ID</th>
                <th style="width: 20%;">Product Name</th>
                <th style="width: 12%;">Category</th>
                <th style="width: 10%;">Price</th>
                <th style="width: 10%;">Quantity</th>
                <th style="width: 8%;">Sold</th>
                <th style="width: 18%;">Farmer</th>
                <th style="width: 16%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($products->num_rows > 0): ?>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars(!empty($product['category']) ? $product['category'] : '—'); ?></td>
                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['quantity']; ?></td>
                        <td><?php echo $product['sold']; ?></td>
                        <td><?php echo htmlspecialchars($product['farmer_name']); ?></td>
                        <td class="actions-cell">
                            <div class="action-buttons">
                                <a href="?edit=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary btn-sm">Edit</a>
                                <a href="?delete=<?php echo $product['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No products found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Product Modal -->
<?php if ($edit_product): ?>
<div id="editModal" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 20px; border-radius: 10px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-bottom: 15px; font-size: 1.3em;">Edit Product</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
            
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Product Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required
                       style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em;">
            </div>
            
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Category *</label>
                <select name="category" required style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em;">
                    <option value="">Select Category</option>
                    <?php
                    $categories = ['Vegetables', 'Fruits', 'Fish', 'Cacao', 'Eggs', 'Spices'];
                    foreach ($categories as $cat):
                    ?>
                        <option value="<?php echo $cat; ?>" <?php echo $edit_product['category'] == $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Price (₱) *</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($edit_product['price']); ?>" required
                           style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em;">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Quantity *</label>
                    <input type="number" name="quantity" min="0" value="<?php echo htmlspecialchars($edit_product['quantity']); ?>" required
                           style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Unit</label>
                    <select name="unit" style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em;">
                        <option value="kilo" <?php echo ($edit_product['unit'] ?? 'kilo') == 'kilo' ? 'selected' : ''; ?>>Kilo</option>
                        <option value="piece" <?php echo ($edit_product['unit'] ?? '') == 'piece' ? 'selected' : ''; ?>>Piece</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($edit_product['location'] ?? ''); ?>"
                           style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em;">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Description</label>
                <textarea name="description" rows="4" style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em;"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
            </div>
            
            <div style="text-align: right; margin-top: 15px;">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.85em; margin-right: 8px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.85em; border: none; border-radius: 6px; cursor: pointer;">Update Product</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function closeEditModal() {
    window.location.href = 'manage_products.php<?php echo !empty($search) ? "?search=" . urlencode($search) . "&sort=" . $sort . "&order=" . $order : ""; ?>';
}

// Close modal on background click
<?php if ($edit_product): ?>
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>

