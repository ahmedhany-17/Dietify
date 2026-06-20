<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `reviews` WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: reviews_management.php?msg=deleted");
    } else {
        header("Location: reviews_management.php?msg=error");
    }
    exit;
}

// Search and Filter Logic
$search_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
$search_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : '';
$search_rating_min = isset($_GET['rating_min']) ? intval($_GET['rating_min']) : '';
$search_rating_max = isset($_GET['rating_max']) ? intval($_GET['rating_max']) : '';

$sql = "SELECT r.id, r.rating, r.comment, r.created_at, r.user_id, r.product_id, u.username, p.name as product_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        JOIN products p ON r.product_id = p.id 
        WHERE 1=1";

$params = [];
$types = "";

if ($search_user_id) {
    $sql .= " AND r.user_id = ?";
    $params[] = $search_user_id;
    $types .= "i";
}

if ($search_product_id) {
    $sql .= " AND r.product_id = ?";
    $params[] = $search_product_id;
    $types .= "i";
}

if ($search_rating_min) {
    $sql .= " AND r.rating >= ?";
    $params[] = $search_rating_min;
    $types .= "i";
}

if ($search_rating_max) {
    $sql .= " AND r.rating <= ?";
    $params[] = $search_rating_max;
    $types .= "i";
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Review Management - Healthy Food</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .rating {
            color: #f1c40f;
            font-size: 1.1rem;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: background 0.2s;
        }

        .delete-btn:hover {
            background-color: #c0392b;
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

        .id-badge {
            background: #f0f2f5;
            color: #666;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-family: monospace;
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
            <li><a href="order_management.php">Order Management</a></li>
            <li><a href="reviews_management.php" class="active">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>
    <main>
        <h1> Product Reviews</h1>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <p style="color: #27ae60; background: #d4edda; padding: 10px; border-radius: 5px;">Review deleted successfully.</p>
        <?php endif; ?>

        <!-- Search Form -->
        <form action="" method="GET" class="search-form">
            <div class="form-group">
                <label for="user_id">User ID</label>
                <input type="number" name="user_id" id="user_id" value="<?php echo htmlspecialchars($search_user_id); ?>" placeholder="e.g. 45">
            </div>
            <div class="form-group">
                <label for="product_id">Product ID</label>
                <input type="number" name="product_id" id="product_id" value="<?php echo htmlspecialchars($search_product_id); ?>" placeholder="e.g. 1">
            </div>
            <div class="form-group">
                <label for="rating_min">Min Rating</label>
                <select name="rating_min" id="rating_min">
                    <option value="">Any</option>
                    <?php for($i=1; $i<=5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $search_rating_min == $i ? 'selected' : ''; ?>><?php echo $i; ?> Star<?php echo $i>1?'s':''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="rating_max">Max Rating</label>
                <select name="rating_max" id="rating_max">
                    <option value="">Any</option>
                    <?php for($i=1; $i<=5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $search_rating_max == $i ? 'selected' : ''; ?>><?php echo $i; ?> Star<?php echo $i>1?'s':''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn-search">🔍 Search</button>
            <a href="reviews_management.php" class="btn-reset">↺ Reset</a>
        </form>

        <?php if (count($reviews) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>#<?php echo $review['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                    <br><span class="id-badge">ID: #<?php echo $review['user_id']; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($review['product_name']); ?></strong>
                                </td>
                                <td class="rating">
                                    <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($review['comment']); ?></td>
                                <td><small><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></small></td>
                                <td>
                                    <a href="?delete=<?php echo $review['id']; ?>" class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No reviews found.</p>
        <?php endif; ?>
    </main>
</body>

</html>