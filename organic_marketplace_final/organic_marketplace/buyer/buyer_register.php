<?php
session_start();
require_once '../database/db_connect.php';

$error = '';
$success = '';

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
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $age_group = sanitize_input($_POST['age_group'] ?? '');
    $barangay = sanitize_input($_POST['barangay'] ?? '');
    
    $name = $first_name . ' ' . $last_name; // Combine for database
    
    $firstNameError = validate_name_field($first_name, 'First name');
    $lastNameError = validate_name_field($last_name, 'Last name');
    
    if ($firstNameError) {
        $error = $firstNameError;
    } elseif ($lastNameError) {
        $error = $lastNameError;
    } elseif (empty($address)) {
        $error = 'Address is required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM buyers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Check if age_group and barangay columns exist
            $colCheck = $conn->query("SHOW COLUMNS FROM buyers LIKE 'age_group'");
            if ($colCheck->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO buyers (name, email, password, phone, address, age_group, barangay) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $name, $email, $hashed_password, $phone, $address, $age_group, $barangay);
            } else {
                $stmt = $conn->prepare("INSERT INTO buyers (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $address);
            }
            
            if ($stmt->execute()) {
                // Redirect to login page after successful registration
                header('Location: buyer_login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
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
    <title>Buyer Registration - Organic Marketplace</title>
    <link rel="stylesheet" href="css/register_style.css">
</head>
<body>
    <div class="register-container">
        <h2>Buyer Registration</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="" class="form-grid">
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
                <label>Address</label>
                <textarea name="address" rows="3" placeholder="Street, House No." required></textarea>
            </div>
            <div class="form-group">
                <label>Barangay (Optional - for analytics)</label>
                <input type="text" name="barangay" placeholder="e.g., Poblacion 1, La Union">
            </div>
            <div class="form-group">
                <label>Age Group (Optional - for analytics)</label>
                <select name="age_group">
                    <option value="">Prefer not to say</option>
                    <option value="18-25">18-25</option>
                    <option value="26-35">26-35</option>
                    <option value="36-45">36-45</option>
                    <option value="46-55">46-55</option>
                    <option value="56+">56+</option>
                </select>
            </div>
            <button type="submit" class="btn">Register</button>
            <div class="links">
                <a href="buyer_login.php">Already have an account? Login</a> | 
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