<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['buyer_id'])) {
    header('Location: buyer_login.php');
    exit();
}

$product_id = intval($_GET['id'] ?? 0);

// ===== GET PRODUCT DETAILS =====
$query = "SELECT 
            p.*, 
            f.name AS farmer_name, 
            f.email AS farmer_email, 
            f.phone AS farmer_phone, 
            f.location AS farmer_location
          FROM products p
          JOIN farmers f ON p.farmer_id = f.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL ERROR: " . $conn->error);
}

$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: browse_products.php');
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// ========= ADD TO CART ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = intval($_POST['quantity']);

    if ($quantity > 0 && $quantity <= $product['quantity']) {

        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE buyer_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $_SESSION['buyer_id'], $product_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();

        if ($cart_result->num_rows > 0) {
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;

            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (buyer_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $_SESSION['buyer_id'], $product_id, $quantity);
            $stmt->execute();
        }

        $stmt->close();
        header('Location: my_cart.php');
        exit();
    }
}

// LOAD PRODUCT IMAGES
$slides = [];
if (!empty($product['image'])) $slides[] = $product['image'];

$tblCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
if ($tblCheck && $tblCheck->num_rows > 0) {
    $stmtG = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY id ASC");
    $stmtG->bind_param('i', $product_id);
    $stmtG->execute();
    $resG = $stmtG->get_result();

    while ($row = $resG->fetch_assoc()) {
        if (!in_array($row['image_path'], $slides))
            $slides[] = $row['image_path'];
    }
    $stmtG->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']); ?> - Product Details</title>

    <!-- Your main buyer theme -->
    <link rel="stylesheet" href="css/buyer_style.css">

    <!-- NEW PRODUCT DETAILS CSS -->
    <link rel="stylesheet" href="css/product_details.css">
</head>

<body>
<div class="buyer-wrapper">
    <?php include 'includes/buyer_sidebar.php'; ?>

    <div class="buyer-content">
        <div class="page-header">
            <h1>Product Details</h1>
            <p>View complete product information</p>
        </div>

        <div class="product-details">

            <!-- =================== PRODUCT IMAGES =================== -->
            <div class="product-image-section">
                <?php if (!empty($slides)): ?>
                <div class="carousel" id="carousel">
                    <div class="carousel-track" id="carouselTrack">
                        <?php foreach ($slides as $img): ?>
                            <div class="carousel-slide">
                                <img src="../<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="carousel-nav">
                        <button class="carousel-btn" id="prevBtn">‹</button>
                        <button class="carousel-btn" id="nextBtn">›</button>
                    </div>

                    <div class="carousel-dots" id="carouselDots"></div>
                </div>

                <?php else: ?>
                    <div class="carousel no-img">
                        No Image Available
                    </div>
                <?php endif; ?>
            </div>

            <!-- =================== PRODUCT INFORMATION =================== -->
            <div class="product-info-detail">
                <h1><?= htmlspecialchars($product['name']); ?></h1>

                <div class="category">Category: <?= htmlspecialchars($product['category']); ?></div>

                <div class="price">₱<?= number_format($product['price'], 2); ?></div>

                <div class="location">📍 <?= htmlspecialchars($product['location']); ?></div>

                <?php if ($product['description']): ?>
                <div class="description">
                    <strong>Description:</strong><br>
                    <?= nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                <?php endif; ?>

                <div class="farmer-info">
                    <h3>👨‍🌾 Farmer Information</h3>
                    <p><strong>Name:</strong> <?= htmlspecialchars($product['farmer_name']); ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($product['farmer_location']); ?></p>
                    <?php if ($product['farmer_phone']): ?>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($product['farmer_phone']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="quantity-info">
                    <strong>Available Quantity:</strong> <span class="quantity-value"><?= $product['quantity']; ?></span>
                </div>

                <?php if ($product['quantity'] > 0): ?>
                <form method="POST" class="add-to-cart-form">
                    <input type="number" name="quantity" min="1" max="<?= $product['quantity']; ?>" value="1" required>
                    <button type="submit" name="add_to_cart">Add to Cart</button>
                </form>
                <?php else: ?>
                    <p style="color:#ff4b4b; font-weight:600;">Out of Stock</p>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<!-- =================== JS FOR CAROUSEL =================== -->
<script>
(function(){
    const track = document.getElementById('carouselTrack');
    if (!track) return;

    const slides = Array.from(track.children);
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const dotsWrap = document.getElementById('carouselDots');
    let index = 0;

    function update(){
        track.style.transform = `translateX(${-(index * 100)}%)`;
        dotsWrap.querySelectorAll('.carousel-dot').forEach((d, i)=>{
            d.classList.toggle('active', i === index);
        });
    }

    function goTo(i){
        index = Math.max(0, Math.min(i, slides.length - 1));
        update();
    }

    slides.forEach((_, i)=>{
        const dot = document.createElement('div');
        dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
        dot.onclick = ()=> goTo(i);
        dotsWrap.appendChild(dot);
    });

    prevBtn.onclick = ()=> goTo(index - 1);
    nextBtn.onclick = ()=> goTo(index + 1);

    update();
})();
</script>

</body>
</html>
