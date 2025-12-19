<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['farmer_id'])) {
    header('Location: farmer_login.php');
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

// Get product id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my_products.php');
    exit();
}

$product_id = (int)$_GET['id'];

// Ensure product belongs to farmer
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND farmer_id = ? LIMIT 1");
$stmt->bind_param("ii", $product_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: my_products.php');
    exit();
}

// Check if verification columns exist and get seller type info
$colCheck = $conn->query("SHOW COLUMNS FROM farmers LIKE 'verification_status'");
$hasVerification = $colCheck->num_rows > 0;

$allowed_categories = [];
if ($hasVerification) {
    $stmt = $conn->prepare("SELECT seller_type FROM farmers WHERE id = ?");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $farmer_result = $stmt->get_result();
    $farmer_data = $farmer_result->fetch_assoc();
    $stmt->close();
    
    if ($farmer_data && isset($farmer_data['seller_type'])) {
        $seller_type = $farmer_data['seller_type'];
        require_once __DIR__ . '/../database/seller_type_config.php';
        $allowed_categories = get_allowed_categories($seller_type);
    }
}

$errors = [];

// Detect AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? $conn->real_escape_string(trim($_POST['name'])) : '';
    $category = isset($_POST['category']) ? $conn->real_escape_string(trim($_POST['category'])) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $description = isset($_POST['description']) ? $conn->real_escape_string(trim($_POST['description'])) : '';
    $location = isset($_POST['location']) ? $conn->real_escape_string(trim($_POST['location'])) : (isset($product['location']) ? $product['location'] : '');
    // unit (kilo or piece)
    $unit = isset($_POST['unit']) ? $conn->real_escape_string(trim($_POST['unit'])) : (isset($product['unit']) ? $product['unit'] : 'kilo');

    if ($name === '') $errors[] = 'Product name is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if ($quantity < 0) $errors[] = 'Quantity cannot be negative.';

    // Handle images upload if provided
$image_path = isset($product['image']) ? $product['image'] : '';
// Load existing gallery images if table exists
$existing_images = [];
$tblCheckImg = $conn->query("SHOW TABLES LIKE 'product_images'");
if ($tblCheckImg && $tblCheckImg->num_rows > 0) {
    $stmtImgs = $conn->prepare("SELECT id, image_path, is_primary FROM product_images WHERE product_id = ?");
    $stmtImgs->bind_param("i", $product_id);
    $stmtImgs->execute();
    $resImgs = $stmtImgs->get_result();
    if ($resImgs) {
        while ($row = $resImgs->fetch_assoc()) {
            $existing_images[] = $row;
        }
        $stmtImgs->close();
    }
}
    $new_images = [];
    $remove_main = isset($_POST['remove_main']) ? true : false;
    $remove_images = isset($_POST['remove_images']) && is_array($_POST['remove_images']) ? array_map('intval', $_POST['remove_images']) : [];

    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp','image/avif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $upload_dir = __DIR__ . '/../uploads/product_images/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
        foreach ($_FILES['images']['name'] as $idx => $origName) {
            if ($_FILES['images']['error'][$idx] !== UPLOAD_ERR_OK) continue;
            $mime = finfo_file($finfo, $_FILES['images']['tmp_name'][$idx]);
            if (!in_array($mime, $allowed)) { $errors[] = 'Invalid image type for one of the files.'; continue; }
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $newName = uniqid('prod_') . '.' . $ext;
            $dest = $upload_dir . $newName;
            if (!move_uploaded_file($_FILES['images']['tmp_name'][$idx], $dest)) {
                $errors[] = 'Failed to move one of the uploaded images.';
            } else {
                $rel = 'uploads/product_images/' . $newName;
                $new_images[] = $rel;
            }
        }
        finfo_close($finfo);
        if (!empty($new_images) && empty($image_path)) {
            $image_path = $new_images[0];
        }
    }

    if (empty($errors)) {
        // Remove main image if requested
        if ($remove_main && !empty($image_path)) {
            $fileToDelete = __DIR__ . '/../' . $image_path;
            if (file_exists($fileToDelete)) { @unlink($fileToDelete); }
            $image_path = '';
        }

        // Remove selected gallery images
        if (!empty($remove_images) && !empty($existing_images)) {
            foreach ($existing_images as $img) {
                if (in_array((int)$img['id'], $remove_images, true)) {
                    $path = __DIR__ . '/../' . $img['image_path'];
                    if (file_exists($path)) { @unlink($path); }
                    $conn->query("DELETE FROM product_images WHERE id = " . (int)$img['id']);
                }
            }
        }

        // If main image is empty but there are still gallery images, promote one to main
        if ($image_path === '') {
            $stmtPrimary = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1");
            $stmtPrimary->bind_param("i", $product_id);
            $stmtPrimary->execute();
            $resPrimary = $stmtPrimary->get_result();
            if ($resPrimary && $resPrimary->num_rows > 0) {
                $rowP = $resPrimary->fetch_assoc();
                $image_path = $rowP['image_path'];
            }
            $stmtPrimary->close();
        }

        // Ensure category is present in products.category ENUM; attempt to alter column if needed
        if (!empty($category)) {
            $colRes = $conn->query("SHOW COLUMNS FROM `products` LIKE 'category'");
            if ($colRes && $col = $colRes->fetch_assoc()) {
                $type = $col['Type'];
                if (strpos($type, "'" . $conn->real_escape_string($category) . "'") === false) {
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

        // Update columns including unit and location
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, quantity = ?, description = ?, image = ?, location = ?, unit = ? WHERE id = ? AND farmer_id = ?");
        $stmt->bind_param("ssdississi", $name, $category, $price, $quantity, $description, $image_path, $location, $unit, $product_id, $farmer_id);
        if ($stmt->execute()) {
            // Persist new images if table exists
            $tblCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
            if ($tblCheck && $tblCheck->num_rows > 0 && !empty($new_images)) {
                // Check if any primary exists
                $hasPrimary = false;
                $stmtP = $conn->prepare("SELECT 1 FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
                $stmtP->bind_param("i", $product_id);
                $stmtP->execute();
                $resP = $stmtP->get_result();
                if ($resP && $resP->num_rows > 0) { $hasPrimary = true; }
                $stmtP->close();
                foreach ($new_images as $i => $img) {
                    $is_primary = (!$hasPrimary && $i === 0) ? 1 : 0;
                    $stmtImg = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                    if ($stmtImg) {
                        $stmtImg->bind_param('isi', $product_id, $img, $is_primary);
                        $stmtImg->execute();
                        $stmtImg->close();
                    }
                }
            }
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product updated']);
                exit();
            }
            header('Location: my_products.php?updated=1');
            exit();
        } else {
            $errors[] = 'Failed to update product: ' . $conn->error;
            $stmt->close();
        }
    }

    // If AJAX and there are errors, return JSON
    if ($is_ajax && !empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit();
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Organic Marketplace</title>
    <link rel="stylesheet" href="css/farmer_style.css">
    <link rel="stylesheet" href="css/add_product_style.css">
    <style>
        /* Form container styling to match modern design */
        .form-container {
            background: var(--card-bg, #ffffff);
            padding: 30px;
            border-radius: var(--radius, 16px);
            box-shadow: var(--shadow-card, 0 4px 20px rgba(26, 77, 46, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05));
            border: 1px solid var(--border-color, #e5e7eb);
            margin-bottom: 24px;
        }
        
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
        .form-container .helper-note { margin-top: 6px; font-size: 0.9em; color:#666; }
        .form-container .btn-row { display:flex; justify-content:flex-end; gap:12px; }

        /* Split row for Quantity + Product Images */
        .two-split .split { display:flex; gap:18px; }
        .two-split .split > .split-item { flex:1; }
        .two-split input[type="file"] { width:100%; box-sizing:border-box; }
        /* image list chips */
        .image-list { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
        .image-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid #e5e5e5; border-radius:8px; background:#fafafa; position:relative; }
        .image-chip .thumb { width:40px; height:40px; border-radius:6px; overflow:hidden; border:1px solid #e5e5e5; display:inline-flex; }
        .image-chip .thumb img { width:100%; height:100%; object-fit:cover; display:block; }
        .image-chip .remove-btn { background:#e74c3c; color:#fff; border:none; border-radius:50%; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; font-weight:700; cursor:pointer; font-size:12px; }
        .image-chip.removed { opacity:0.5; text-decoration:line-through; }
        .image-chip.removed .thumb { filter: grayscale(1); }
        .image-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 10px; border:1px solid #e5e5e5; border-radius:6px; background:#fafafa; margin-top:6px; }
        .image-row span { font-size:0.95em; color:#333; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        
        @media (max-width: 768px) {
            .form-container { padding: 20px; }
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
                <h1>Edit Product</h1>
                <p>Update product details below.</p>
            </div>

            <div class="form-container">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $e): ?>
                                    <li><?php echo htmlspecialchars($e); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                    <div class="two-column">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
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
                                    <option value="<?php echo $cat; ?>" <?php echo $product['category'] == $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
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
                            <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Unit</label>
                            <select name="unit" required>
                                <option value="kilo" <?php echo $product['unit'] == 'kilo' ? 'selected' : ''; ?>>Per Kilo</option>
                                <option value="piece" <?php echo $product['unit'] == 'piece' ? 'selected' : ''; ?>>Per Piece</option>
                            </select>
                        </div>

                        <!-- Quantity and Product Images side-by-side -->
                        <div class="form-group col-span-2 two-split">
                            <div class="split">
                                <div class="split-item">
                                    <label>Quantity</label>
                                    <input type="number" name="quantity" min="0" value="<?php echo htmlspecialchars($product['quantity']); ?>" required>
                                </div>
                                <div class="split-item">
                                    <label>Product Images</label>
                                    <input type="file" id="edit-images" name="images[]" accept="image/*" multiple>
                                    <div class="helper-note">Upload new images; you can remove selected files below before saving.</div>
                                    <div id="edit-images-preview" class="image-list"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>" placeholder="Product location">
                        </div>

                        <div class="form-group col-span-2">
                            <label>Description (Optional)</label>
                            <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>

                        <!-- Existing Images -->
                        <div class="form-group col-span-2">
                            <label>Existing Images</label>
                            <div class="image-list" id="existing-images-list">
                                <?php if (!empty($product['image'])): ?>
                                    <div class="image-chip" data-type="main">
                                        <span class="thumb"><img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Main"></span>
                                        <span>Main image</span>
                                        <button type="button" class="remove-btn" data-target="remove_main">&times;</button>
                                        <input type="hidden" name="remove_main" value="1" disabled>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($existing_images)): ?>
                                    <?php foreach ($existing_images as $img): ?>
                                        <div class="image-chip" data-type="gallery" data-id="<?php echo $img['id']; ?>">
                                            <span class="thumb"><img src="../<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery"></span>
                                            <span>Gallery</span>
                                            <button type="button" class="remove-btn" data-id="<?php echo $img['id']; ?>">&times;</button>
                                            <input type="hidden" name="remove_images[]" value="<?php echo $img['id']; ?>" disabled>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (empty($product['image']) && empty($existing_images)): ?>
                                    <div style="color:#666;font-size:0.9em;">No images yet.</div>
                                <?php endif; ?>
                            </div>
                            <small class="helper-note">Click “×” to mark an image for deletion. If main is removed and no others remain, please upload a new one.</small>
                        </div>

                        <div class="form-group col-span-2 btn-row">
                            <a href="my_products.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
            <script>
            (function(){
                const form = document.getElementById('editProductForm');
                const saveBtn = document.getElementById('saveBtn');
                const msgEl = document.getElementById('formMessage');

                if (!form) return;

                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    msgEl.textContent = '';
                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Saving...';

                    const formData = new FormData(form);
                    // Send AJAX request
                    fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    }).then(resp => {
                        const ct = resp.headers.get('content-type') || '';
                        if (ct.indexOf('application/json') !== -1) {
                            return resp.json();
                        }
                        // Not JSON => read text for debugging
                        return resp.text().then(text => ({ __raw_text: text, ok: resp.ok, status: resp.status }));
                    })
                    .then(data => {
                        if (data && data.__raw_text) {
                            // Show raw server response for debugging
                            saveBtn.disabled = false;
                            saveBtn.textContent = 'Save Changes';
                            msgEl.innerHTML = '<div style="color:#c33; max-width:400px;">Server returned non-JSON response:<pre style="white-space:pre-wrap;">' + escapeHtml(data.__raw_text) + '</pre></div>';
                            return;
                        }

                        if (data.success) {
                            msgEl.innerHTML = '<span style="color:green; font-weight:600;">Saved</span>';
                            // Redirect back after short delay
                            setTimeout(() => {
                                window.location.href = 'my_products.php?updated=1';
                            }, 900);
                        } else {
                            saveBtn.disabled = false;
                            saveBtn.textContent = 'Save Changes';
                            if (data.errors && data.errors.length) {
                                msgEl.innerHTML = '<div style="color:#c33;"><ul>' + data.errors.map(e=>'<li>'+escapeHtml(e)+'</li>').join('') + '</ul></div>';
                            } else if (data.message) {
                                msgEl.innerHTML = '<span style="color:#c33;">'+escapeHtml(data.message)+'</span>';
                            } else if (data.debug) {
                                msgEl.innerHTML = '<div style="color:#c33;"><strong>Debug:</strong><pre style="white-space:pre-wrap;">'+escapeHtml(data.debug)+'</pre></div>';
                            } else {
                                msgEl.innerHTML = '<span style="color:#c33;">Failed to save</span>';
                            }
                        }
                    }).catch(err=>{
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Save Changes';
                        const msg = (err && err.message) ? err.message : 'Network error';
                        msgEl.innerHTML = '<span style="color:#c33;">'+escapeHtml(msg)+'</span>';
                        console.error('Fetch error:', err);
                    });
                });

                function escapeHtml(s) {
                    if (!s) return '';
                    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                }
            })();
            </script>
    <script>
    (function(){
        const input = document.getElementById('edit-images');
        const preview = document.getElementById('edit-images-preview');
        if (input && preview) {
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
        }
    })();
    (function(){
        const existingList = document.getElementById('existing-images-list');
        if (!existingList) return;
        existingList.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const chip = btn.closest('.image-chip');
                if (!chip) return;
                const hidden = chip.querySelector('input[type="hidden"]');
                if (hidden) {
                    const nowDisabled = !hidden.disabled;
                    hidden.disabled = nowDisabled;
                    chip.classList.toggle('removed', !nowDisabled);
                }
            });
        });
    })();
    </script>
    <script src="js/farmer_script.js"></script>
        </body>
        </html>
