<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `products` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: viewproduct.php");
    exit;
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products</title>
    <link rel="stylesheet" href="assets/css/view_products.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <nav>
        <ul>
            <li><a href="../shop.php"
                    style="background-color: #27ae60; color: white; margin-bottom: 20px; font-weight: bold;">← Back to
                    Shop</a></li>
            <li><a href="AdminPanel.php">Admin Panel</a></li>
            <li><a href="useradmin.php">Users</a></li>
            <li><a href="addproduct.php">Add Products</a></li>
            <li><a href="#" class="active">View Products</a></li>
            <li><a href="Inventory.php">Inventory Management</a></li>
            <li><a href="order_management.php">Order Management</a></li>
            <li><a href="reviews_management.php">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>
    <main>
        <h1> Product List</h1>

        <!-- Search Form -->
        <form action="" method="GET" style="display: flex; gap: 12px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px; position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by ID or Name..." 
                    style="width: 100%; padding: 12px 15px 12px 45px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; outline: none;">
            </div>
            <button type="submit" style="padding: 12px 25px; background: #3498db; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;">Search</button>
            <?php if ($search !== ''): ?>
                <a href="viewproduct.php" style="padding: 12px 20px; background: #f1f5f9; color: #64748b; border-radius: 10px; text-decoration: none; font-weight: 600;">Reset</a>
            <?php endif; ?>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Image</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($value = $run_select->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $value['id']; ?></td>
                        <td><?php echo htmlspecialchars($value['name']); ?></td>
                        <td><?php echo CURRENCY_SYMBOL; ?><?php echo number_format($value['price'], 2); ?></td>
                        <td><?php echo $value['stock']; ?></td>
                        <td><img style="width:40px; height:40px; object-fit: cover;"
                                src="../<?php echo $value['image_path'] ?: 'assets/images/placeholder-300x300.png'; ?>"
                                alt="Product Image"></td>
                        <td><?php echo htmlspecialchars(substr($value['description'], 0, 50)) . '...'; ?></td>
                        <td><?php echo ucfirst($value['type']); ?></td>
                        <td>
                            <a href="addproduct.php?edit=<?php echo $value['id']; ?>" class="edit-link">Edit</a> |
                            <a href="?delete=<?php echo $value['id']; ?>" style="color: #dc3545;"
                                onclick="return confirm('Are you sure you want to delete this product?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</body>

</html>