<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['buyer_id'])) {
    header('Location: buyer_login.php');
    exit();
}

// Get filter/sort/search parameters
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build query
// Base query
$query = "SELECT p.*, f.name as farmer_name FROM products p JOIN farmers f ON p.farmer_id = f.id WHERE p.quantity > 0";

// If search query provided, match against name, description, category
if ($q !== '') {
    $safe_q = $conn->real_escape_string($q);
    $query .= " AND (p.name LIKE '%" . $safe_q . "%' OR p.description LIKE '%" . $safe_q . "%' OR p.category LIKE '%" . $safe_q . "%')";
}

if ($filter != 'all') {
    $query .= " AND p.category = '" . $conn->real_escape_string($filter) . "'";
}

switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'best_seller':
        $query .= " ORDER BY p.sold DESC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

$products = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Organic Marketplace</title>
    <link rel="stylesheet" href="css/buyer_style.css">
</head>
<body>
    <div class="buyer-wrapper">
        <?php include 'includes/buyer_sidebar.php'; ?>
        <div class="buyer-content">
            <div class="page-header">
                <h1>🛍️ Browse Products</h1>
                <p>Discover fresh organic products</p>
                <button id="filter-toggle" title="Show filters" style="margin-left:16px; padding:8px 10px; border-radius:6px; border:none; background:#eef6ec; cursor:pointer;">🔎 Filters</button>
            </div>

        <div id="filter-panel" class="filter-section">
            <h3 style="margin-bottom: 15px;">Filter by Category</h3>
            <div class="filter-buttons">
                <button class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>" data-filter="all" onclick="window.location.href='?filter=all&sort=<?php echo $sort; ?>'">All</button>
                <button class="filter-btn <?php echo $filter == 'Vegetables' ? 'active' : ''; ?>" data-filter="Vegetables" onclick="window.location.href='?filter=Vegetables&sort=<?php echo $sort; ?>'">Vegetables</button>
                <button class="filter-btn <?php echo $filter == 'Fruits' ? 'active' : ''; ?>" data-filter="Fruits" onclick="window.location.href='?filter=Fruits&sort=<?php echo $sort; ?>'">Fruits</button>
                <button class="filter-btn <?php echo $filter == 'Fish' ? 'active' : ''; ?>" data-filter="Fish" onclick="window.location.href='?filter=Fish&sort=<?php echo $sort; ?>'">Fish</button>
                <button class="filter-btn <?php echo $filter == 'Cacao' ? 'active' : ''; ?>" data-filter="Cacao" onclick="window.location.href='?filter=Cacao&sort=<?php echo $sort; ?>'">Cacao</button>
                <button class="filter-btn <?php echo $filter == 'Eggs' ? 'active' : ''; ?>" data-filter="Eggs" onclick="window.location.href='?filter=Eggs&sort=<?php echo $sort; ?>'">Eggs</button>
                <button class="filter-btn <?php echo $filter == 'Spices' ? 'active' : ''; ?>" data-filter="Spices" onclick="window.location.href='?filter=Spices&sort=<?php echo $sort; ?>'">Spices</button>
            </div>
            
            <h3 style="margin: 20px 0 15px 0;">Sort by</h3>
            <div class="filter-buttons">
                <button class="filter-btn <?php echo $sort == 'newest' ? 'active' : ''; ?>" onclick="window.location.href='?filter=<?php echo $filter; ?>&sort=newest'">Newest</button>
                <button class="filter-btn <?php echo $sort == 'price_low' ? 'active' : ''; ?>" onclick="window.location.href='?filter=<?php echo $filter; ?>&sort=price_low'">Price: Low to High</button>
                <button class="filter-btn <?php echo $sort == 'price_high' ? 'active' : ''; ?>" onclick="window.location.href='?filter=<?php echo $filter; ?>&sort=price_high'">Price: High to Low</button>
                <button class="filter-btn <?php echo $sort == 'best_seller' ? 'active' : ''; ?>" onclick="window.location.href='?filter=<?php echo $filter; ?>&sort=best_seller'">Best Seller</button>
            </div>
        </div>

        <div class="products-grid">
            <?php if ($products->num_rows > 0): ?>
                <?php while ($product = $products->fetch_assoc()): ?>
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
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">No products found</p>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <script src="js/buyer_script.js"></script>
</body>
</html>

