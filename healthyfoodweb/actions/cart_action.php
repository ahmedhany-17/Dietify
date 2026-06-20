<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn()) {
    // Redirect to root login.php (must go up one level from 'actions/' folder)
    header('Location: ../login.php');
    exit();
}

//i will check this later 
//
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    // Avoid undefined array key warnings for remove action where quantity might not be passed
    $productId = $_POST['product_id'] ?? null;
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    $action = $_POST['action'] ?? 'add';

    if (!$productId) {
        header('Location: ../cart.php');
        exit();
    }

    // Validation: Ensure quantity is at least 1
    if ($quantity < 1) {
        $quantity = 1;
    }

    // Ensure user has a cart
    $cartStmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
    $cartStmt->bind_param("i", $userId);
    $cartStmt->execute();
    $cart = $cartStmt->get_result()->fetch_assoc();

    if (!$cart) {
        $conn->query("INSERT INTO carts (user_id) VALUES ($userId)");
        $cartId = $conn->insert_id;
    } else {
        $cartId = $cart['id'];
    }

    if ($action === 'add') {
        // Check if item already in cart
        $itemStmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $itemStmt->bind_param("ii", $cartId, $productId);
        $itemStmt->execute();
        $item = $itemStmt->get_result()->fetch_assoc();

        if ($item) {
            $newQuantity = $item['quantity'] + $quantity;
            $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $newQuantity, $item['id']);
            $updateStmt->execute();
        } else {
            $insertStmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $insertStmt->bind_param("iii", $cartId, $productId, $quantity);
            $insertStmt->execute();
        }
    } elseif ($action === 'remove') {
        $removeStmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $removeStmt->bind_param("ii", $cartId, $productId);
        $removeStmt->execute();
    } elseif ($action === 'update') {
        $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
        $updateStmt->bind_param("iii", $quantity, $cartId, $productId);
        $updateStmt->execute();
    }

    // Redirect to cart in root folder
    header('Location: ../cart.php');
}
?>