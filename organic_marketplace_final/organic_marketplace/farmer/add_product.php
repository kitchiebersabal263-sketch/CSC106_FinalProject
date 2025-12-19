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

// Check verification status
$colCheck = $conn->query("SHOW COLUMNS FROM farmers LIKE 'verification_status'");
$hasVerification = $colCheck->num_rows > 0;

if ($hasVerification) {
    $stmt = $conn->prepare("SELECT location, verification_status, allowed_categories, seller_type FROM farmers WHERE id = ?");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();
    
    if ($farmer['verification_status'] != 'approved') {
        header('Location: farmer_dashboard.php?error=verification_pending');
        exit();
    }
    
    $farmer_location = $farmer['location'];
    $allowed_categories_json = $farmer['allowed_categories'] ?? '[]';
    $allowed_categories = json_decode($allowed_categories_json, true) ?? [];
    $seller_type = $farmer['seller_type'] ?? 'farmer';
    $stmt->close();
} else {
    // Old schema - no verification
    $stmt = $conn->prepare("SELECT location FROM farmers WHERE id = ?");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();
    $farmer_location = $farmer['location'];
    $allowed_categories = ['Vegetables', 'Fruits', 'Fish', 'Cacao', 'Eggs', 'Spices']; // All categories allowed
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $category = sanitize_input($_POST['category']);
    $price = sanitize_input($_POST['price']);
    $quantity = sanitize_input($_POST['quantity']);
    $description = sanitize_input($_POST['description'] ?? '');
    $unit = isset($_POST['unit']) ? sanitize_input($_POST['unit']) : 'kilo';
    // Ensure category value is part of ENUM; if not, try to alter the column to include it
    if (!empty($category)) {
        $colRes = $conn->query("SHOW COLUMNS FROM `products` LIKE 'category'");
        if ($colRes && $col = $colRes->fetch_assoc()) {
            $type = $col['Type']; // e.g. enum('Vegetables','Fruits',...)
            if (strpos($type, "'" . $conn->real_escape_string($category) . "'") === false) {
                // parse existing values
                preg_match("/^enum\\((.*)\\)$/", $type, $m);
                $vals = [];
                if (isset($m[1])) {
                    $parts = str_getcsv($m[1], ',', "'");
                    foreach ($parts as $p) {
                        $p = trim($p, "' ");
                        if ($p !== '') $vals[] = $p;
                    }
                }
                if (!in_array($category, $vals)) {
                    $vals[] = $category;
                    $enumList = array_map(function($v){ return "'" . $v . "'"; }, $vals);
                    $enumSql = implode(',', $enumList);
                    $alter = "ALTER TABLE `products` MODIFY COLUMN `category` ENUM(" . $enumSql . ") NOT NULL";
                    $conn->query($alter);
                }
            }
        }
    }
    // Ensure `unit` column exists in products table; if not, try to add it (safe migration)
    $colCheckSql = "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $tbl = 'products';
    $col = 'unit';
    $stmtChk = $conn->prepare($colCheckSql);
    if ($stmtChk) {
        // bind_param requires variables (passed by reference). Don't pass constants directly.
        $dbName = DB_NAME;
        $stmtChk->bind_param('sss', $dbName, $tbl, $col);
        $stmtChk->execute();
        $resChk = $stmtChk->get_result();
        $rowChk = $resChk->fetch_assoc();
        $stmtChk->close();
        if (empty($rowChk) || intval($rowChk['cnt']) === 0) {
            // attempt to add column
            $alterSql = "ALTER TABLE `products` ADD COLUMN `unit` ENUM('kilo','piece') NOT NULL DEFAULT 'kilo' AFTER `image`";
            if (!$conn->query($alterSql)) {
                $error = 'Database is missing required `unit` column and automatic migration failed: ' . $conn->error;
            }
        }
    } else {
        // if prepare failed, attempt a direct ALTER just in case
        $alterSql = "ALTER TABLE `products` ADD COLUMN `unit` ENUM('kilo','piece') NOT NULL DEFAULT 'kilo' AFTER `image`";
        if ($conn->query($alterSql) === FALSE) {
            $error = 'Database migration for `unit` column failed: ' . $conn->error;
        }
    }
    $unit = isset($_POST['unit']) ? sanitize_input($_POST['unit']) : 'kilo';
    
    // Validate category against allowed categories
    if ($hasVerification && !empty($allowed_categories) && !in_array($category, $allowed_categories)) {
        $error = 'You are not authorized to sell products in this category. Please select from your allowed categories.';
    }
    
    // Handle multiple image upload
    $image_path = '';
    $uploaded_images = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $upload_dir = '../uploads/product_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $allowed = ['image/jpeg','image/png','image/webp','image/avif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        foreach ($_FILES['images']['name'] as $idx => $origName) {
            if ($_FILES['images']['error'][$idx] !== UPLOAD_ERR_OK) continue;
            $mime = finfo_file($finfo, $_FILES['images']['tmp_name'][$idx]);
            if (!in_array($mime, $allowed)) continue;
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $file_name = uniqid('prod_') . '.' . $ext;
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['images']['tmp_name'][$idx], $target_path)) {
                $rel = 'uploads/product_images/' . $file_name;
                $uploaded_images[] = $rel;
            }
        }
        finfo_close($finfo);
        if (!empty($uploaded_images)) {
            $image_path = $uploaded_images[0]; // first as main
        }
    }
    
    // Validate category again before insert
    if ($hasVerification && !empty($allowed_categories) && !in_array($category, $allowed_categories)) {
        $error = 'You are not authorized to sell products in this category. Please select from your allowed categories.';
    }
    
    if (empty($error)) {
        // Prepare SQL statement with 9 placeholders (includes unit)
        $stmt = $conn->prepare("INSERT INTO products (farmer_id, name, category, price, quantity, image, description, location, unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind parameters: i=integer, s=string, d=double
        // Types: farmer_id(i), name(s), category(s), price(d), quantity(i), image(s), description(s), location(s), unit(s)
        $stmt->bind_param("issdissss", $farmer_id, $name, $category, $price, $quantity, $image_path, $description, $farmer_location, $unit);
        
        if ($stmt->execute()) {
            $newProductId = $stmt->insert_id;
            // Persist additional images if table exists
            $tblCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
            if ($tblCheck && $tblCheck->num_rows > 0 && !empty($uploaded_images)) {
                // ensure first is in products.image; others into product_images
                foreach ($uploaded_images as $i => $img) {

                    // Skip first image because already stored in products.image
                    if ($i === 0) continue;
                
                    $is_primary = 0;
                
                    $stmtImg = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                    if ($stmtImg) {
                        $stmtImg->bind_param("isi", $newProductId, $img, $is_primary);
                        $stmtImg->execute();
                        $stmtImg->close();
                    }
                }                
                    }
            $success = 'Product added successfully!';
            header('Location: my_products.php');
            exit();
        } else {
            $error = 'Failed to add product. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Organic Marketplace</title>
    <link rel="stylesheet" href="css/farmer_style.css">
    <style>
        /* Two-column form layout */
        .form-container .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            align-items: start;
        }
        .form-container .two-column .form-group {
            margin: 0;
        }
        /* Elements that should span both columns */
        .col-span-2 { grid-column: 1 / -1; }
        /* Make file input and description span both columns and button align */
        .form-container .helper-note { margin-top: 6px; font-size: 0.9em; color:#666; }
        .form-container .btn-row { display:flex; justify-content:flex-end; gap:12px; }

        /* NEW: split row for Quantity + Product Images */
        .two-split .split { display:flex; gap:18px; }
        .two-split .split > .split-item { flex:1; }
        /* ensure the file input doesn't overflow its column */
        .two-split input[type="file"] { width:100%; box-sizing:border-box; }
        /* image preview list */
        .image-list { display:flex; flex-direction:column; gap:6px; margin-top:8px; }
        .image-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 10px; border:1px solid #e5e5e5; border-radius:6px; background:#fafafa; }
        .image-row span { font-size:0.95em; color:#333; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        @media (max-width: 768px) {
            .form-container .two-column { grid-template-columns: 1fr; }
            .form-container .btn-row { justify-content: stretch; }
            .two-split .split { flex-direction:column; }
        }
    </style>
</head>
<body>
    <div class="farmer-wrapper">
        <?php include 'includes/farmer_sidebar.php'; ?>
        <div class="farmer-content">
            <div class="page-header">
                <h1> Add Product</h1>
                <p>Create a new product listing</p>
            </div>
            
            <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
             <form method="POST" action="" enctype="multipart/form-data">
                <div class="two-column">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php
                            $all_categories = ['Vegetables', 'Fruits', 'Fish', 'Cacao', 'Eggs', 'Spices'];
                            foreach ($all_categories as $cat):
                                if (!$hasVerification || empty($allowed_categories) || in_array($cat, $allowed_categories)):
                            ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </select>
                        <?php if ($hasVerification && !empty($allowed_categories)): ?>
                            <small style="color: #666; font-size: 0.85em;">Your seller type allows: <?php echo implode(', ', $allowed_categories); ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Price (₱)</label>
                        <input type="number" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="unit" required>
                            <option value="kilo">Per Kilo</option>
                            <option value="piece">Per Piece</option>
                        </select>
                    </div>

                    <!-- Quantity (left column) -->
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" min="0" required>
                    </div>

                    <!-- Product Images (right column, beside Quantity) -->
                    <div class="form-group">
                        <label>Product Images</label>
                        <input type="file" id="add-images" name="images[]" accept="image/*" multiple>
                        <div class="helper-note">You can upload multiple images. The first will be used as the main image. You can remove selected files below.</div>
                        <div id="add-images-preview" class="image-list"></div>
                    </div>

                    <div class="form-group col-span-2">
                        <label>Description (Optional)</label>
                        <textarea name="description" rows="4"></textarea>
                    </div>

                    <div class="form-group col-span-2 btn-row">
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </div>
            </form>
        </div>
        </div>
    </div>
    <script src="js/farmer_script.js"></script>
    <script>
    (function() {
        const input = document.getElementById('add-images');
        const preview = document.getElementById('add-images-preview');
        if (!input || !preview) return;

        function renderList(files) {
            preview.innerHTML = '';
            Array.from(files).forEach((file, idx) => {
                const row = document.createElement('div');
                row.className = 'image-row';
                const name = document.createElement('span');
                name.textContent = file.name;
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.textContent = 'Remove';
                removeBtn.className = 'btn btn-secondary';
                removeBtn.style.marginLeft = '8px';
                removeBtn.onclick = () => {
                    const dt = new DataTransfer();
                    Array.from(files).forEach((f, i) => { if (i !== idx) dt.items.add(f); });
                    input.files = dt.files;
                    renderList(input.files);
                };
                row.appendChild(name);
                row.appendChild(removeBtn);
                preview.appendChild(row);
            });
        }

        input.addEventListener('change', () => renderList(input.files));
    })();
    </script>
</body>
</html>