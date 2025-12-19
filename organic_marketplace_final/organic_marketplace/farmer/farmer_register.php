<?php
session_start();
require_once '../database/db_connect.php';

$error = '';
$success = '';

$hasCertificateColumn = false;
$colCheckCertificate = $conn->query("SHOW COLUMNS FROM farmers LIKE 'certificate_path'");
if ($colCheckCertificate) {
    $hasCertificateColumn = $colCheckCertificate->num_rows > 0;
    $colCheckCertificate->free();
}
if (!$hasCertificateColumn) {
    $conn->query("ALTER TABLE farmers ADD COLUMN certificate_path VARCHAR(255) NULL AFTER phone");
    $hasCertificateColumn = true;
}

function validate_name_field($value, $label) {
    if (mb_strlen($value) < 2) {
        return "$label must be at least 2 characters long.";
    }
    if (!preg_match('/^[A-Z]/', $value)) {
        return "$label must start with a capital letter.";
    }
    if (preg_match('/\d/', $value)) {
        return "$label cannot contain numbers.";
    }
    if (!preg_match("/^[A-Z][A-Za-z\\s'-]*$/", $value)) {
        return "$label cannot contain special characters.";
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $location = sanitize_input($_POST['location']);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $certificate_path = null;
    $certificate_full_path = null;
    
    $name = $first_name . ' ' . $last_name; // Combine for database
    
    $firstNameError = validate_name_field($first_name, 'First name');
    $lastNameError = validate_name_field($last_name, 'Last name');
    
    if ($firstNameError) {
        $error = $firstNameError;
    } elseif ($lastNameError) {
        $error = $lastNameError;
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['certificate']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Failed to upload certificate. Please try again.';
            } else {
                $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
                $ext = strtolower(pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_ext)) {
                    $error = 'Certificate must be a PDF, JPG, or PNG file.';
                } elseif ($_FILES['certificate']['size'] > 5 * 1024 * 1024) {
                    $error = 'Certificate file must be 5MB or smaller.';
                } else {
                    $uploadDir = __DIR__ . '/../uploads/certificates/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }
                    $newName = uniqid('cert_', true) . '.' . $ext;
                    $targetPath = $uploadDir . $newName;
                    if (move_uploaded_file($_FILES['certificate']['tmp_name'], $targetPath)) {
                        $certificate_path = 'uploads/certificates/' . $newName;
                        $certificate_full_path = $targetPath;
                    } else {
                        $error = 'Unable to save uploaded certificate. Please try again.';
                    }
                }
            }
        }
    }

    if (!$error) {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM farmers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($hasCertificateColumn) {
                $stmt = $conn->prepare("INSERT INTO farmers (name, email, password, location, phone, certificate_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $name, $email, $hashed_password, $location, $phone, $certificate_path);
            } else {
                $stmt = $conn->prepare("INSERT INTO farmers (name, email, password, location, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $hashed_password, $location, $phone);
            }
            
            if ($stmt->execute()) {
                // Redirect to login page after successful registration
                header('Location: farmer_login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
                if ($certificate_full_path && file_exists($certificate_full_path)) {
                    unlink($certificate_full_path);
                }
            }
        }
        $stmt->close();
        if ($error && $certificate_full_path && file_exists($certificate_full_path)) {
            unlink($certificate_full_path);
        }
    } else {
        if ($certificate_full_path && file_exists($certificate_full_path)) {
            unlink($certificate_full_path);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Registration - Organic Marketplace</title>
    <link rel="stylesheet" href="css/register_style.css">
</head>
<body>
    <div class="register-container">
        <h2>🧑‍🌾 Farmer Registration</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="" class="form-grid" enctype="multipart/form-data">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" class="name-field" data-label="First name" data-error-id="firstNameError" required>
                <div class="input-error" id="firstNameError"></div>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" class="name-field" data-label="Last name" data-error-id="lastNameError" required>
                <div class="input-error" id="lastNameError"></div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone (Optional)</label>
                <input type="text" name="phone">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="passwordField" name="password" required>
                <div class="password-strength" id="passwordStrength" data-strength="weak">
                    <span class="strength-dot"></span>
                    <span class="strength-text">Enter a password</span>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" id="confirmPasswordField" name="confirm_password" required>
                <div class="password-match" id="passwordMatch"></div>
            </div>
            <div class="form-group full-width">
                <label>Location</label>
                <input type="text" name="location" placeholder="e.g., Barangay, Municipality" required>
            </div>
            <div class="form-group full-width">
                <label>Business Permit / Certificate (PDF, JPG, PNG - max 5MB)</label>
                <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png">
                <div class="helper-note">Optional: upload your business permit or farmer certificate for faster verification.</div>
            </div>
            <button type="submit" class="btn">Register</button>
            <div class="links">
                <a href="farmer_login.php">Already have an account? Login</a> | 
                <a href="../index.php">Back to Home</a>
            </div>
        </form>
    </div>
    <script>
        (function() {
            const passwordField = document.getElementById('passwordField');
            const confirmField = document.getElementById('confirmPasswordField');
            const strengthWrap = document.getElementById('passwordStrength');
            const strengthText = strengthWrap ? strengthWrap.querySelector('.strength-text') : null;
            const matchWrap = document.getElementById('passwordMatch');

            if (!passwordField || !confirmField) {
                return;
            }

            function evaluateStrength(value) {
                let score = 0;
                if (value.length >= 8) score++;
                if (/[A-Z]/.test(value)) score++;
                if (/[0-9]/.test(value)) score++;
                if (/[^A-Za-z0-9]/.test(value)) score++;
                if (value.length >= 12) score++;

                if (score >= 4) return 'strong';
                if (score >= 2) return 'medium';
                return 'weak';
            }

            function updateStrength() {
                if (!strengthWrap || !strengthText) return;
                const value = passwordField.value.trim();
                if (!value) {
                    strengthWrap.dataset.strength = 'weak';
                    strengthText.textContent = 'Enter a password';
                    return;
                }
                const level = evaluateStrength(value);
                strengthWrap.dataset.strength = level;
                strengthText.textContent = level.charAt(0).toUpperCase() + level.slice(1) + ' password';
            }

            function updateMatchState() {
                if (!matchWrap) return;
                const pwd = passwordField.value;
                const confirm = confirmField.value;
                confirmField.classList.remove('matching', 'not-matching');
                matchWrap.classList.remove('matching');
                matchWrap.textContent = '';

                if (!confirm) return;

                if (pwd === confirm) {
                    confirmField.classList.add('matching');
                    matchWrap.classList.add('matching');
                    matchWrap.textContent = 'Passwords match';
                } else {
                    confirmField.classList.add('not-matching');
                    matchWrap.textContent = 'Passwords do not match';
                }
            }

            const nameInputs = document.querySelectorAll('.name-field');

            function validateName(input) {
                const label = input.dataset.label || 'Name';
                const value = input.value.trim();
                const errorEl = document.getElementById(input.dataset.errorId);
                let message = '';

                if (!value) {
                    message = '';
                } else if (/^[0-9]/.test(value)) {
                    message = `${label} cannot start with a number.`;
                } else if (/^[^A-Za-z]/.test(value)) {
                    message = `${label} cannot start with a special character.`;
                } else if (!/^[A-Z]/.test(value)) {
                    message = `${label} must start with a capital letter.`;
                } else if (value.length < 2) {
                    message = `${label} must be at least 2 characters.`;
                } else if (/[^A-Za-z\\s'-]/.test(value)) {
                    message = `${label} cannot contain numbers or special characters.`;
                }

                input.setCustomValidity(message);
                if (errorEl) {
                    errorEl.textContent = message;
                }
                if (message) {
                    input.classList.add('invalid');
                } else {
                    input.classList.remove('invalid');
                    if (errorEl) errorEl.textContent = '';
                }
            }

            if (passwordField) {
                passwordField.addEventListener('input', () => {
                    updateStrength();
                    updateMatchState();
                });
            }

            if (confirmField) {
                confirmField.addEventListener('input', updateMatchState);
            }

            nameInputs.forEach((input) => {
                input.addEventListener('input', () => validateName(input));
                input.addEventListener('blur', () => validateName(input));
            });
        })();
    </script>
</body>
</html>