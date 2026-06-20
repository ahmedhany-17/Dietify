<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/vendor/autoload.php';

// 1. Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /app/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// ── Handle Address Selection & Payment Redirection (POST) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Check if Stripe Secret Key is configured
    if (empty(STRIPE_SECRET_KEY)) {
        die("Stripe Secret Key is not configured in .env");
    }

    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    $addressId = (int) ($_POST['address_id'] ?? 0);

    // Load address and user details
    $addrStmt = $conn->prepare("
        SELECT a.*, p.phone as user_phone 
        FROM user_addresses a 
        LEFT JOIN user_profiles p ON a.user_id = p.user_id 
        WHERE a.id = ? AND a.user_id = ?
    ");
    $addrStmt->bind_param("ii", $addressId, $userId);
    $addrStmt->execute();
    $address = $addrStmt->get_result()->fetch_assoc();
    $addrStmt->close();

    if (!$address) {
        die("Please select a valid shipping address.");
    }

    $location = $address['location_description'];
    // Use address-specific phone if it exists, otherwise fall back to user's primary phone
    $phone = !empty($address['phone']) ? $address['phone'] : ($address['user_phone'] ?? '');

    // Load Cart Items
    $cartQuery = "SELECT ci.*, p.name, p.price, p.image_path 
                  FROM cart_items ci 
                  JOIN carts c ON ci.cart_id = c.id 
                  JOIN products p ON ci.product_id = p.id 
                  WHERE c.user_id = $userId";
    $result = $conn->query($cartQuery);

    $line_items = [];
    $totalAmount = 0;
    $items = [];

    while ($row = $result->fetch_assoc()) {
        $totalAmount += $row['price'] * $row['quantity'];
        $items[] = $row;
        $line_items[] = [
            'price_data' => [
                'currency' => strtolower(CURRENCY_CODE),
                'product_data' => [
                    'name' => $row['name'],
                    'images' => [getImageUrl($row['image_path']) ? (str_starts_with(getImageUrl($row['image_path']), 'http') ? getImageUrl($row['image_path']) : APP_URL . getImageUrl($row['image_path'])) : 'https://via.placeholder.com/300'],
                ],
                'unit_amount' => $row['price'] * 100,
            ],
            'quantity' => $row['quantity'],
        ];
    }

    if (empty($line_items)) {
        header('Location: /app/cart.php');
        exit();
    }

    // Cleanup: Mark any existing "pending" orders for this user as cancelled
    $conn->query("UPDATE orders SET status = 'cancelled' WHERE user_id = $userId AND status = 'pending'");

    // Create Pending Order
    $orderStmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, location_description, phone) VALUES (?, ?, 'pending', ?, ?)");
    $orderStmt->bind_param("idss", $userId, $totalAmount, $location, $phone);
    $orderStmt->execute();
    $orderId = $conn->insert_id;

    // Insert Order Items
    foreach ($items as $item) {
        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
        $itemStmt->bind_param("iisdi", $orderId, $item['product_id'], $item['name'], $item['price'], $item['quantity']);
        $itemStmt->execute();
    }

    // Create Stripe Session
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'metadata' => [
            'order_id' => $orderId,
            'user_id' => $userId
        ],
        'success_url' => APP_URL . 'payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => APP_URL . 'payment_success.php?session_id={CHECKOUT_SESSION_ID}&cancelled=1',
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
    exit();
}

// ── Display Order Review & Address Selection (GET) ───────────────────────────
include __DIR__ . '/header.php';

$cartQuery = "SELECT ci.*, p.name, p.price, p.image_path 
              FROM cart_items ci 
              JOIN carts c ON ci.cart_id = c.id 
              JOIN products p ON ci.product_id = p.id 
              WHERE c.user_id = $userId";
$result = $conn->query($cartQuery);
$items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

if (empty($items)) {
    header('Location: cart.php');
    exit();
}

$userQuery = $conn->query("SELECT phone FROM user_profiles WHERE user_id = $userId");
$userPhone = $userQuery->fetch_assoc()['phone'] ?? '';
$addrs = $conn->query("SELECT * FROM user_addresses WHERE user_id = $userId ORDER BY is_default DESC");
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Checkout</h1>
        <p>Review your order and select a shipping address.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; margin-top: 30px;">
        <!-- Left Side: Address Selection -->
        <div>
            <form action="checkout.php" method="POST" id="checkoutForm">
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    📍 Shipping Address
                </h3>

                <?php if ($addrs->num_rows > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php while ($addr = $addrs->fetch_assoc()): ?>
                            <label style="display: block; cursor: pointer;">
                                <input type="radio" name="address_id" value="<?php echo $addr['id']; ?>" <?php echo $addr['is_default'] ? 'checked' : ''; ?> style="display:none; peer;">
                                <div class="address-card"
                                    style="padding: 20px; border: 2px solid #eee; border-radius: 15px; transition: all 0.3s; background: white; position: relative;">
                                    <div style="display: flex; align-items: flex-start; gap: 15px;">
                                        <div class="radio-circle"
                                            style="width: 20px; height: 20px; border: 2px solid #ddd; border-radius: 50%; margin-top: 2px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                            <div class="inner-dot"
                                                style="width: 10px; height: 10px; background: #ff6b35; border-radius: 50%; display: none;">
                                            </div>
                                        </div>
                                        <div>
                                            <p style="margin: 0 0 5px; font-weight: 600; color: #333;">
                                                <?php echo htmlspecialchars($addr['location_description']); ?>
                                            </p>
                                            <p style="margin: 0; color: #888; font-size: 0.9rem;">📞
                                                <?php echo htmlspecialchars(!empty($addr['phone']) ? $addr['phone'] : $userPhone); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        <?php endwhile; ?>
                    </div>
                    <a href="profile.php?tab=addresses"
                        style="display: inline-block; margin-top: 15px; color: #ff6b35; text-decoration: none; font-weight: 600;">+
                        Add or Manage Addresses</a>
                <?php else: ?>
                    <div
                        style="text-align: center; padding: 40px; background: #fff; border: 2px dashed #eee; border-radius: 15px;">
                        <p style="color: #666; margin-bottom: 15px;">You don't have any saved addresses.</p>
                        <a href="profile.php?tab=addresses" class="login-btn"
                            style="display: inline-block; text-decoration: none;">Add Shipping Address</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Right Side: Order Review -->
        <div>
            <div
                style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 20px;">Order Summary</h3>
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px;">
                    <?php foreach ($items as $item): ?>
                        <div
                            style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem;">
                            <span style="color: #555;"><?php echo $item['quantity']; ?>x
                                <?php echo htmlspecialchars($item['name']); ?></span>
                            <span
                                style="font-weight: 600;"><?php echo CURRENCY_SYMBOL . number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 2px solid #f8f9fa; pt-20; margin-top: 20px; padding-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #888;">
                        <span>Subtotal</span>
                        <span><?php echo CURRENCY_SYMBOL . number_format($total, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; color: #888;">
                        <span>Delivery</span>
                        <span style="color: #27ae60; font-weight: 600;">FREE</span>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; font-weight: 800; font-size: 1.4rem; color: #333;">
                        <span>Total</span>
                        <span style="color: #27ae60;"><?php echo CURRENCY_SYMBOL . number_format($total, 2); ?></span>
                    </div>
                </div>

                <button type="submit" form="checkoutForm" class="login-btn"
                    style="width: 100%; margin-top: 30px; padding: 18px; font-size: 1.1rem; <?php echo ($addrs->num_rows === 0) ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                    Confirm & Pay Now
                </button>
                <p style="text-align: center; font-size: 0.8rem; color: #aaa; margin-top: 15px;">
                    Secure payment via Stripe
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    input[type="radio"]:checked+.address-card {
        border-color: #ff6b35 !important;
        background: #fff9f6 !important;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.1);
    }

    input[type="radio"]:checked+.address-card .radio-circle {
        border-color: #ff6b35 !important;
    }

    input[type="radio"]:checked+.address-card .inner-dot {
        display: block !important;
    }

    .address-card:hover {
        border-color: #ddd;
    }
</style>

</main>
</body>

</html>