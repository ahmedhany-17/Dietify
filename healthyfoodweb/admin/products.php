<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

$message = '';
$error = '';

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $conn->begin_transaction();

        // 1. Delete dependent entries first (Cascade Simulation)
        $conn->query("DELETE FROM `cart_items` WHERE `product_id` = $id");
        $conn->query("DELETE FROM `order_items` WHERE `product_id` = $id");
        $conn->query("DELETE FROM `reviews` WHERE `product_id` = $id");

        // 2. Delete the actual product
        $stmt = $conn->prepare("DELETE FROM `products` WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $conn->commit();
            header("Location: products.php?msg=deleted");
            exit;
        } else {
            $conn->rollback();
            $error = "Error deleting product: " . $conn->error;
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Database Error: " . $e->getMessage();
    }
}

// Handle "Edit" data loading
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
$name = '';
$price = '';
$description = '';
$type = 'food';
$image = '';
$stock = 0;

if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM `products` WHERE `id` = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $name = $product['name'];
        $price = $product['price'];
        $description = $product['description'];
        $image = $product['image_path'];
        $type = $product['type'];
        $stock = $product['stock'];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add']) || isset($_POST['update'])) {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $description = $_POST['description'] ?? '';
        $type = !empty($_POST['type']) ? $_POST['type'] : 'food';
        $stock = intval($_POST['stock'] ?? 0);

        $imagePath = $image; // Use existing if updating and no new image
        if (!empty($_FILES['image']['name'])) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $magicNumbers = [
                "\xFF\xD8\xFF" => 'jpg',
                "\x89\x50\x4E\x47" => 'png',
                "RIFF" => 'webp'
            ];

            $fileTitle = $_FILES['image']['name'];
            $fileExt = strtolower(pathinfo($fileTitle, PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExts)) {
                $error = "Invalid file extension. Only JPG, PNG, and WebP are allowed.";
            } else {
                $handle = fopen($_FILES['image']['tmp_name'], 'rb');
                $fileHeader = fread($handle, 4);
                fclose($handle);

                $isValidMagic = false;
                foreach ($magicNumbers as $magic => $mtype) {
                    if (str_starts_with($fileHeader, $magic)) {
                        $isValidMagic = true;
                        break;
                    }
                }

                if ($isValidMagic) {
                    $imageName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
                    $target = "../assets/images/" . $imageName;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                        $imagePath = "assets/images/" . $imageName;
                    } else {
                        $error = "Failed to upload image.";
                    }
                } else {
                    $error = "File header does not match a valid image type.";
                }
            }
        }

        if (empty($error)) {
            if (isset($_POST['add'])) {
                $stmt = $conn->prepare("INSERT INTO `products` (`name`, `price`, `description`, `image_path`, `stock`, `type`) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sdssis", $name, $price, $description, $imagePath, $stock, $type);
                if ($stmt->execute()) {
                    header("Location: products.php?msg=added");
                    exit;
                } else {
                    $error = "Error adding product: " . $conn->error;
                }
            } else {
                $stmt = $conn->prepare("UPDATE `products` SET `name` = ?, `price` = ?, `description` = ?, `image_path` = ?, `stock` = ?, `type` = ? WHERE `id` = ?");
                $stmt->bind_param("sdssisi", $name, $price, $description, $imagePath, $stock, $type, $edit_id);
                if ($stmt->execute()) {
                    header("Location: products.php?msg=updated");
                    exit;
                } else {
                    $error = "Error updating product: " . $conn->error;
                }
            }
        }
    }
}

// Handle search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type_filter']) ? trim($_GET['type_filter']) : '';

$query = "SELECT id, name, price, image_path, description, type, stock FROM `products` WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    if (is_numeric($search)) {
        $query .= " AND (id = ? OR name LIKE ?)";
        $params[] = (int) $search;
        $likeSearch = "%$search%";
        $params[] = $likeSearch;
        $types .= "is";
    } else {
        $query .= " AND name LIKE ?";
        $likeSearch = "%$search%";
        $params[] = $likeSearch;
        $types .= "s";
    }
}

if ($type_filter !== '' && in_array($type_filter, ['food', 'drink'])) {
    $query .= " AND type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$run_select = $stmt->get_result();

$view = isset($_GET['form']) || isset($_GET['edit']) ? 'form' : 'list';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="assets/css/products.css">
</head>

<body>
    <nav>
        <ul>
            <li><a href="../shop.php"
                    style="background-color: #27ae60; color: white; margin-bottom: 20px; font-weight: bold;">← Back to
                    Shop</a></li>
            <li><a href="AdminPanel.php">Admin Panel</a></li>
            <li><a href="useradmin.php">Users</a></li>
            <li><a href="products.php" class="active">Products</a></li>
            <li><a href="Inventory.php">Inventory Management</a></li>
            <li><a href="order_management.php">Order Management</a></li>
            <li><a href="reviews_management.php">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>
    <main>
        <?php
        if (isset($_GET['msg'])) {
            $m = $_GET['msg'];
            if ($m == 'added')
                echo '<div class="alert alert-success">Product added successfully!</div>';
            if ($m == 'updated')
                echo '<div class="alert alert-success">Product updated successfully!</div>';
            if ($m == 'deleted')
                echo '<div class="alert alert-success">Product deleted successfully!</div>';
        }
        if ($error)
            echo '<div class="alert alert-error">' . $error . '</div>';
        ?>

        <div class="tabs">
            <a href="products.php" class="tab <?php echo $view === 'list' ? 'active' : ''; ?>">Product List</a>
            <a href="products.php?form" class="tab <?php echo $view === 'form' && !$edit_id ? 'active' : ''; ?>">Add
                Product</a>
            <?php if ($edit_id): ?>
                <a href="#" class="tab active">Edit Product</a>
            <?php endif; ?>
        </div>

        <?php if ($view === 'list'): ?>
            <div class="filter-header" style="margin-bottom: 30px;">
                <h2 style="margin-bottom: 15px;">Product Inventory</h2>
                <form action="products.php" method="GET" class="filter-form"
                    style="display: flex; gap: 12px; background: #f8f9fa; padding: 20px; border-radius: 12px; border: 1px solid #eef0f2; align-items: center; flex-wrap: wrap;">

                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" placeholder="Search by ID or Name..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            style="width: 100%; padding: 12px 15px; border: 1.5px solid #dee2e6; border-radius: 8px; font-size: 0.95rem; outline: none; transition: border-color 0.2s;">
                    </div>

                    <div style="min-width: 150px;">
                        <select name="type_filter"
                            style="width: 100%; padding: 12px 15px; border: 1.5px solid #dee2e6; border-radius: 8px; font-size: 0.95rem; background: white; cursor: pointer; outline: none;">
                            <option value="">All Categories</option>
                            <option value="food" <?php echo $type_filter === 'food' ? 'selected' : ''; ?>>🍎 Food</option>
                            <option value="drink" <?php echo $type_filter === 'drink' ? 'selected' : ''; ?>>🥤 Drink</option>
                        </select>
                    </div>

                    <button type="submit"
                        style="padding: 12px 25px; background: #27ae60; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; min-width: 100px;">Filter
                        Results</button>

                    <?php if ($search !== '' || $type_filter !== ''): ?>
                        <a href="products.php"
                            style="padding: 12px 20px; background: #adb5bd; color: white; border: none; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; transition: background 0.2s;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Image</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($value = $run_select->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <?php echo $value['id']; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($value['name']); ?>
                            </td>
                            <td>
                                <?php echo CURRENCY_SYMBOL; ?>
                                <?php echo number_format($value['price'], 2); ?>
                            </td>
                            <td>
                                <?php echo $value['stock']; ?>
                            </td>
                            <td><img style="width:40px; height:40px; object-fit: cover;"
                                    src="<?php $imgUrl = getImageUrl($value['image_path']);
                                    echo (str_starts_with($imgUrl, 'http') ? $imgUrl : '../' . ($imgUrl ?: 'assets/images/placeholder-300x300.png')); ?>" alt="Product">
                            </td>
                            <td>
                                <?php echo ucfirst($value['type']); ?>
                            </td>
                            <td>
                                <a href="products.php?edit=<?php echo $value['id']; ?>" class="edit-link">Edit</a> |
                                <a href="?delete=<?php echo $value['id']; ?>" style="color: #dc3545;"
                                    onclick="return confirm('Delete this product?');">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data" style="margin-top: 10px;">
                <h2 style="margin-bottom: 20px;">
                    <?php echo $edit_id ? '✏️ Edit' : '➕ Add New'; ?> Product
                </h2>

                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input value="<?php echo htmlspecialchars($name); ?>" id="name" type="text" name="name" required placeholder="Enter product name">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (<?php echo CURRENCY_CODE; ?>)</label>
                        <input value="<?php echo $price; ?>" id="price" type="number" step="0.01" name="price" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input value="<?php echo $stock; ?>" id="stock" type="number" name="stock" required placeholder="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Product Description</label>
                    <textarea id="description" name="description" placeholder="Write a short description..."
                        style="min-height: 60px;"><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Product Category</label>
                        <select id="type" name="type">
                            <option value="food" <?php echo $type === 'food' ? 'selected' : ''; ?>>🍎 Food</option>
                            <option value="drink" <?php echo $type === 'drink' ? 'selected' : ''; ?>>🥤 Drink</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input id="image" type="file" name="image">
                    </div>
                </div>

                <?php if (!empty($image)): ?>
                    <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; background: #f8f9fa; padding: 10px; border-radius: 8px;">
                        <img src="<?php $imgUrl = getImageUrl($image);
                        echo (str_starts_with($imgUrl, 'http') ? $imgUrl : '../' . ($imgUrl ?: 'assets/images/placeholder-300x300.png')); ?>"
                            alt="Current Image" width="60" height="60" style="object-fit: cover; border-radius: 4px;">
                        <span style="font-size: 0.85rem; color: #666;">Current image preview</span>
                    </div>
                <?php endif; ?>

                <div class="f-btn" style="display: flex; gap: 15px; align-items: center; margin-top: 10px;">
                    <?php if ($edit_id): ?>
                        <button type="submit" name="update" style="background: #27ae60;">Save Changes</button>
                        <a href="products.php" style="color: #666; text-decoration: none; font-weight: 600;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add" style="background: #27ae60;">Add Product</button>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </main>
</body>

</html>