<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn()) {
    // Redirect to root login.php (must go up one level from 'actions/' folder)
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    // Security check: Verify user is actually eligible to review (has purchased more than they've reviewed)
    // 1. Total bought
    $purchase_stmt = $conn->prepare("
        SELECT SUM(oi.quantity) as total_bought
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'paid'
    ");
    $purchase_stmt->bind_param("ii", $user_id, $product_id);
    $purchase_stmt->execute();
    $purchase_data = $purchase_stmt->get_result()->fetch_assoc();
    $total_bought = $purchase_data['total_bought'] ?? 0;

    // 2. Total reviewed
    $written_stmt = $conn->prepare("SELECT COUNT(*) as written_count FROM reviews WHERE user_id = ? AND product_id = ?");
    $written_stmt->bind_param("ii", $user_id, $product_id);
    $written_stmt->execute();
    $written_data = $written_stmt->get_result()->fetch_assoc();
    $written_count = $written_data['written_count'] ?? 0;

    // Only insert if they bought more than they reviewed
    if ($total_bought > $written_count) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
        $stmt->execute();
    }

    header("Location: /app/product.php?id=$product_id");
}
?>