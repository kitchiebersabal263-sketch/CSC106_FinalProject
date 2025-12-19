<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['buyer_id'])) {
    header('Location: buyer_login.php');
    exit();
}

$buyer_id = $_SESSION['buyer_id'];
$delivery_fee_base = 30; // flat delivery fee
$display_delivery_type = isset($_POST['delivery_type']) ? sanitize_input($_POST['delivery_type']) : 'delivery';

/* --------------------------------------
   GET BUYER SAVED ADDRESS
-------------------------------------- */
$stmt_addr = $conn->prepare("SELECT address FROM buyers WHERE id = ?");
if (!$stmt_addr) {
    die("SQL ERROR (buyer address): " . $conn->error);
}
$stmt_addr->bind_param("i", $buyer_id);
$stmt_addr->execute();
$res_addr = $stmt_addr->get_result();
$buyer_row = $res_addr->fetch_assoc();
$buyer_address = $buyer_row['address'] ?? '';
$stmt_addr->close();

/* --------------------------------------
   GET ACTIVE PAYMENT METHODS
-------------------------------------- */
$payment_methods = $conn->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY id");
$hasPaymentMethods = $payment_methods && $payment_methods->num_rows > 0;

/* --------------------------------------
   GET ACTIVE PICKUP POINTS
-------------------------------------- */
$pickup_points = $conn->query("SELECT * FROM pickup_points ORDER BY address");
$hasPickupPoints = $pickup_points && $pickup_points->num_rows > 0;

/* --------------------------------------
   HANDLE CART FORM SUBMISSION (REDIRECT TO CHECKOUT)
-------------------------------------- */
// If POST request with selected_items from cart (no payment_method), store in session and redirect
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_items']) && !isset($_POST['payment_method'])) {
    $selected_items = array_map('intval', $_POST['selected_items']);
    if (empty($selected_items)) {
        header('Location: my_cart.php?error=no_selection');
        exit();
    }
    $_SESSION['checkout_selected_items'] = $selected_items;
    header('Location: checkout.php');
    exit();
}

/* --------------------------------------
   PROCESS CHECKOUT (ONLY WHEN PAYMENT METHOD IS SELECTED)
-------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_method'])) {

    $payment_method = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : 'cash_on_delivery';
    $delivery_type_raw = isset($_POST['delivery_type']) ? sanitize_input($_POST['delivery_type']) : 'delivery';
    
    // Map form values to database values - ensure proper mapping
    if ($delivery_type_raw === 'delivery' || $delivery_type_raw === 'home_delivery') {
        $delivery_type = 'home_delivery';
    } elseif ($delivery_type_raw === 'pickup' || $delivery_type_raw === 'pickup_point' || $delivery_type_raw === 'pickup_points') {
        $delivery_type = 'pickup_point';  // Database uses singular 'pickup_point'
    } else {
        // Default to home_delivery if value is unclear
        $delivery_type = 'home_delivery';
    }
    
    // Ensure delivery_type is always a valid string, never empty or null
    if (empty($delivery_type) || !is_string($delivery_type)) {
        $delivery_type = 'home_delivery';
    }
    
    $display_delivery_type = $delivery_type_raw;

    $pickup_point_id = ($delivery_type_raw === 'pickup' && !empty($_POST['pickup_point_id']))
                        ? intval($_POST['pickup_point_id'])
                        : NULL;

    // Use provided address OR saved address
    $delivery_address = sanitize_input($_POST['delivery_address'] ?? $buyer_address);

    // Delivery fee: apply when home delivery
    $delivery_fee_base = 30; // flat fee, adjust as needed
    $delivery_fee = ($delivery_type_raw === 'delivery') ? $delivery_fee_base : 0;

    /* --------------------------------------
       GET CART ITEMS FOR PROCESSING (ONLY SELECTED ITEMS)
    -------------------------------------- */
    // Get selected items from POST (form submission) or session
    $selected_items = [];
    if (isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        $selected_items = array_map('intval', $_POST['selected_items']);
    } elseif (isset($_SESSION['checkout_selected_items'])) {
        $selected_items = $_SESSION['checkout_selected_items'];
    } else {
        // fallback: use all cart items
        $selected_items = get_all_cart_ids($conn, $buyer_id);
    }
    
    if (empty($selected_items)) {
        unset($_SESSION['checkout_selected_items']);
        header('Location: my_cart.php?error=no_selection');
        exit();
    }
    
    // Create placeholders for IN clause
    $placeholders = str_repeat('?,', count($selected_items) - 1) . '?';
    $stmt_cart = $conn->prepare("
        SELECT c.*, p.name, p.price, p.farmer_id, p.location, p.category 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.buyer_id = ? AND c.id IN ($placeholders)
    ");
    
    $params = array_merge([$buyer_id], $selected_items);
    $types = str_repeat('i', count($params));
    $stmt_cart->bind_param($types, ...$params);
    $stmt_cart->execute();
    $cart_items_process = $stmt_cart->get_result();

    if ($cart_items_process->num_rows == 0) {
        $stmt_cart->close();
        unset($_SESSION['checkout_selected_items']);
        header('Location: my_cart.php?error=no_items');
        exit();
    }

    /* --------------------------------------
       ENSURE COLUMNS EXIST IN ORDERS TABLE
    -------------------------------------- */
    $mig = $conn->query("
        SELECT COLUMN_NAME 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = '".DB_NAME."' 
        AND TABLE_NAME = 'orders'
    ");

    $columns = [];
    while ($r = $mig->fetch_assoc()) $columns[] = $r['COLUMN_NAME'];

    if (!in_array('payment_method', $columns)) {
        $conn->query("
            ALTER TABLE orders 
            ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER total,
            ADD COLUMN payment_status ENUM('pending','completed','failed') DEFAULT 'pending' AFTER payment_method
        ");
    }

    if (!in_array('delivery_fee', $columns)) {
        $conn->query("ALTER TABLE orders ADD COLUMN delivery_fee DECIMAL(10,2) DEFAULT 0 AFTER total");
    }

    if (!in_array('delivery_type', $columns)) {
        $conn->query("ALTER TABLE orders ADD COLUMN delivery_type VARCHAR(50) NULL AFTER payment_status");
    }

    if (!in_array('pickup_point_id', $columns)) {
        $conn->query("ALTER TABLE orders ADD COLUMN pickup_point_id INT NULL AFTER delivery_type");
    }

    // Final verification: ensure delivery_type is set correctly before transaction
    if (!isset($delivery_type) || empty($delivery_type) || !is_string($delivery_type)) {
        $delivery_type = 'home_delivery';
    }
    
    $conn->begin_transaction();

    try {
        while ($item = $cart_items_process->fetch_assoc()) {

            /* Check quantity */
            $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($product['quantity'] < $item['quantity']) {
                throw new Exception("Insufficient quantity for " . htmlspecialchars($item['name']));
            }

            $total = $item['price'] * $item['quantity'];
            $payment_status = "pending";

            // CRITICAL: Re-verify delivery_type is set correctly for each iteration
            // This ensures the variable hasn't been modified or lost scope
            if (!isset($delivery_type) || empty($delivery_type) || !is_string($delivery_type)) {
                $delivery_type = 'home_delivery';
            }
            // Re-apply mapping if needed (safety check)
            if ($delivery_type !== 'home_delivery' && $delivery_type !== 'pickup_point') {
                if ($delivery_type === 'delivery' || $delivery_type === 'home_delivery') {
                    $delivery_type = 'home_delivery';
                } elseif ($delivery_type === 'pickup' || $delivery_type === 'pickup_point' || $delivery_type === 'pickup_points') {
                    $delivery_type = 'pickup_point';  // Database uses singular 'pickup_point'
                } else {
                    $delivery_type = 'home_delivery';
                }
            }

            /* Insert order */
            $stmt = $conn->prepare("
                INSERT INTO orders 
                (buyer_id, farmer_id, product_id, quantity, price, total, delivery_fee, location, 
                payment_method, payment_status, delivery_type, pickup_point_id, delivery_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            // Ensure delivery_type is explicitly a string before binding - final check
            $delivery_type_bound = trim((string)$delivery_type);
            // If value is empty, '0', numeric, or not a valid delivery type, set to home_delivery
            if (empty($delivery_type_bound) || 
                $delivery_type_bound === '0' || 
                is_numeric($delivery_type_bound) ||
                !in_array($delivery_type_bound, ['home_delivery', 'pickup_point'])) {
                $delivery_type_bound = 'home_delivery';
            }
            
            // Prepare all values for binding with explicit types
            $bind_buyer_id = (int)$buyer_id;
            $bind_farmer_id = (int)$item['farmer_id'];
            $bind_product_id = (int)$item['product_id'];
            $bind_quantity = (int)$item['quantity'];
            $bind_price = (float)$item['price'];
            $bind_total = (float)$total;
            $bind_delivery_fee = (float)$delivery_fee;
            $bind_location = (string)$item['location'];
            $bind_payment_method = (string)$payment_method;
            $bind_payment_status = (string)$payment_status;
            
            // CRITICAL: Ensure delivery_type is a non-empty string
            $bind_delivery_type = (string)$delivery_type_bound;
            if ($bind_delivery_type === '' || $bind_delivery_type === '0' || is_numeric($bind_delivery_type)) {
                $bind_delivery_type = 'home_delivery';
            }
            
            // Handle NULL pickup_point_id - mysqli can bind NULL as integer, but we'll be explicit
            if ($pickup_point_id === NULL) {
                $bind_pickup_point_id = NULL;
            } else {
                $bind_pickup_point_id = (int)$pickup_point_id;
            }
            
            $bind_delivery_address = (string)$delivery_address;
            
            // Bind parameters - type string: iiiidddsssiss (13 parameters)
            // CRITICAL FIX: Position 11 MUST be 's' for delivery_type (was 'i' causing 0!)
            // Column order: buyer_id, farmer_id, product_id, quantity, price, total, delivery_fee, 
            //               location, payment_method, payment_status, delivery_type, pickup_point_id, delivery_address
            // Correct type mapping: 1=i, 2=i, 3=i, 4=i, 5=d, 6=d, 7=d, 8=s, 9=s, 10=s, 11=s, 12=i, 13=s
            // Original "iiiidddsssiss" had position 11 as 'i' (WRONG - caused delivery_type to be 0)
            // Fixed string: "iiiidddsssiss" with position 11 changed from 'i' to 's'
            // Build correct type string: positions 1-10 stay same, position 11 must be 's', position 12 must be 'i'
            // Original had: iiiiddd(7) + sss(3) + i(1) + s(1) + s(1) = "iiiidddsssiss" (pos11='i' WRONG!)
            // Correct should be: iiiiddd(7) + sss(3) + s(1) + i(1) + s(1) = "iiiidddsssiss" (pos11='s' CORRECT!)
            $type_string = "iiiiddd" . "sss" . "s" . "i" . "s";  // Explicitly construct: pos11='s', pos12='i'
            $stmt->bind_param(
                $type_string,
                $bind_buyer_id,                    // 1: i
                $bind_farmer_id,                   // 2: i
                $bind_product_id,                  // 3: i
                $bind_quantity,                   // 4: i
                $bind_price,                       // 5: d
                $bind_total,                       // 6: d
                $bind_delivery_fee,                // 7: d
                $bind_location,                    // 8: s
                $bind_payment_method,              // 9: s
                $bind_payment_status,              // 10: s
                $bind_delivery_type,               // 11: s (STRING - was 'i' before, causing 0!)
                $bind_pickup_point_id,              // 12: i (was 's' before)
                $bind_delivery_address             // 13: s
            );
            

            $stmt->execute();
            $stmt->close();

            /* Update product stock */
            $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }

        /* Clear only selected items from cart */
        $placeholders_delete = str_repeat('?,', count($selected_items) - 1) . '?';
        $stmt = $conn->prepare("DELETE FROM cart WHERE buyer_id = ? AND id IN ($placeholders_delete)");
        $params_delete = array_merge([$buyer_id], $selected_items);
        $types_delete = str_repeat('i', count($params_delete));
        $stmt->bind_param($types_delete, ...$params_delete);
        $stmt->execute();
        $stmt->close();
        
        // Clear session
        unset($_SESSION['checkout_selected_items']);

        $stmt_cart->close();
        $conn->commit();

        header("Location: orders.php?success=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $stmt_cart->close();
        $error = $e->getMessage();
    }
}

/* --------------------------------------
   HANDLE CART FORM SUBMISSION (REDIRECT TO CHECKOUT)
-------------------------------------- */
// If POST request with selected_items from cart, store in session and redirect
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_items']) && !isset($_POST['payment_method'])) {
    $selected_items = array_map('intval', $_POST['selected_items']);
    if (empty($selected_items)) {
        header('Location: my_cart.php?error=no_selection');
        exit();
    }
    $_SESSION['checkout_selected_items'] = $selected_items;
    header('Location: checkout.php');
    exit();
}

/* --------------------------------------
   DISPLAY CART ITEMS (ONLY SELECTED ITEMS)
-------------------------------------- */
// Helper: load all cart ids for this buyer
function get_all_cart_ids(mysqli $conn, int $buyer_id): array {
    $ids = [];
    $stmt = $conn->prepare("SELECT id FROM cart WHERE buyer_id = ?");
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ids[] = (int)$row['id'];
    }
    $stmt->close();
    return $ids;
}

// Get selected items from session or fallback to all cart items
$selected_items = [];
if (isset($_SESSION['checkout_selected_items'])) {
    $selected_items = $_SESSION['checkout_selected_items'];
}
if (empty($selected_items)) {
    $selected_items = get_all_cart_ids($conn, $buyer_id);
}

if (empty($selected_items)) {
    unset($_SESSION['checkout_selected_items']);
    header('Location: my_cart.php?error=no_selection');
    exit();
}

// Create placeholders for IN clause
$placeholders = str_repeat('?,', count($selected_items) - 1) . '?';
$stmt_display = $conn->prepare("
    SELECT c.*, p.name, p.price, p.location, p.category, p.image, f.name AS farmer_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN farmers f ON p.farmer_id = f.id
    WHERE c.buyer_id = ? AND c.id IN ($placeholders)
");
$params = array_merge([$buyer_id], $selected_items);
$types = str_repeat('i', count($params));
$stmt_display->bind_param($types, ...$params);
$stmt_display->execute();
$cart_items = $stmt_display->get_result();

if ($cart_items->num_rows == 0) {
    $stmt_display->close();
    unset($_SESSION['checkout_selected_items']);
    header('Location: my_cart.php?error=no_selection');
    exit();
}

// Cache cart items for reuse (avoid pointer exhaustion)
$cart_data = $cart_items->fetch_all(MYSQLI_ASSOC);
$cart_items->free();
$stmt_display->close();

// Get first product location from cached cart data
$first_product_location = $cart_data[0]['location'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Organic Marketplace</title>
    <link rel="stylesheet" href="css/buyer_style.css">
    <link rel="stylesheet" href="css/cart_style.css">
    <link rel="stylesheet" href="css/checkout.css">

</head>
<body>
    <div class="buyer-wrapper">
        <?php include 'includes/buyer_sidebar.php'; ?>
        <div class="buyer-content">
            <div class="page-header">
                <h1>💳 Checkout</h1>
                <p>Complete your purchase</p>
            </div>

            <div class="cart-container">

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php foreach ($selected_items as $item_id): ?>
                    <input type="hidden" name="selected_items[]" value="<?php echo $item_id; ?>">
                <?php endforeach; ?>

            <div class="checkout-grid">

                <div class="checkout-section">
                    <h2>Order Items</h2>

                    <?php 
                    $subtotal = 0;
                    $product_locations = [];
                    foreach ($cart_data as $item):
                        $item_total = $item['price'] * $item['quantity'];
                        $subtotal += $item_total;
                        $product_locations[] = $item['location'];
                    ?>
                        <div class="cart-item checkout-item">
                            <?php if (!empty($item['image'])): ?>
                                <div class="cart-item-thumb">
                                    <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="cart-item-info">
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cart-item-details">
                                    Qty: <?php echo $item['quantity']; ?> |
                                    Category: <?php echo htmlspecialchars($item['category']); ?> |
                                    Farmer: <?php echo htmlspecialchars($item['farmer_name']); ?>
                                </div>
                                <div class="cart-item-price">₱<?php echo number_format($item_total, 2); ?></div>

                                <!-- SHOW PRODUCT LOCATION -->
                                <p style="margin-top:5px; font-weight:bold; color:#444;">
                                    Location: <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php 
                    // Show unique locations for delivery/pickup
                    $unique_locations = array_unique($product_locations);
                    ?>

                    <div class="location-summary">
                        <h4><strong>All Product Locations</strong></h4>
                        <?php foreach ($unique_locations as $loc): ?>
                            <p>• <?php echo htmlspecialchars($loc); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="checkout-section">
                    <h3>Delivery & Payment</h3>
                    <div class="form-group">
                        <label>Delivery Option</label>
                        <select name="delivery_type" id="delivery_type" onchange="updateDelivery()" required>
                            <option value="delivery">Home Delivery</option>
                            <option value="pickup">Pickup</option>
                        </select>
                    </div>

                    <div class="form-group" id="delivery_address_group">
                        <label>Delivery Address</label>
                        <textarea name="delivery_address" id="delivery_address" rows="4" required><?php echo htmlspecialchars($buyer_address); ?></textarea>
                    </div>

                    <div class="form-group" id="pickup_address_group" style="display:none;">
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <input type="text" id="payment_method_display" class="form-control" value="Cash on Delivery (COD)" readonly>
                        <input type="hidden" name="payment_method" id="payment_method" value="cash_on_delivery">
                    </div>
                </div>

                <?php 
                    $summary_delivery_fee = ($display_delivery_type === 'pickup') ? 0 : $delivery_fee_base;
                    $grand_total = $subtotal + $summary_delivery_fee;
                ?>
                <div class="cart-summary" id="summary-box" data-subtotal="<?php echo number_format($subtotal, 2, '.', ''); ?>">
                    <div class="summary-totals">
                    <div class="cart-total-line">
                        <span>Subtotal</span>
                        <span id="subtotal-amount">₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="cart-total-line">
                        <span>Delivery Fee</span>
                        <span id="delivery-fee-amount">₱<?php echo number_format($summary_delivery_fee, 2); ?></span>
                    </div>
                    <div class="cart-total cart-total-strong">
                        <span>Total</span>
                        <span id="total-amount">₱<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    </div>
                    <button class="btn btn-checkout" type="submit">Confirm Order</button>
                    <a href="my_cart.php" class="btn" style="background:#aaa;">Cancel</a>
                </div>

            </div>
            </form>

            </div>
        </div>
    </div>

<script>
// Initialize payment method on page load
document.addEventListener('DOMContentLoaded', function() {
    updateDelivery();
});

function updateDelivery() {
    const type = document.getElementById('delivery_type').value;

    const deliveryGroup = document.getElementById('delivery_address_group');
    const pickupGroup = document.getElementById('pickup_address_group');

    const paymentDisplay = document.getElementById('payment_method_display');
    const paymentHidden = document.getElementById('payment_method');

    const summaryBox = document.getElementById('summary-box');
    const subtotalAmount = document.getElementById('subtotal-amount');
    const deliveryFeeAmount = document.getElementById('delivery-fee-amount');
    const totalAmount = document.getElementById('total-amount');
    const DELIVERY_FEE = 30; // keep in sync with PHP
    const subtotal = summaryBox ? parseFloat(summaryBox.dataset.subtotal || '0') : 0;

    let deliveryFee = 0;

    if (type === "pickup") {
        // HIDE delivery address, SHOW pickup info
        deliveryGroup.style.display = "none";
        pickupGroup.style.display = "block";

        // Set payment method
        paymentDisplay.value = "Cash on Pickup (COP)";
        paymentHidden.value = "cash_on_pickup";

        deliveryFee = 0;

        // Remove delivery requirement
        const deliveryAddress = document.getElementById('delivery_address');
        if (deliveryAddress) {
            deliveryAddress.removeAttribute('required');
        }

    } else {
        // SHOW delivery address, HIDE pickup
        deliveryGroup.style.display = "block";
        pickupGroup.style.display = "none";

        // Set payment method
        paymentDisplay.value = "Cash on Delivery (COD)";
        paymentHidden.value = "cash_on_delivery";

        deliveryFee = DELIVERY_FEE;

        // Require address again
        const deliveryAddress = document.getElementById('delivery_address');
        if (deliveryAddress) {
            deliveryAddress.setAttribute('required', 'required');
        }
    }

    if (deliveryFeeAmount && totalAmount) {
        deliveryFeeAmount.textContent = `₱${deliveryFee.toFixed(2)}`;
        totalAmount.textContent = `₱${(subtotal + deliveryFee).toFixed(2)}`;
    }
}
</script>

</body>
</html>