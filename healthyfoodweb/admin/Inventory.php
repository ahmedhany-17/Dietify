<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $id = intval($_POST['product_id']);
    $new_quantity = max(0, intval($_POST['new_quantity'])); // avoid negative quantity
    $update = "UPDATE `products` SET `stock` = ? WHERE `id` = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("ii", $new_quantity, $id);
    $stmt->execute();
    header("Location: Inventory.php");
    exit;
}

// Fetch products with filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : '';

$select = "SELECT id, name, price, image_path, description, type, stock FROM `products` WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    if (is_numeric($search)) {
        $searchInt = intval($search);
        $select .= " AND (id = ? OR name LIKE ?)";
        $params[] = $searchInt;
        $likeSearch = "%$search%";
        $params[] = $likeSearch;
        $types .= "is";
    } else {
        $select .= " AND name LIKE ?";
        $likeSearch = "%$search%";
        $params[] = $likeSearch;
        $types .= "s";
    }
}

if ($search_type) {
    $select .= " AND type = ?";
    $params[] = $search_type;
    $types .= "s";
}

$select .= " ORDER BY id DESC";

$stmt = $conn->prepare($select);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$run_select = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="assets/css/inventory.css">
    <style>
        input[type="number"] {
            width: 60px;
            padding: 4px;
        }

        .update-btn {
            padding: 4px 8px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .update-btn:hover {
            background-color: #45a049;
        }

        .search-form {
            background: white;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
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
            <li><a href="#" class="active">Inventory Management</a></li>
            <li><a href="order_management.php">Order Management</a></li>
            <li><a href="reviews_management.php">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>
    <main>
        <h1> Inventory Management</h1>

        <!-- Search Form -->
        <form action="" method="GET" class="search-form">
            <div class="form-group" style="flex: 1; min-width: 250px;">
                <label for="search">Product Search</label>
                <input type="text" name="search" id="search"
                    value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by ID or Name...">
            </div>
            <div class="form-group">
                <label for="type">Category</label>
                <select name="type" id="type">
                    <option value="">All Categories</option>
                    <option value="food" <?php echo $search_type == 'food' ? 'selected' : ''; ?>>Food</option>
                    <option value="drink" <?php echo $search_type == 'drink' ? 'selected' : ''; ?>>Drink</option>
                </select>
            </div>
            <button type="submit" class="btn-search">🔍 Search</button>
            <a href="Inventory.php" class="btn-reset">↺ Reset</a>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Edit Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $run_select->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo $product['price']; ?></td>
                        <td><img src="<?php $imgUrl = getImageUrl($product['image_path']); echo (str_starts_with($imgUrl, 'http') ? $imgUrl : '../' . ($imgUrl ?: 'assets/images/placeholder-300x300.png')); ?>" width="40" height="40" style="object-fit: cover; border-radius: 4px;">
                        </td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td><?php echo ucfirst($product['type']); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="new_quantity" value="<?php echo $product['stock']; ?>" min="0">
                                <button type="submit" name="update_quantity" class="update-btn">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</body>

</html>