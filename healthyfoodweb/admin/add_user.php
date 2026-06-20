<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/send_otp_email.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers, and underscores (no @ allowed).";
    } else {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $verifyImmediately = isset($_POST['verify_immediately']);
            $isVerified = $verifyImmediately ? 1 : 0;

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, is_verified) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $username, $email, $hashedPass, $role, $isVerified);
            
            if ($stmt->execute()) {
                if (!$verifyImmediately) {
                    // Generate verification token
                    $token = bin2hex(random_bytes(32));
                    
                    // Store in Redis
                    $redis = new Predis\Client();
                    $redis->setex("email_verification:" . strtolower($email), 3600, $token);
                    
                    // Send verification email
                    try {
                        sendVerificationEmail($email, $username, $token);
                        header("Location: useradmin.php?msg=added_verify");
                    } catch (Exception $e) {
                        header("Location: useradmin.php?msg=added_no_mail");
                    }
                } else {
                    header("Location: useradmin.php?msg=added");
                }
                exit();
            } else {
                $error = "Error creating user: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add New User - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/useradmin.css">
    <style>
        .add-container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #475569;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn-save {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        .btn-save:hover {
            background-color: #219150;
        }
        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="../shop.php" style="background-color: #27ae60; color: white; margin-bottom: 20px; font-weight: bold;">← Back to Shop</a></li>
            <li><a href="AdminPanel.php">Admin Panel</a></li>
            <li><a href="useradmin.php" class="active">Users</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="Inventory.php">Inventory Management</a></li>
            <li><a href="order_management.php">Order Management</a></li>
            <li><a href="reviews_management.php">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <h1 class="page-title">➕ Add New User Account</h1>
        
        <div class="add-container">
            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="e.g. john_doe">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" minlength="8" required placeholder="At least 8 characters">
                </div>
                <div class="form-group">
                    <label>Account Role</label>
                    <select name="role">
                        <option value="customer">Customer (Standard User)</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="form-group" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <label class="checkbox-container" style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 500;">
                        <input type="checkbox" name="verify_immediately" checked style="width: auto;">
                        Verify account immediately?
                    </label>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 8px; line-height: 1.4;">
                        <i class="fas fa-info-circle"></i> If unchecked, the user will receive a verification link via email and must verify their account before they can log in.
                    </p>
                </div>
                <button type="submit" class="btn-save">Create User Account</button>
                <a href="useradmin.php" class="btn-cancel">Cancel and Go Back</a>
            </form>
        </div>
    </div>
</body>
</html>
