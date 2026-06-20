<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Healthy Food</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
    <nav>
        <ul>
            <li><a href="../shop.php"
                    style="background-color: #27ae60; color: white; margin-bottom: 20px; font-weight: bold;">← Back to
                    Shop</a></li>
            <li><a href="AdminPanel.php" class="active">Admin Panel</a></li>
            <li><a href="useradmin.php">Users</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="Inventory.php">Inventory Management</a></li>
            <li><a href="order_management.php">Order Management</a></li>
            <li><a href="reviews_management.php">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>

    <main>
        <h1>📊 Admin Panel</h1>

        <div class="dashboard-stats">
            <?php
            $stats_query = "SELECT
            (SELECT COUNT(*) FROM users) as user_count,
            (SELECT COUNT(*) FROM products) as product_count,
            (SELECT COUNT(*) FROM orders) as order_count,
            (SELECT COUNT(*) FROM reviews) as review_count";
            $stats = $conn->query($stats_query)->fetch_assoc();
            ?>
            <div class="stat-card">
                <h3>Users</h3>
                <p class="stat-value"><?php echo $stats['user_count']; ?></p>
                <a href="useradmin.php">Manage Users</a>
            </div>
            <div class="stat-card">
                <h3>Products</h3>
                <p class="stat-value"><?php echo $stats['product_count']; ?></p>
                <a href="viewproduct.php">Manage Products</a>
            </div>
            <div class="stat-card">
                <h3>Orders</h3>
                <p class="stat-value"><?php echo $stats['order_count']; ?></p>
                <a href="order_management.php">Manage Orders</a>
            </div>
            <div class="stat-card">
                <h3>Reviews</h3>
                <p class="stat-value"><?php echo $stats['review_count']; ?></p>
                <a href="reviews_management.php">View Reviews</a>
            </div>
        </div>

        <div class="section">
            <h2>👥 Users</h2>
            <?php
            $user_sql = "SELECT username, email, created_at FROM users ORDER BY created_at DESC LIMIT 10";
            $user_result = $conn->query($user_sql);

            if ($user_result && $user_result->num_rows > 0) {
                echo "<table>";
                echo "<tr><th>Username</th><th>Email</th><th>Created At</th></tr>";
                while ($user = $user_result->fetch_assoc()) {
                    echo "<tr>
                      <td>" . htmlspecialchars($user['username']) . "</td>
                      <td>" . htmlspecialchars($user['email']) . "</td>
                      <td>" . date('Y-m-d', strtotime($user['created_at'])) . "</td>
                    </tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No users found.</p>";
            }
            ?>
        </div>

        <div class="section">
            <h2>📦 Recent Orders</h2>
            <?php
            $order_sql = "SELECT 
                          u.username, 
                          u.email, 
                          o.location_description, 
                          o.total_amount, 
                          o.created_at 
                        FROM orders o
                        JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC LIMIT 10";

            $order_result = $conn->query($order_sql);

            if ($order_result && $order_result->num_rows > 0) {
                echo "<table>";
                echo "<tr><th>Customer</th><th>Email</th><th>Shipping Address</th><th>Total</th><th>Order Date</th></tr>";
                while ($order = $order_result->fetch_assoc()) {
                    echo "<tr>
                      <td>" . htmlspecialchars($order['username']) . "</td>
                      <td>" . htmlspecialchars($order['email']) . "</td>
                      <td>" . htmlspecialchars($order['location_description']) . "</td>
                      <td>" . number_format($order['total_amount'], 2) . "</td>
                      <td>" . date('Y-m-d', strtotime($order['created_at'])) . "</td>
                    </tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No orders found.</p>";
            }
            ?>
        </div>
    </main>

</body>

</html>