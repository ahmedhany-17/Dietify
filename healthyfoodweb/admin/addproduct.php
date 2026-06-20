<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

// Define default values
$name = '';
$price = '';
$description = '';
$type = 'food';
$image = '';
$stock = 0;

// Handle "Edit" GET action
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("SELECT * FROM `products` WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $array = $result->fetch_assoc();
        $name = $array['name'];
        $price = $array['price'];
        $description = $array['description'];
        $image = $array['image_path'];
        $type = $array['type'];
        $stock = $array['stock'];
    }
}

// Handle "Add" form submission
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'] ?? '';
    $type = !empty($_POST['type']) ? $_POST['type'] : 'food';
    $stock = intval($_POST['stock'] ?? 0);

    $imagePath = '';
    if (!empty($_FILES['image']['name'])) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $magicNumbers = [
            "\xFF\xD8\xFF" => 'jpg',
            "\x89\x50\x4E\x47" => 'png',
            "RIFF" => 'webp'
        ];

        $fileTitle = $_FILES['image']['name'];
        $fileExt = strtolower(pathinfo($fileTitle, PATHINFO_EXTENSION));

        // 1. Check extension
        if (!in_array($fileExt, $allowedExts)) {
            echo "<script>alert('Error: Invalid file extension. Only JPG, PNG, and WebP are allowed.'); window.history.back();</script>";
            exit;
        }

        // 2. Verify magic numbers (file headers)
        $handle = fopen($_FILES['image']['tmp_name'], 'rb');
        $fileHeader = fread($handle, 4);
        fclose($handle);

        $isValidMagic = false;
        foreach ($magicNumbers as $magic => $type) {
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
            }
        } else {
            echo "<script>alert('Error: File header does not match a valid image type. Upload rejected.'); window.history.back();</script>";
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO `products` (`name`, `price`, `description`, `image_path`, `stock`, `type`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssis", $name, $price, $description, $imagePath, $stock, $type);

    if ($stmt->execute()) {
        echo "<script>alert('Product added!'); window.location='viewproduct.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Handle "Update" form submission
if (isset($_POST['update']) && isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $stock = intval($_POST['stock'] ?? 0);

    $imagePath = $image; // Default to old image
    if (!empty($_FILES['image']['name'])) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $magicNumbers = [
            "\xFF\xD8\xFF" => 'jpg',
            "\x89\x50\x4E\x47" => 'png',
            "RIFF" => 'webp'
        ];

        $fileTitle = $_FILES['image']['name'];
        $fileExt = strtolower(pathinfo($fileTitle, PATHINFO_EXTENSION));

        // 1. Check extension
        if (!in_array($fileExt, $allowedExts)) {
            echo "<script>alert('Error: Invalid file extension. Only JPG, PNG, and WebP are allowed.'); window.history.back();</script>";
            exit;
        }

        // 2. Verify magic numbers
        $handle = fopen($_FILES['image']['tmp_name'], 'rb');
        $fileHeader = fread($handle, 4);
        fclose($handle);

        $isValidMagic = false;
        foreach ($magicNumbers as $magic => $type) {
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
            }
        } else {
            echo "<script>alert('Error: File header does not match a valid image type. Upload rejected.'); window.history.back();</script>";
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE `products` SET `name` = ?, `price` = ?, `description` = ?, `image_path` = ?, `stock` = ?, `type` = ? WHERE `id` = ?");
    $stmt->bind_param("sdssisi", $name, $price, $description, $imagePath, $stock, $type, $id);

    if ($stmt->execute()) {
        header("Location: viewproduct.php");
        exit;
    } else {
        echo "Error updating: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['edit']) ? 'Edit' : 'Add'; ?> Product</title>
    <link rel="stylesheet" href="assets/css/addproduct.css">
</head>

<body>
    <nav>
        <ul>
            <li><a href="../shop.php"
                    style="background-color: #27ae60; color: white; margin-bottom: 20px; font-weight: bold;">← Back to
                    Shop</a></li>
            <li><a href="AdminPanel.php">Admin Panel</a></li>
            <li><a href="useradmin.php">Users</a></li>
            <li><a href="addproduct.php" class="<?php echo !isset($_GET['edit']) ? 'active' : ''; ?>">Add Products</a>
            </li>
            <li><a href="viewproduct.php">View Products</a></li>
            <li><a href="Inventory.php">Inventory Management</a></li>
            <li><a href="order_management.php">Order Management</a></li>
            <li><a href="reviews_management.php">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>
    <main>
        <form method="POST" enctype="multipart/form-data">
            <h2><?php echo isset($_GET['edit']) ? 'Edit' : 'Add New'; ?> Product</h2>

            <label for="name">Product Name</label>
            <input value="<?php echo htmlspecialchars($name); ?>" id="name" type="text" name="name" required><br><br>

            <label for="price">Product Price ($)</label>
            <input value="<?php echo $price; ?>" id="price" type="number" step="0.01" name="price" required><br><br>

            <label for="stock">Stock Quantity</label>
            <input value="<?php echo $stock; ?>" id="stock" type="number" name="stock" required><br><br>

            <label for="description">Product Description</label>
            <textarea id="description" name="description"
                rows="4"><?php echo htmlspecialchars($description); ?></textarea><br><br>

            <label for="type">Product Type</label>
            <select id="type" name="type">
                <option value="food" <?php echo $type === 'food' ? 'selected' : ''; ?>>Food</option>
                <option value="drink" <?php echo $type === 'drink' ? 'selected' : ''; ?>>Drink</option>
            </select><br><br>

            <label for="image">Product Image</label>
            <?php if (!empty($image)): ?>
                <div style="margin-bottom: 10px;">
                    <img src="../<?php echo $image; ?>" alt="Current Image" width="100">
                </div>
            <?php endif; ?>
            <input id="image" type="file" name="image"><br><br>

            <div class="f-btn">
                <?php if (isset($_GET['edit'])): ?>
                    <button type="submit" name="update">Update Product</button>
                    <a href="viewproduct.php" style="padding: 10px; color: #666;">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add">Add Product</button>
                <?php endif; ?>
            </div>
        </form>
    </main>
</body>

</html>