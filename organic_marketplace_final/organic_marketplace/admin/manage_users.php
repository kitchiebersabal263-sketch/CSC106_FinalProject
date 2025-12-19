<?php
session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle delete user
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    $user_type = sanitize_input($_GET['type'] ?? '');
    $preserve_filter = sanitize_input($_GET['user_type'] ?? 'all');
    
    if ($user_type === 'farmer') {
        // Delete farmer and related data
        $conn->begin_transaction();
        try {
            // Delete farmer's products first (using prepared statement)
            $stmt = $conn->prepare("DELETE FROM products WHERE farmer_id = ?");
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete products: " . $stmt->error);
            }
            $stmt->close();
            
            // Delete farmer (using prepared statement)
            $stmt = $conn->prepare("DELETE FROM farmers WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete farmer: " . $stmt->error);
            }
            $stmt->close();
            
            $conn->commit();
            $message = 'Seller deleted successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error deleting seller: ' . $e->getMessage();
            $message_type = 'error';
        }
    } elseif ($user_type === 'buyer') {
        // Delete buyer and related data
        $conn->begin_transaction();
        try {
            // Delete buyer's cart items (using prepared statement)
            $stmt = $conn->prepare("DELETE FROM cart WHERE buyer_id = ?");
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete cart items: " . $stmt->error);
            }
            $stmt->close();
            
            // Delete buyer (using prepared statement)
            $stmt = $conn->prepare("DELETE FROM buyers WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete buyer: " . $stmt->error);
            }
            $stmt->close();
            
            $conn->commit();
            $message = 'Buyer deleted successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Error deleting buyer: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    // Preserve user_type filter in redirect
    $redirect_url = 'manage_users.php?msg=' . urlencode($message) . '&type=' . $message_type;
    if (!empty($preserve_filter)) {
        $redirect_url .= '&user_type=' . urlencode($preserve_filter);
    }
    header('Location: ' . $redirect_url);
    exit();
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $farmer_id = intval($_POST['farmer_id']);
    $admin_id = $_SESSION['admin_id'];
    
    // Check if verification columns exist
    $colCheck = $conn->query("SHOW COLUMNS FROM farmers LIKE 'verification_status'");
    $hasVerification = $colCheck->num_rows > 0;
    
    if ($hasVerification) {
        if ($_POST['action'] == 'approve') {
            $stmt = $conn->prepare("UPDATE farmers SET verification_status = 'approved', verified_at = NOW(), verified_by = ? WHERE id = ?");
            $stmt->bind_param("ii", $admin_id, $farmer_id);
            if ($stmt->execute()) {
                $message = 'Seller approved successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error approving seller: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif ($_POST['action'] == 'reject') {
            $rejection_reason = sanitize_input($_POST['rejection_reason'] ?? '');
            if (empty($rejection_reason)) {
                $rejection_reason = 'Account did not meet verification requirements.';
            }
            $stmt = $conn->prepare("UPDATE farmers SET verification_status = 'rejected', rejection_reason = ?, verified_at = NOW(), verified_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $rejection_reason, $admin_id, $farmer_id);
            if ($stmt->execute()) {
                $message = 'Seller rejected successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error rejecting seller: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = 'Verification system not yet migrated. Please run the migration script first.';
        $message_type = 'error';
    }
}

// Get search and sort parameters
$search_farmer = sanitize_input($_GET['search_farmer'] ?? '');
$search_buyer = sanitize_input($_GET['search_buyer'] ?? '');
$sort_farmer = sanitize_input($_GET['sort_farmer'] ?? 'created_at');
$sort_buyer = sanitize_input($_GET['sort_buyer'] ?? 'created_at');
$order_farmer = sanitize_input($_GET['order_farmer'] ?? 'DESC');
$order_buyer = sanitize_input($_GET['order_buyer'] ?? 'DESC');

// Get user type filter
$user_type_filter = sanitize_input($_GET['user_type'] ?? 'all');
// Validate user_type_filter
$allowed_user_types = ['all', 'sellers', 'buyers'];
$user_type_filter = in_array($user_type_filter, $allowed_user_types) ? $user_type_filter : 'all';

// Validate sort columns
$allowed_farmer_sorts = ['id', 'name', 'email', 'location', 'created_at', 'verification_status'];
$allowed_buyer_sorts = ['id', 'name', 'email', 'phone', 'created_at'];
$sort_farmer = in_array($sort_farmer, $allowed_farmer_sorts) ? $sort_farmer : 'created_at';
$sort_buyer = in_array($sort_buyer, $allowed_buyer_sorts) ? $sort_buyer : 'created_at';
$order_farmer = strtoupper($order_farmer) === 'ASC' ? 'ASC' : 'DESC';
$order_buyer = strtoupper($order_buyer) === 'ASC' ? 'ASC' : 'DESC';

// Check if verification columns exist
$colCheck = $conn->query("SHOW COLUMNS FROM farmers LIKE 'verification_status'");
$hasVerification = $colCheck->num_rows > 0;

// Build farmers query with search and sort
$farmer_where = "1=1";
if (!empty($search_farmer)) {
    $search_escaped = $conn->real_escape_string($search_farmer);
    $farmer_where .= " AND (f.name LIKE '%$search_escaped%' OR f.email LIKE '%$search_escaped%' OR f.location LIKE '%$search_escaped%' OR f.phone LIKE '%$search_escaped%')";
}

$farmer_order = "ORDER BY ";
if ($hasVerification && $sort_farmer === 'verification_status') {
    $farmer_order .= "CASE f.verification_status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 END, f.created_at $order_farmer";
} else {
    $farmer_order .= "f.$sort_farmer $order_farmer";
}

if ($hasVerification) {
    $farmers = $conn->query("SELECT f.*, a.username as verified_by_username 
                              FROM farmers f 
                              LEFT JOIN admins a ON f.verified_by = a.id 
                              WHERE $farmer_where
                              $farmer_order");
    if (!$farmers) {
        die("Error fetching farmers: " . $conn->error);
    }
} else {
    $farmers = $conn->query("SELECT * FROM farmers WHERE $farmer_where $farmer_order");
    if (!$farmers) {
        die("Error fetching farmers: " . $conn->error);
    }
}

// Build buyers query with search and sort
$buyer_where = "1=1";
if (!empty($search_buyer)) {
    $search_escaped = $conn->real_escape_string($search_buyer);
    $buyer_where .= " AND (name LIKE '%$search_escaped%' OR email LIKE '%$search_escaped%' OR phone LIKE '%$search_escaped%' OR address LIKE '%$search_escaped%')";
}

$buyers = $conn->query("SELECT * FROM buyers WHERE $buyer_where ORDER BY $sort_buyer $order_buyer");
if (!$buyers) {
    die("Error fetching buyers: " . $conn->error);
}

$page_title = 'Manage Users';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <h1>Manage Users</h1>
    <p><a href="admin_dashboard.php">← Back to Dashboard</a></p>
</div>

<?php 
// Show message from GET parameter (after redirect)
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['type'] ?? 'success';
}
if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 20px;"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!$hasVerification): ?>
    <div class="alert alert-error" style="margin-bottom: 15px;">
        <strong>⚠ Migration Required:</strong> Please run the migration script to enable seller verification: 
        <code>php database/migrate_seller_verification.php</code>
    </div>
<?php endif; ?>

<!-- User Type Filter -->
<div style="margin-bottom: 15px; background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
    <form method="GET" action="" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <label style="font-weight: 600; margin-right: 8px; font-size: 0.9em;">Filter:</label>
        <select name="user_type" onchange="this.form.submit()" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; background: white; cursor: pointer; width: 140px;">
            <option value="all" <?php echo $user_type_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
            <option value="sellers" <?php echo $user_type_filter === 'sellers' ? 'selected' : ''; ?>>Sellers Only</option>
            <option value="buyers" <?php echo $user_type_filter === 'buyers' ? 'selected' : ''; ?>>Buyers Only</option>
        </select>
        <!-- Preserve other GET parameters -->
        <?php if (!empty($search_farmer)): ?>
            <input type="hidden" name="search_farmer" value="<?php echo htmlspecialchars($search_farmer); ?>">
        <?php endif; ?>
        <?php if (!empty($search_buyer)): ?>
            <input type="hidden" name="search_buyer" value="<?php echo htmlspecialchars($search_buyer); ?>">
        <?php endif; ?>
        <?php if (!empty($sort_farmer)): ?>
            <input type="hidden" name="sort_farmer" value="<?php echo htmlspecialchars($sort_farmer); ?>">
        <?php endif; ?>
        <?php if (!empty($sort_buyer)): ?>
            <input type="hidden" name="sort_buyer" value="<?php echo htmlspecialchars($sort_buyer); ?>">
        <?php endif; ?>
        <?php if (!empty($order_farmer)): ?>
            <input type="hidden" name="order_farmer" value="<?php echo htmlspecialchars($order_farmer); ?>">
        <?php endif; ?>
        <?php if (!empty($order_buyer)): ?>
            <input type="hidden" name="order_buyer" value="<?php echo htmlspecialchars($order_buyer); ?>">
        <?php endif; ?>
    </form>
</div>

<?php if ($user_type_filter === 'all' || $user_type_filter === 'sellers'): ?>
<div class="table-container" style="margin-bottom: 20px;">
    <h2 style="margin-bottom: 15px; font-size: 1.3em;">🧑‍🌾 Sellers</h2>
    <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="font-size: 0.9em;">
            <strong>Pending Verification:</strong> 
            <?php 
            if ($hasVerification) {
                $pending_count = $conn->query("SELECT COUNT(*) as cnt FROM farmers WHERE verification_status = 'pending'")->fetch_assoc()['cnt'];
                echo "<span style='background: #ff9800; color: white; padding: 3px 10px; border-radius: 5px; margin-left: 10px;'>{$pending_count}</span>";
            }
            ?>
        </div>
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user_type_filter); ?>">
            <input type="text" name="search_farmer" value="<?php echo htmlspecialchars($search_farmer); ?>" 
                   placeholder="Search by name, email, location..." 
                   style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; min-width: 250px;">
            <select name="sort_farmer" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                <option value="created_at" <?php echo $sort_farmer === 'created_at' ? 'selected' : ''; ?>>Sort by Date</option>
                <option value="name" <?php echo $sort_farmer === 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                <option value="email" <?php echo $sort_farmer === 'email' ? 'selected' : ''; ?>>Sort by Email</option>
                <option value="location" <?php echo $sort_farmer === 'location' ? 'selected' : ''; ?>>Sort by Location</option>
                <?php if ($hasVerification): ?>
                <option value="verification_status" <?php echo $sort_farmer === 'verification_status' ? 'selected' : ''; ?>>Sort by Status</option>
                <?php endif; ?>
            </select>
            <select name="order_farmer" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                <option value="DESC" <?php echo $order_farmer === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                <option value="ASC" <?php echo $order_farmer === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
            </select>
            <button type="submit" style="background: #2196F3 !important; color: white !important; padding: 6px 12px !important; font-size: 0.85em !important; white-space: nowrap !important; border: none !important; border-radius: 5px !important; cursor: pointer !important; min-width: auto !important; width: auto !important; max-width: none !important;">Search</button>
            <?php if (!empty($search_farmer)): ?>
                <a href="manage_users.php?user_type=<?php echo urlencode($user_type_filter); ?>" style="background: #999 !important; color: white !important; padding: 6px 12px !important; font-size: 0.85em !important; text-decoration: none !important; white-space: nowrap !important; border-radius: 5px !important; display: inline-block !important; min-width: auto !important; width: auto !important; max-width: none !important;">✕</a>
            <?php endif; ?>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Location</th>
                <th>Phone</th>
                <th>Seller Type</th>
                <?php if ($hasVerification): ?>
                <th>Status</th>
                <th>Document</th>
                <th>Verified By</th>
                <th>Actions</th>
                <?php endif; ?>
                <th>Joined</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($farmers->num_rows > 0): ?>
                <?php while ($farmer = $farmers->fetch_assoc()): ?>
                    <tr style="<?php echo ($hasVerification && $farmer['verification_status'] == 'pending') ? 'background: #fff3cd;' : ''; ?>">
                        <td><?php echo $farmer['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($farmer['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($farmer['email']); ?></td>
                        <td><?php echo htmlspecialchars($farmer['location']); ?></td>
                        <td><?php echo htmlspecialchars($farmer['phone'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            $seller_type = $farmer['seller_type'] ?? 'farmer';
                            echo get_seller_type_badge($seller_type, 'medium');
                            ?>
                        </td>
                        <?php if ($hasVerification): ?>
                        <td>
                            <?php
                            $status = $farmer['verification_status'] ?? 'pending';
                            $status_colors = ['pending' => '#ff9800', 'approved' => '#4caf50', 'rejected' => '#f44336'];
                            $status_labels = ['pending' => '⏳ Pending', 'approved' => '✅ Approved', 'rejected' => '❌ Rejected'];
                            echo "<span style='background: {$status_colors[$status]}; color: white; padding: 5px 10px; border-radius: 5px; font-size: 0.85em;'>" . ($status_labels[$status] ?? $status) . "</span>";
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($farmer['verification_document'])): ?>
                                <a href="../<?php echo htmlspecialchars($farmer['verification_document']); ?>" target="_blank" style="color: #2196F3;">📄 View Document</a>
                            <?php else: ?>
                                <span style="color: #999;">No document</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($farmer['verified_by_username'] ?? 'N/A'); ?>
                            <?php if (!empty($farmer['verified_at'])): ?>
                                <br><small style="color: #666;"><?php echo date('M d, Y', strtotime($farmer['verified_at'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status == 'pending'): ?>
                                <form method="POST" action="" style="display: inline-block; margin-right: 5px;">
                                    <input type="hidden" name="farmer_id" value="<?php echo $farmer['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn" style="background: #4caf50; color: white; padding: 5px 10px; font-size: 0.85em; border: none; cursor: pointer; border-radius: 3px;">Approve</button>
                                </form>
                                <button type="button" onclick="showRejectModal(<?php echo $farmer['id']; ?>)" class="btn" style="background: #f44336; color: white; padding: 5px 10px; font-size: 0.85em; border: none; cursor: pointer; border-radius: 3px;">Reject</button>
                            <?php elseif ($status == 'rejected' && !empty($farmer['rejection_reason'])): ?>
                                <small style="color: #666;" title="<?php echo htmlspecialchars($farmer['rejection_reason']); ?>">Reason: <?php echo htmlspecialchars(substr($farmer['rejection_reason'], 0, 50)) . '...'; ?></small>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td><?php echo date('M d, Y', strtotime($farmer['created_at'])); ?></td>
                        <td>
                            <a href="?delete_user=<?php echo $farmer['id']; ?>&type=farmer&user_type=<?php echo urlencode($user_type_filter); ?>" 
                               class="btn btn-danger" 
                               style="padding: 6px 12px; font-size: 0.85em; text-decoration: none; border-radius: 6px;"
                               onclick="return confirm('Are you sure you want to delete this seller? This will also delete all their products!')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo $hasVerification ? '12' : '7'; ?>" style="text-align: center;">No farmers found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%;">
        <h3>Reject Seller Application</h3>
        <form method="POST" action="">
            <input type="hidden" name="farmer_id" id="reject_farmer_id">
            <input type="hidden" name="action" value="reject">
            <div class="form-group" style="margin: 20px 0;">
                <label>Rejection Reason (Optional but recommended):</label>
                <textarea name="rejection_reason" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="Explain why the seller application was rejected..."></textarea>
            </div>
            <div style="text-align: right;">
                <button type="button" onclick="hideRejectModal()" class="btn" style="background: #999; margin-right: 10px;">Cancel</button>
                <button type="submit" class="btn" style="background: #f44336; color: white;">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(farmerId) {
    document.getElementById('reject_farmer_id').value = farmerId;
    document.getElementById('rejectModal').style.display = 'flex';
}

function hideRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close modal on background click
document.addEventListener('DOMContentLoaded', function() {
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideRejectModal();
            }
        });
    }
});
</script>

<?php if ($user_type_filter === 'all' || $user_type_filter === 'buyers'): ?>
<div class="table-container" style="margin-bottom: 20px;">
    <h2 style="margin-bottom: 15px; font-size: 1.3em;">🛒 Buyers</h2>
    <div style="margin-bottom: 12px; background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
        <form method="GET" action="" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($user_type_filter); ?>">
            <input type="text" name="search_buyer" value="<?php echo htmlspecialchars($search_buyer); ?>" 
                   placeholder="Search..." 
                   style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; width: 180px; font-size: 0.9em;">
            <select name="sort_buyer" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 130px;">
                <option value="created_at" <?php echo $sort_buyer === 'created_at' ? 'selected' : ''; ?>>Date</option>
                <option value="name" <?php echo $sort_buyer === 'name' ? 'selected' : ''; ?>>Name</option>
                <option value="email" <?php echo $sort_buyer === 'email' ? 'selected' : ''; ?>>Email</option>
            </select>
            <select name="order_buyer" style="padding: 6px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9em; width: 110px;">
                <option value="DESC" <?php echo $order_buyer === 'DESC' ? 'selected' : ''; ?>>Desc</option>
                <option value="ASC" <?php echo $order_buyer === 'ASC' ? 'selected' : ''; ?>>Asc</option>
            </select>
            <button type="submit" style="background: #2196F3 !important; color: white !important; padding: 6px 12px !important; font-size: 0.85em !important; white-space: nowrap !important; border: none !important; border-radius: 5px !important; cursor: pointer !important; min-width: auto !important; width: auto !important; max-width: none !important;">Search</button>
            <?php if (!empty($search_buyer)): ?>
                <a href="manage_users.php?user_type=<?php echo urlencode($user_type_filter); ?>" style="background: #999 !important; color: white !important; padding: 6px 12px !important; font-size: 0.85em !important; text-decoration: none !important; white-space: nowrap !important; border-radius: 5px !important; display: inline-block !important; min-width: auto !important; width: auto !important; max-width: none !important;">✕</a>
            <?php endif; ?>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Joined</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($buyers->num_rows > 0): ?>
                <?php while ($buyer = $buyers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $buyer['id']; ?></td>
                        <td><?php echo htmlspecialchars($buyer['name']); ?></td>
                        <td><?php echo htmlspecialchars($buyer['email']); ?></td>
                        <td><?php echo htmlspecialchars($buyer['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($buyer['address'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($buyer['created_at'])); ?></td>
                        <td>
                            <a href="?delete_user=<?php echo $buyer['id']; ?>&type=buyer&user_type=<?php echo urlencode($user_type_filter); ?>" 
                               class="btn btn-danger" 
                               style="padding: 6px 12px; font-size: 0.85em; text-decoration: none; border-radius: 6px;"
                               onclick="return confirm('Are you sure you want to delete this buyer? This will also delete their cart items!')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No buyers found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include 'includes/admin_footer.php'; ?>

