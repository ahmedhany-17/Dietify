<?php
require_once __DIR__ . '/init.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$cartQuery = "SELECT ci.*, p.name, p.price, p.image_path, p.stock 
              FROM cart_items ci 
              JOIN carts c ON ci.cart_id = c.id 
              JOIN products p ON ci.product_id = p.id 
              WHERE c.user_id = $userId";
$result = $conn->query($cartQuery);

$total = 0;
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

include __DIR__ . '/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Your Shopping Cart</h1>
        <p>You have
            <?php echo count($items); ?> items in your cart.
        </p>
    </div>

    <?php if (count($items) > 0): ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            <div class="cart-items">
                <?php foreach ($items as $item): ?>
                    <div
                        style="background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; align-items: center; gap: 20px;">
                        <img src="<?php echo getImageUrl($item['image_path']) ?: 'assets/images/placeholder-300x300.png'; ?>" alt=""
                            style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px;">
                        <div style="flex: 1;">
                            <h3 style="margin-bottom: 5px;">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h3>
                            <div style="color: #27ae60; font-weight: 700;">
                                <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($item['price'], 2); ?>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                            <form action="actions/cart_action.php" method="POST"
                                style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 8px;">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="action" value="update">
                                <button type="button" onclick="let q = this.form.querySelector('input[name=quantity]'); if(q.value > 1) { q.value--; this.form.submit(); }"
                                    style="padding: 5px 12px; border: none; background: transparent; cursor: pointer; font-weight: bold; color: #555;">-</button>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1"
                                    onchange="this.form.submit()"
                                    style="width: 45px; text-align: center; border: 1px solid #eee; border-radius: 4px; padding: 5px 0; font-weight: 600;">
                                <button type="button" onclick="let q = this.form.querySelector('input[name=quantity]'); q.value++; this.form.submit();"
                                    style="padding: 5px 12px; border: none; background: transparent; cursor: pointer; font-weight: bold; color: #555;">+</button>
                            </form>
                            <form action="actions/cart_action.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit"
                                    style="background: none; border: none; color: #e74c3c; cursor: pointer; font-size: 0.9rem;"><i
                                        class="fas fa-trash"></i> Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary"
                style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); height: fit-content;">
                <h2 style="margin-bottom: 25px;">Order Summary</h2>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; color: #666;">
                    <span>
                        <?php echo CURRENCY_SYMBOL; ?>     <?php echo number_format($total, 2); ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; color: #666;">
                    <span>Delivery</span>
                    <span style="color: #27ae60;">FREE</span>
                </div>
                <div
                    style="border-top: 2px solid #eee; margin: 20px 0; padding-top: 20px; display: flex; justify-content: space-between; font-weight: 700; font-size: 1.25rem;">
                    <span>Total</span>
                    <span style="color: #27ae60;">
                        <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($total, 2); ?>
                    </span>
                </div>
                <a href="checkout.php" class="login-btn"
                    style="display: block; text-align: center; text-decoration: none; margin-top: 30px;">Proceed to
                    Checkout</a>
                <a href="shop.php"
                    style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 0.9rem;">Continue
                    Shopping</a>
            </div>
        </div>
    <?php else: ?>
        <div
            style="text-align: center; padding: 80px 20px; background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <div style="font-size: 4rem; margin-bottom: 20px;">🛒</div>
            <h2>Your cart is empty</h2>
            <p style="color: #666; margin-bottom: 30px;">It looks like you haven't added anything to your cart yet.</p>
            <a href="shop.php" class="login-btn"
                style="display: inline-block; padding: 14px 40px; text-decoration: none;">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

</main>
</body>

</html>