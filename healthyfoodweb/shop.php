<?php
require_once __DIR__ . '/init.php';

$type = $_GET['type'] ?? '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT p.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count 
          FROM products p 
          LEFT JOIN reviews r ON p.id = r.product_id 
          WHERE p.status = 'active'";

if ($type) {
    $query .= " AND p.type = '" . $conn->real_escape_string($type) . "'";
}

if ($search) {
    $query .= " AND p.name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$query .= " GROUP BY p.id ORDER BY p.created_at DESC";
$result = $conn->query($query);

include __DIR__ . '/header.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>The Healthy Shop</h1>
        <p>Browse our selection of nutritious food and drinks.</p>

        <!-- Search Bar -->
        <div style="max-width: 500px; margin: 25px auto 0; position: relative;">
            <form action="shop.php" method="GET">
                <?php if ($type): ?>
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Search for healthy meals..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="width: 100%; padding: 12px 50px 12px 20px; border-radius: 25px; border: 2px solid #ffe6dc; font-size: 0.95rem; outline: none; transition: border-color 0.2s;">
                <button type="submit"
                    style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: #ff6b35; color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer;">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <div style="margin-top: 25px;">
            <a href="shop.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>"
                class="<?php echo !$type ? 'btn-login' : 'btn-secondary'; ?>"
                style="padding: 8px 15px; text-decoration: none; margin-right: 10px; border-radius: 20px; border-color: #ff6b35; <?php echo !$type ? '' : 'color: #ff6b35;'; ?>">All</a>
            <a href="shop.php?type=food<?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                class="<?php echo $type === 'food' ? 'btn-login' : 'btn-secondary'; ?>"
                style="padding: 8px 15px; text-decoration: none; margin-right: 10px; border-radius: 20px; border-color: #ff6b35; <?php echo $type === 'food' ? '' : 'color: #ff6b35;'; ?>">Food</a>
            <a href="shop.php?type=drink<?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                class="<?php echo $type === 'drink' ? 'btn-login' : 'btn-secondary'; ?>"
                style="padding: 8px 15px; text-decoration: none; border-radius: 20px; border-color: #ff6b35; <?php echo $type === 'drink' ? '' : 'color: #ff6b35;'; ?>">Drinks</a>
        </div>
    </div>

    <div class="features-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($product = $result->fetch_assoc()): ?>
                <div class="feature-card" style="text-align: left;">
                    <img src="<?php echo getImageUrl($product['image_path']) ?: 'assets/images/placeholder-300x300.png'; ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.8rem; text-transform: uppercase; color: #ff6b35; font-weight: 700;">
                            <?php echo $product['type']; ?>
                        </span>
                        <span style="font-size: 0.9rem; color: #666;"><i class="fas fa-box"></i>
                            <?php echo $product['stock']; ?> left
                        </span>
                    </div>
                    <div style="margin: 10px 0 5px 0; color: #f1c40f; font-size: 0.85rem;">
                        <?php if ($product['review_count'] > 0): ?>
                            <?php
                            $rating = round($product['avg_rating']);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            }
                            ?>
                            <span style="color: #999; margin-left: 5px;">(<?php echo $product['review_count']; ?>)</span>
                        <?php else: ?>
                            <span style="color: #ccc;">No reviews yet</span>
                        <?php endif; ?>
                    </div>
                    <h3 style="margin: 5px 0 10px 0;">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <div style="font-weight: 700; color: #27ae60; font-size: 1.25rem;">
                            <?php echo CURRENCY_SYMBOL; ?>         <?php echo number_format($product['price'], 2); ?>
                        </div>
                        <form action="actions/cart_action.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="quick-add-btn" title="Add to Cart"
                                style="background: #ff6b35; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 4px 10px rgba(255, 107, 53, 0.3);">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </form>
                    </div>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="login-btn"
                        style="display: block; text-align: center; margin-top: 15px; text-decoration: none; background: transparent; color: #ff6b35; border: 1px solid #ff6b35;">View
                        Details</a>
                </div>

                <style>
                    .quick-add-btn:hover {
                        transform: scale(1.1) rotate(-5deg);
                        background: #e85a24 !important;
                        box-shadow: 0 6px 15px rgba(255, 107, 53, 0.5) !important;
                    }

                    .feature-card:hover {
                        transform: translateY(-5px);
                    }
                </style>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1/-1;">No products found matching your criteria.</p>
        <?php endif; ?>
    </div>
</div>

</main>
</body>

</html>