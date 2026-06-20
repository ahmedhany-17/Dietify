<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/vendor/autoload.php';

// Enable strict error reporting for debugging
//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_GET['session_id'])) {
    header('Location: index1.php');
    exit();
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
$sessionId = $_GET['session_id'];
$isExplicitCancel = isset($_GET['cancelled']);

try {
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    // strip hold info about order id so I WILL BE USED TO UPDATE the order table from from "Pending" to "Paid."

    $orderId = $session->metadata->order_id ?? null;
    $userId = $_SESSION['user_id'] ?? $session->metadata->user_id ?? null;

    if (!$orderId || !$userId) {
        throw new Exception("Order or User information missing.");
    }

    if ($session->payment_status === 'paid' && !$isExplicitCancel) {
        $checkStmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
        $checkStmt->bind_param("i", $orderId);
        $checkStmt->execute();
        $order = $checkStmt->get_result()->fetch_assoc();

        if ($order && ($order['status'] === 'pending' || $order['status'] === 'cancelled')) {
            // 1. Update Order to 'paid'
            $orderStmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ? AND user_id = ?");
            $orderStmt->bind_param("ii", $orderId, $userId);
            $orderStmt->execute();

            // 2. Create Payment record (completed)
            $paymentStmt = $conn->prepare("INSERT IGNORE INTO payments (order_id, method, status, transaction_id, paid_at) VALUES (?, 'stripe', 'completed', ?, NOW())");
            $paymentStmt->bind_param("is", $orderId, $sessionId);
            $paymentStmt->execute();

            // 3. Clear Cart
            $conn->query("DELETE FROM cart_items WHERE cart_id = (SELECT id FROM carts WHERE user_id = $userId)");

            // 4. Update Stock
            $itemsResult = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $orderId");
            while ($item = $itemsResult->fetch_assoc()) {
                $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
            }
        }

        $success = true;
        $title = "Payment Successful!";
        $msg = "Thank you for your order. Your healthy food is being prepared!";
        $icon = "✅";
        $btnText = "Return Home";
        $btnLink = "index1.php";
    } else {
        // Handle Failure or Cancellation
        $status = $isExplicitCancel ? 'cancelled' : 'pending'; // keeps as pending or marks cancelled
        $payStatus = 'failed';

        $orderStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
        $orderStmt->bind_param("sii", $status, $orderId, $userId);
        $orderStmt->execute();

        $paymentStmt = $conn->prepare("INSERT INTO payments (order_id, method, status, transaction_id, created_at) VALUES (?, 'stripe', 'failed', ?, NOW())");
        $paymentStmt->bind_param("is", $orderId, $sessionId);
        $paymentStmt->execute();

        $success = false;
        $title = $isExplicitCancel ? "Payment Cancelled" : "Payment Failed";
        $msg = $isExplicitCancel ? "You have cancelled your payment. The items are still in your cart." : "Something went wrong with your payment. Please try again.";
        $icon = "❌";
        $btnText = "Back to Cart";
        $btnLink = "cart.php";
        if (!$isExplicitCancel) {
            $error = "Stripe Payment Status: " . $session->payment_status;
        }
    }
} catch (Exception $e) {
    $success = false;
    $title = "System Error";
    $msg = "We encountered an error processing your request.";
    $icon = "⚠️";
    $btnText = "Back to Home";
    $btnLink = "index1.php";
    $error = "Debug Info: " . $e->getMessage();
}

include __DIR__ . '/header.php';
?>

<div class="dashboard-container" style="text-align: center; padding: 100px 20px;">
    <div style="font-size: 5rem; color: <?php echo $success ? '#27ae60' : '#e74c3c'; ?>; margin-bottom: 20px;">
        <?php echo $icon; ?>
    </div>
    <h1><?php echo $title; ?></h1>
    <p style="color: #666; margin-bottom: 30px;"><?php echo $msg; ?></p>

    <?php if (isset($error)): ?>
        <p
            style='color:#e74c3c; background: #fee; padding: 10px; border-radius: 5px; display: inline-block; margin-bottom: 20px;'>
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <br>
    <a href="<?php echo $btnLink; ?>" class="login-btn"
        style="display: inline-block; padding: 14px 40px; text-decoration: none;">
        <?php echo $btnText; ?>
    </a>
</div>

</main>
</body>

</html>