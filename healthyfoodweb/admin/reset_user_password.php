<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

// Fetch current user data to verify existence and show name
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: useradmin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['new_password'];

    if (strlen($newPass) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $hashedPass = password_hash($newPass, PASSWORD_BCRYPT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hashedPass, $userId);
        if ($upd->execute()) {
            header("Location: useradmin.php?msg=reset&user=" . urlencode($user['username']));
            exit();
        } else {
            $error = "Error updating password: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reset Password - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/useradmin.css">
    <style>
        .reset-container {
            max-width: 500px;
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
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn-save {
            background-color: #f39c12;
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
            background-color: #e67e22;
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
        <h1 class="page-title">🔑 Reset Password for: <?php echo htmlspecialchars($user['username']); ?></h1>
        
        <div class="reset-container">
            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <p style="color: #666; margin-bottom: 20px; font-size: 0.9rem;">Please enter the new password below. You will need to provide this password to the user manually.</p>

            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" minlength="8" required autofocus>
                </div>
                <button type="submit" class="btn-save">Update Password</button>
                <a href="useradmin.php" class="btn-cancel">Cancel and Go Back</a>
            </form>
        </div>
    </div>
</body>
</html>
