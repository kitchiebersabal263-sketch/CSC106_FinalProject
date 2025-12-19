<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['buyer_id'])) {
    header('Location: buyer_login.php');
    exit();
}

$buyer_id = $_SESSION['buyer_id'];

// Handle remove from cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND buyer_id = ?");
    $stmt->bind_param("ii", $cart_id, $buyer_id);
    $stmt->execute();
    $stmt->close();
    header('Location: my_cart.php');
    exit();
}

$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image, p.location, p.category, f.name as farmer_name 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        JOIN farmers f ON p.farmer_id = f.id 
                        WHERE c.buyer_id = ?");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Organic Marketplace</title>
    <link rel="stylesheet" href="css/buyer_style.css">
    <link rel="stylesheet" href="css/cart_style.css">
</head>
<body>
    <div class="buyer-wrapper">
        <?php include 'includes/buyer_sidebar.php'; ?>
        <div class="buyer-content">
            <div class="page-header">
                <h1>My Cart</h1>
                <p>Review your selected items</p>
            </div>

        <div class="cart-container">
            <?php if ($cart_items->num_rows > 0): ?>
                <form id="cart-form" method="POST" action="checkout.php">
                    <div class="cart-topbar">
                        <label class="select-all">
                            <input type="checkbox" id="select-all" checked>
                            <span>Select all items</span>
                        </label>
                    </div>

                    <?php while ($item = $cart_items->fetch_assoc()): ?>
                        <?php 
                        $item_total = $item['price'] * $item['quantity'];
                        $total += $item_total;
                        ?>
                        <div class="cart-item">
                            <label class="item-checkbox">
                                <input type="checkbox" name="selected_items[]" value="<?php echo $item['id']; ?>" class="item-select" checked>
                                <span></span>
                            </label>
                            <?php if (!empty($item['image']) && file_exists('../' . $item['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="cart-item-image" style="display: none; align-items: center; justify-content: center; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #4a7c59; font-weight: 500;">🖼️</div>
                            <?php else: ?>
                                <div class="cart-item-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #4a7c59; font-weight: 500;">🖼️</div>
                            <?php endif; ?>
                            <div class="cart-item-info">
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cart-item-details">
                                    Quantity: <?php echo $item['quantity']; ?> | 
                                    Category: <?php echo htmlspecialchars($item['category']); ?> | 
                                    Location: <?php echo htmlspecialchars($item['location']); ?> | 
                                    Farmer: <?php echo htmlspecialchars($item['farmer_name']); ?>
                                </div>
                                <div class="cart-item-price">₱<?php echo number_format($item_total, 2); ?></div>
                            </div>
                            <div class="cart-item-actions">
                                <a href="?remove=<?php echo $item['id']; ?>" class="btn btn-danger" onclick="return confirm('Remove this item from cart?')">Remove</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div class="cart-summary">
                        <div class="cart-total">
                            <span class="cart-total-label">Total:</span>
                            <span class="cart-total-amount">₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        <button type="submit" class="btn btn-checkout">Proceed to Checkout</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 6h15l-1.4 7.2a2 2 0 0 1-2 1.6H9.4a2 2 0 0 1-2-1.6L6 6z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="10" cy="20" r="1" fill="currentColor"/>
                            <circle cx="18" cy="20" r="1" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="empty-cart-message">Your cart is empty</div>
                    <a href="browse_products.php" class="btn btn-primary" style="display: inline-block; width: auto;">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <script>
        // Select all toggle
        const selectAll = document.getElementById('select-all');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                document.querySelectorAll('.item-select').forEach(cb => cb.checked = selectAll.checked);
            });
        }
    </script>
    <script src="js/buyer_script.js"></script>
</body>
</html>

