<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int) $_POST['order_id'];
    $newStatus = $_POST['status'];
    $allowedStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'];

    if (in_array($newStatus, $allowedStatuses)) {
        $conn->begin_transaction();
        try {
            // 1. Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $orderId);
            $stmt->execute();

            // 2. If status is cancelled or refunded, trigger Stripe refund
            if ($newStatus === 'cancelled' || $newStatus === 'refunded') {
                $stmt_pay = $conn->prepare("SELECT transaction_id, method, status FROM payments WHERE order_id = ? AND status = 'completed'");
                $stmt_pay->bind_param("i", $orderId);
                $stmt_pay->execute();
                $payment = $stmt_pay->get_result()->fetch_assoc();

                if ($payment && $payment['method'] === 'stripe' && !empty($payment['transaction_id'])) {
                    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
                    \Stripe\Refund::create([
                        'payment_intent' => $payment['transaction_id'],
                    ]);

                    // Update payment status in our DB
                    $conn->query("UPDATE payments SET status = 'refunded' WHERE order_id = $orderId");
                }
            }

            $conn->commit();
            $msg = "Order #$orderId updated to " . ucfirst($newStatus) . " and processed successfully.";
            $msgType = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error: " . $e->getMessage();
            $msgType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Management - Healthy Food</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .status-paid {
            color: #27ae60;
            font-weight: bold;
        }

        .status-pending {
            color: #e67e22;
            font-weight: bold;
        }

        .table-container {
            margin-top: 20px;
            overflow-x: auto;
        }

        .search-form {
            background: white;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-size: 0.85rem;
            color: #666;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn-search {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-search:hover {
            background-color: #2980b9;
        }

        .btn-reset {
            background-color: #95a5a6;
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-reset:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>

<body>
    <nav>
        <ul>
            <li><a href="../shop.php"
                    style="background-color: #27ae60; color: white; margin-bottom: 20px; font-weight: bold;">← Back to
                    Shop</a></li>
            <li><a href="AdminPanel.php">Admin Panel</a></li>
            <li><a href="useradmin.php">Users</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="Inventory.php">Inventory Management</a></li>
            <li><a href="#" class="active">Order Management</a></li>
            <li><a href="reviews_management.php">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>
    <main>
        <h1> Order Management</h1>

        <?php if (isset($msg)): ?>
            <div
                style="padding: 15px; margin-bottom: 20px; border-radius: 4px; background: <?php echo $msgType === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $msgType === 'success' ? '#155724' : '#721c24'; ?>;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php
        $search_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : '';
        $search_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : '';
        $search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
        $search_status = isset($_GET['status']) ? $_GET['status'] : '';
        $search_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
        $search_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

        // Build SQL with filters
        $sql = "SELECT 
                    o.id,
                    u.id as user_id,
                    u.username,
                    u.email,
                    o.location_description,
                    o.total_amount,
                    o.status,
                    o.created_at
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE 1=1";

        $params = [];
        $types = "";

        if ($search_id) {
            $sql .= " AND o.id = ?";
            $params[] = $search_id;
            $types .= "i";
        }

        if ($search_customer_id) {
            $sql .= " AND u.id = ?";
            $params[] = $search_customer_id;
            $types .= "i";
        }

        if ($search_query) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $search_like = "%$search_query%";
            $params[] = $search_like;
            $params[] = $search_like;
            $types .= "ss";
        }

        if ($search_status) {
            $sql .= " AND o.status = ?";
            $params[] = $search_status;
            $types .= "s";
        }

        if ($search_start_date) {
            $sql .= " AND DATE(o.created_at) >= ?";
            $params[] = $search_start_date;
            $types .= "s";
        }

        if ($search_end_date) {
            $sql .= " AND DATE(o.created_at) <= ?";
            $params[] = $search_end_date;
            $types .= "s";
        }

        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <!-- Search Form -->
        <form action="" method="GET" class="search-form">
            <div class="form-group">
                <label for="order_id">Order ID</label>
                <input type="number" name="order_id" id="order_id" value="<?php echo htmlspecialchars($search_id); ?>"
                    placeholder="e.g. 123">
            </div>
            <div class="form-group">
                <label for="customer_id">Customer ID</label>
                <input type="number" name="customer_id" id="customer_id"
                    value="<?php echo htmlspecialchars($search_customer_id); ?>" placeholder="e.g. 45">
            </div>
            <div class="form-group">
                <label for="query">Customer (Name/Email)</label>
                <input type="text" name="query" id="query" value="<?php echo htmlspecialchars($search_query); ?>"
                    placeholder="Search name or email...">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $search_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo $search_status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="shipped" <?php echo $search_status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $search_status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $search_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="refunded" <?php echo $search_status == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">From Date</label>
                <input type="text" name="start_date" id="start_date" class="datepicker"
                    value="<?php echo htmlspecialchars($search_start_date); ?>" placeholder="YYYY-MM-DD">
            </div>
            <div class="form-group">
                <label for="end_date">To Date</label>
                <input type="text" name="end_date" id="end_date" class="datepicker"
                    value="<?php echo htmlspecialchars($search_end_date); ?>" placeholder="YYYY-MM-DD">
            </div>
            <button type="submit" class="btn-search">🔍 Search</button>
            <a href="order_management.php" class="btn-reset">↺ Reset</a>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class='table-container'>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Shipping Address</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $statusClass = ($row['status'] === 'paid' ? 'status-paid' : 'status-pending'); ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <br><small style="color: #888;">ID: #<?php echo $row['user_id']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['location_description']); ?></td>
                                <td><?php echo number_format($row['total_amount'], 2); ?> $</td>
                                <td>
                                    <form method="POST" style="margin:0; display:flex; gap:5px;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 4px; font-size: 0.8rem;">
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <option value="pending" selected>Pending</option>
                                                <option value="paid">Paid (Manual)</option>
                                                <option value="cancelled">Cancelled</option>
                                            <?php elseif ($row['status'] === 'paid'): ?>
                                                <option value="paid" selected>Paid</option>
                                                <option value="shipped">Shipped</option>
                                                <option value="delivered">Delivered</option>
                                                <option value="refunded">Refunded (Triggers Stripe Refund)</option>
                                            <?php elseif ($row['status'] === 'shipped'): ?>
                                                <option value="shipped" selected>Shipped</option>
                                                <option value="delivered">Delivered</option>
                                                <option value="refunded">Refunded (Triggers Stripe Refund)</option>
                                            <?php elseif ($row['status'] === 'delivered'): ?>
                                                <option value="delivered" selected>Delivered</option>
                                                <option value="refunded">Refunded (Triggers Stripe Refund)</option>
                                            <?php else: ?>
                                                <option value="<?php echo $row['status']; ?>" selected><?php echo ucfirst($row['status']); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div
                style="background: white; padding: 40px; text-align: center; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <p style="color: #666; font-size: 1.1rem;">No orders found matching your search criteria.</p>
                <a href="order_management.php" style="color: #3498db; text-decoration: none; font-weight: 600;">Show all
                    orders</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            allowInput: true,
            altInput: false, // Ensure the actual value is what the user sees
            onReady: function (selectedDates, dateStr, instance) {
                instance.element.placeholder = "YYYY-MM-DD";
            }
        });
    </script>
</body>

</html>