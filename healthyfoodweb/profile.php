<?php
require_once __DIR__ . '/init.php';

// use etc is like using namespace std; in c++ 
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PragmaRX\Google2FA\Google2FA;

// Auth guard it redirect users without id to login page
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
//(int ) make user_id as integer ,, it turn the data type into integer  
$userId = (int) $_SESSION['user_id'];
$message = '';
$msgType = 'success';
$google2fa = new Google2FA();
$currentTab = $_GET['tab'] ?? 'orders';
$allowedTabs = ['settings', 'orders', 'addresses'];
if (!in_array($currentTab, $allowedTabs)) {
    $currentTab = 'orders';
}

// ── Load user details ────────────────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT u.id, u.username, u.email, u.password, u.twofa_method, u.role, u.created_at, 
            p.avatar, p.age, p.weight, p.height, p.gender, p.phone, 
            t.totp_secret, t.confirmed_at as totp_confirmed_at
     FROM users u
     LEFT JOIN user_profiles p ON u.id = p.user_id
     LEFT JOIN user_totp t ON u.id = t.user_id
     WHERE u.id = ?"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// ── Handle POST actions (2FA Management & Orders) ────────────────────────────
$action = $_POST['action'] ?? '';

// 0. CANCEL ORDER
if ($action === 'cancel_order') {
    $orderIdToCancel = (int) ($_POST['order_id'] ?? 0);

    // Fetch order details first to check timing and status
    $stmt = $conn->prepare("SELECT status, created_at FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $orderIdToCancel, $userId);
    $stmt->execute();
    $orderToCancel = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($orderToCancel) {
        $createdAt = strtotime($orderToCancel['created_at']);
        $now = time();
        $isCancellable = false;
        $timeMsg = "10 minutes";

        if ($orderToCancel['status'] === 'pending') {
            if (($now - $createdAt) <= 600) {
                $isCancellable = true;
            }
        } elseif ($orderToCancel['status'] === 'paid') {
            // Check payment time specifically for paid orders
            $timeMsg = "20 minutes from payment";
            $pStmt = $conn->prepare("SELECT paid_at FROM payments WHERE order_id = ? AND status = 'completed'");
            $pStmt->bind_param('i', $orderIdToCancel);
            $pStmt->execute();
            $payData = $pStmt->get_result()->fetch_assoc();
            $pStmt->close();

            if ($payData && ($now - strtotime($payData['paid_at'])) <= 1200) {
                $isCancellable = true;
            }
        }
        // Shipped orders CANNOT be cancelled

        if ($isCancellable) {
            $conn->begin_transaction();
            try {
                $newStatus = 'cancelled';

                if ($orderToCancel['status'] === 'paid') {
                    // Check payment method
                    $pStmt = $conn->prepare("SELECT method, transaction_id FROM payments WHERE order_id = ? AND status = 'completed'");
                    $pStmt->bind_param('i', $orderIdToCancel);
                    $pStmt->execute();
                    $paymentInfo = $pStmt->get_result()->fetch_assoc();
                    $pStmt->close();

                    if ($paymentInfo && $paymentInfo['method'] === 'stripe') {
                        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
                        try {
                            $session = \Stripe\Checkout\Session::retrieve($paymentInfo['transaction_id']);
                            if ($session->payment_intent) {
                                \Stripe\Refund::create(['payment_intent' => $session->payment_intent]);

                                // Update payment record
                                $updPay = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE order_id = ? AND transaction_id = ?");
                                $updPay->bind_param('is', $orderIdToCancel, $paymentInfo['transaction_id']);
                                $updPay->execute();
                                $updPay->close();

                                $newStatus = 'cancelled'; // Or 'refunded' if you prefer
                            }
                        } catch (Exception $e) {
                            throw new Exception("Stripe Refund Failed: " . $e->getMessage());
                        }
                    }

                    // Restore Stock
                    $itemsResult = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $orderIdToCancel");
                    while ($item = $itemsResult->fetch_assoc()) {
                        $conn->query("UPDATE products SET stock = stock + {$item['quantity']} WHERE id = {$item['product_id']}");
                    }
                }

                // Update Order Status
                $cancelStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
                $cancelStmt->bind_param('sii', $newStatus, $orderIdToCancel, $userId);
                $cancelStmt->execute();

                if ($cancelStmt->affected_rows > 0) {
                    $conn->commit();
                    $message = "Order #$orderIdToCancel has been successfully cancelled" . ($orderToCancel['status'] === 'paid' ? " and refunded" : "") . ".";
                    $msgType = 'success';
                } else {
                    throw new Exception("Order update failed.");
                }
                $cancelStmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Could not cancel order: " . $e->getMessage();
                $msgType = 'error';
            }
        } else {
            $message = "Cancellation period ($timeMsg) has expired for this order.";
            $msgType = 'error';
        }
    } else {
        $message = "Order not found.";
        $msgType = 'error';
    }
}

// ── ADDRESS MANAGEMENT ────────────────────────────────────────────────────────
if ($action === 'add_address') {
    $location = trim($_POST['location_description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if (!empty($location)) {
        // If phone is provided, validate it. If not, it will be NULL in DB.
        if (!empty($phone) && !preg_match('/^01[0125][0-9]{8}$/', $phone)) {
            $message = "Phone number must be 11 digits and start with 010, 011, 012, or 015.";
            $msgType = 'error';
        } else {
            if ($is_default) {
                $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $userId");
            }

            $dbPhone = !empty($phone) ? $phone : null;
            $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, location_description, phone, is_default) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $userId, $location, $dbPhone, $is_default);
            if ($stmt->execute()) {
                $message = "Address added successfully!";
                $msgType = 'success';
            } else {
                $message = "Error adding address.";
                $msgType = 'error';
            }
            $stmt->close();
        }
    }
} elseif ($action === 'delete_address') {
    $addrId = (int) ($_POST['address_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $addrId, $userId);
    if ($stmt->execute()) {
        $message = "Address deleted successfully.";
        $msgType = 'success';
    }
    $stmt->close();
} elseif ($action === 'set_default_address') {
    $addrId = (int) ($_POST['address_id'] ?? 0);
    $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $userId");
    $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $addrId, $userId);
    if ($stmt->execute()) {
        $message = "Default address updated.";
        $msgType = 'success';
    }
    $stmt->close();
}

// 1. DISABLE 2FA
if ($action === 'disable') {
    // begin_transaction is used to start a transaction ,, it is used to make sure that all the queries are executed successfully ,, if any query fails then all the queries are rolled back 
    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE users SET twofa_method='none' WHERE id=?");
        $upd->bind_param('i', $userId);
        $upd->execute();
        $upd->close();

        $del = $conn->prepare("DELETE FROM user_totp WHERE user_id=?");
        $del->bind_param('i', $userId);
        $del->execute();
        $del->close();

        // Delete backup codes
        $delBc = $conn->prepare("DELETE FROM backup_codes WHERE user_id=?");
        $delBc->bind_param('i', $userId);
        $delBc->execute();
        $delBc->close();
        //COMMI
        $conn->commit();
        $message = "Two-factor authentication has been disabled.";
        $msgType = 'error';
        $user['twofa_method'] = 'none';
        $user['totp_secret'] = null;
        $user['totp_confirmed_at'] = null;
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error disabling 2FA.";
        $msgType = 'error';
    }
}
// 2. ENABLE EMAIL 2FA
elseif ($action === 'enable_email') {
    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE users SET twofa_method='email' WHERE id=?");
        $upd->bind_param('i', $userId);
        $upd->execute();
        $upd->close();

        $del = $conn->prepare("DELETE FROM user_totp WHERE user_id=?");
        $del->bind_param('i', $userId);
        $del->execute();
        $del->close();

        // Delete backup codes (TOTP specific)
        $delBc = $conn->prepare("DELETE FROM backup_codes WHERE user_id=?");
        $delBc->bind_param('i', $userId);
        $delBc->execute();
        $delBc->close();

        $conn->commit();
        $message = "Email 2FA enabled successfully!";
        $user['twofa_method'] = 'email';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error enabling email 2FA.";
        $msgType = 'error';
    }
}
// 3. START TOTP SETUP
elseif ($action === 'start_totp') {
    $secret = $google2fa->generateSecretKey();

    $conn->begin_transaction();
    try {
        // Clear any existing (unconfirmed/confirmed) TOTP and backup codes
        $del = $conn->prepare("DELETE FROM user_totp WHERE user_id=?");
        $del->bind_param('i', $userId);
        $del->execute();
        $del->close();

        $delBc = $conn->prepare("DELETE FROM backup_codes WHERE user_id=?");
        $delBc->bind_param('i', $userId);
        $delBc->execute();
        $delBc->close();

        // Insert new secret (not confirmed yet)
        $dummyDate = '1970-01-01 00:00:00';
        $ins = $conn->prepare("INSERT INTO user_totp (user_id, totp_secret, confirmed_at) VALUES (?, ?, ?)");
        $ins->bind_param('iss', $userId, $secret, $dummyDate);
        $ins->execute();
        $ins->close();

        $conn->commit();
        $user['totp_secret'] = $secret;
        $user['totp_confirmed_at'] = null; // We'll treat the dummy as null in display
        $showQr = true;
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error starting TOTP setup.";
        $msgType = 'error';
    }
}
// 4. CONFIRM TOTP
elseif ($action === 'confirm_totp') {
    $code = trim($_POST['totp_code'] ?? '');

    $s2 = $conn->prepare("SELECT totp_secret FROM user_totp WHERE user_id=?");
    $s2->bind_param('i', $userId);
    $s2->execute();
    $secret = $s2->get_result()->fetch_assoc()['totp_secret'] ?? '';
    $s2->close();

    if ($google2fa->verifyKey($secret, $code)) {
        $now = date('Y-m-d H:i:s');
        $conn->begin_transaction();
        try {
            $upd1 = $conn->prepare("UPDATE users SET twofa_method='totp' WHERE id=?");
            $upd1->bind_param('i', $userId);
            $upd1->execute();
            $upd1->close();

            $upd2 = $conn->prepare("UPDATE user_totp SET confirmed_at=? WHERE user_id=?");
            $upd2->bind_param('si', $now, $userId);
            $upd2->execute();
            $upd2->close();

            // ── DELETE OLD BACKUP CODES & GENERATE NEW ONES ───────────────────
            $delBc = $conn->prepare("DELETE FROM backup_codes WHERE user_id=?");
            $delBc->bind_param('i', $userId);
            $delBc->execute();
            $delBc->close();

            $plainCodes = [];
            for ($i = 0; $i < 10; $i++) {
                $code = strtoupper(bin2hex(random_bytes(4)));
                $plainCodes[] = $code;
                $hash = password_hash($code, PASSWORD_BCRYPT);
                $ins = $conn->prepare("INSERT INTO backup_codes (user_id, code_hash) VALUES (?, ?)");
                $ins->bind_param('is', $userId, $hash);
                $ins->execute();
                $ins->close();
            }

            require_once __DIR__ . '/includes/send_otp_email.php';
            sendBackupCodesEmail($user['email'], $user['username'], $plainCodes);

            $_SESSION['new_backup_codes'] = $plainCodes;
            // ───────────────────────────────────────────────────────────────

            $conn->commit();
            $message = "Authenticator app enabled! Backup codes sent to email. 🎉";
            $user['twofa_method'] = 'totp';
            $user['totp_confirmed_at'] = $now;
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error confirming TOTP: " . $e->getMessage();
            $msgType = 'error';
        }
    } else {
        $message = "Invalid code – please try again.";
        $msgType = 'error';
        $showQr = true;
        $user['totp_secret'] = $secret;
    }
}
// 5. CHANGE PASSWORD
elseif ($action === 'change_password') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPass, $user['password'])) {
        $message = "Current password is incorrect.";
        $msgType = 'error';
    } elseif ($newPass !== $confirmPass) {
        $message = "New passwords do not match.";
        $msgType = 'error';
    } elseif (strlen($newPass) < 12) {
        $message = "New password must be at least 12 characters.";
        $msgType = 'error';
    } else {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param('si', $hashed, $userId);
        if ($upd->execute()) {
            $message = "Password updated successfully! 🔐";
            $msgType = 'success';
            // Update the local $user array so subsequent checks use the new hash
            $user['password'] = $hashed;
        } else {
            $message = "Error updating password.";
            $msgType = 'error';
        }
        $upd->close();
    }
}

// ── Build TOTP QR code if required ───────────────────────────────────────────
$qrBase64 = '';
if (!empty($showQr) && !empty($user['totp_secret'])) {
    $otpUrl = $google2fa->getQRCodeUrl('Healthy Food', $user['email'], $user['totp_secret']);
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_MARKUP_SVG,
        'eccLevel' => QRCode::ECC_L,
        'scale' => 6,
    ]);
    $qrBase64 = (new QRCode($options))->render($otpUrl);
}

$currentMethod = $user['twofa_method'];

include __DIR__ . '/header.php';
?>

<style>
    .profile-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 30px;
    }

    .user-card,
    .security-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .avatar-circle {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #ff6b35, #ff9f1c);
        border-radius: 50%;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        font-weight: 700;
    }

    .user-info {
        text-align: center;
    }

    .user-info h2 {
        margin: 10px 0 5px;
        color: #333;
    }

    .user-info p {
        color: #888;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }

    .info-grid {
        text-align: left;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .info-item {
        margin-bottom: 15px;
    }

    .info-label {
        font-size: 0.8rem;
        color: #aaa;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .info-value {
        font-weight: 600;
        color: #444;
    }

    .logout-btn {
        display: block;
        width: 100%;
        padding: 12px;
        background: #f8f9fa;
        color: #dc3545;
        border: 1px solid #eee;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 20px;
        transition: all 0.3s;
    }

    .logout-btn:hover {
        background: #fee2e2;
        border-color: #fecaca;
    }

    /* Backup Codes UI */
    .backup-codes-box {
        background: #f8f9fa;
        border: 2px dashed #d1d5da;
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
        animation: slideIn 0.5s ease;
    }

    .codes-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 15px;
    }

    .code-item {
        font-family: monospace;
        background: #fff;
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #e1e4e8;
        text-align: center;
        font-weight: 700;
        color: #24292e;
        font-size: 1.1rem;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Tab Navigation Styles */
    .profile-tabs {
        margin-top: 30px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .tab-link {
        display: block;
        padding: 12px 15px;
        margin-bottom: 10px;
        border-radius: 10px;
        text-decoration: none;
        color: #555;
        font-weight: 600;
        transition: all 0.3s;
        background: #f8f9fa;
        border: 1px solid transparent;
    }

    .tab-link:hover {
        background: #e9ecef;
    }

    .tab-link.active {
        background: white;
        color: #ff6b35;
        border-color: #ff6b35;
        box-shadow: 0 4px 6px rgba(255, 107, 53, 0.1);
    }

    /* Order Styles */
    .order-card {
        background: #f8f9fa;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px dashed #eee;
    }

    .order-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-paid {
        background: #d4edda;
        color: #155724;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .cancel-timer {
        font-size: 0.8rem;
        color: #dc3545;
        font-weight: 600;
        margin-top: 5px;
    }

    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }

    .password-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
        max-width: 400px;
        margin-top: 20px;
    }

    .form-input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: border-color 0.3s;
    }

    .form-input:focus {
        outline: none;
        border-color: #ff6b35;
    }

    .btn-save-pass {
        background: #333;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s;
    }

    .btn-save-pass:hover {
        background: #000;
        transform: translateY(-1px);
    }
</style>

<div style="background: #f0f2f5; min-height: calc(100vh - 70px); padding: 40px 0;">
    <div class="profile-container">
        <!-- ── LEFT: USER INFO ── -->
        <div class="user-card">
            <?php if (!empty($user['avatar']) && $user['avatar'] !== 'assets/images/default_avatar.png'): ?>
                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar"
                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 20px; display: block; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <?php else: ?>
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div class="user-info">
                <h2>
                    <?php echo htmlspecialchars($user['username']); ?>
                </h2>
                <p>Member since
                    <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                </p>
                <a href="edit_profile.php" class="settings-btn"
                    style="background:#ff6b35; color:white; border:none; padding: 8px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-bottom: 20px; font-size: 0.9rem;">✏️
                    Edit Profile</a>

                <div class="info-grid">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value">
                                <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '<span style="color:#aaa;font-weight:normal;">Not provided</span>'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Age</div>
                            <div class="info-value">
                                <?php echo $user['age'] ? htmlspecialchars($user['age']) . ' yrs' : '<span style="color:#aaa;font-weight:normal;">Not provided</span>'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Gender</div>
                            <div class="info-value">
                                <?php echo $user['gender'] ? ucfirst(htmlspecialchars($user['gender'])) : '<span style="color:#aaa;font-weight:normal;">Not provided</span>'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Weight</div>
                            <div class="info-value">
                                <?php echo $user['weight'] ? htmlspecialchars($user['weight']) . ' kg' : '<span style="color:#aaa;font-weight:normal;">Not provided</span>'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Height</div>
                            <div class="info-value">
                                <?php echo $user['height'] ? htmlspecialchars($user['height']) . ' cm' : '<span style="color:#aaa;font-weight:normal;">Not provided</span>'; ?>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee;">
                        <!-- Role check restored -->
                        <div class="info-item">
                            <div class="info-label">Account Role</div>
                            <div class="info-value"
                                style="color: <?php echo $user['role'] === 'admin' ? '#ff6b35' : '#444'; ?>;">
                                <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Account Security</div>
                            <div class="info-value">
                                <?php if ($currentMethod === 'none'): ?>
                                    <span style="color:#dc3545">● Standard</span>
                                <?php else: ?>
                                    <span style="color:#28a745">● 2FA Protected</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-tabs">
                    <a href="?tab=settings" class="tab-link <?php echo $currentTab === 'settings' ? 'active' : ''; ?>">
                        ⚙️ Settings
                    </a>
                    <a href="?tab=orders" class="tab-link <?php echo $currentTab === 'orders' ? 'active' : ''; ?>">
                        📦 My Orders
                    </a>
                    <a href="?tab=addresses"
                        class="tab-link <?php echo $currentTab === 'addresses' ? 'active' : ''; ?>">
                        📍 My Addresses
                    </a>
                </div>

                <a href="/app/logout.php" class="logout-btn">Sign Out</a>
            </div>
        </div>

        <!-- ── RIGHT: TAB CONTENT ── -->
        <div class="security-card">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $msgType; ?> mb-4" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($currentTab === 'settings'): ?>
                <!-- Settings Tab Content -->
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    ⚙️ Account Settings
                </h3>

                <!-- Backup Codes Display (One-time) -->
                <?php if (!empty($_SESSION['new_backup_codes'])): ?>
                    <div class="backup-codes-box">
                        <h4 style="margin: 0; color: #d4a017;">⚠️ Save your Backup Codes</h4>
                        <p style="font-size: 0.85rem; color: #666; margin: 5px 0 15px;">
                            Each code can be used once to log in if you lose your phone.
                        </p>
                        <div class="codes-grid">
                            <?php foreach ($_SESSION['new_backup_codes'] as $code): ?>
                                <div class="code-item"><?php echo htmlspecialchars($code); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php unset($_SESSION['new_backup_codes']); ?>
                <?php endif; ?>

                <div class="twofa-status">
                    <?php if ($currentMethod === 'none'): ?>
                        <span class="badge badge-off">🔓 Two-Factor Authentication is currently Disabled</span>
                    <?php elseif ($currentMethod === 'email'): ?>
                        <span class="badge badge-on">✅ Email 2FA enabled</span>
                    <?php elseif ($currentMethod === 'totp'): ?>
                        <span class="badge badge-on">✅ Authenticator Apps enabled</span>
                    <?php endif; ?>
                </div>

                <!-- QR Setup Step -->
                <?php if (!empty($showQr)): ?>
                    <div class="qr-panel">
                        <p class="qr-instruction">Scan this with Google Authenticator:</p>
                        <div class="qr-container">
                            <img src="<?php echo $qrBase64; ?>" alt="QR Code">
                        </div>
                        <p class="qr-manual">Manual key: <code class="secret-code"><?php echo $user['totp_secret']; ?></code>
                        </p>
                        <form method="POST" class="confirm-form">
                            <input type="hidden" name="action" value="confirm_totp">
                            <label
                                style="display:block; margin-bottom: 8px; font-size: 0.9rem; color: #475569; font-weight: 600;">
                                Enter the 6-digit code:
                            </label>
                            <input type="text" name="totp_code" class="otp-input" maxlength="6" required autofocus
                                inputmode="numeric" pattern="[0-9]*">
                            <button type="submit" class="settings-btn btn-totp" style="margin-top:15px">Verify & Enable</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="settings-actions">
                    <?php if ($currentMethod !== 'email'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="enable_email">
                            <button type="submit" class="settings-btn btn-email">
                                📧
                                <?php echo ($currentMethod === 'none') ? 'Enable' : 'Switch to'; ?> Email 2FA
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($currentMethod !== 'totp' && empty($showQr)): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="start_totp">
                            <button type="submit" class="settings-btn btn-totp">
                                🔐
                                <?php echo ($currentMethod === 'none') ? 'Enable' : 'Switch to'; ?> Authenticator Apps
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($currentMethod !== 'none'): ?>
                        <form method="POST"
                            onsubmit="return confirm('Disable Two-Factor Authentication? Your account will be less secure.')">
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="settings-btn btn-disable">
                                🔓 Disable 2FA
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 30px;">
                    <h4 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 8px;">
                        🔑 Update Your Password
                    </h4>
                    <form method="POST" class="password-form">
                        <input type="hidden" name="action" value="change_password">
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-size: 0.85rem; color: #666; font-weight: 600;">Current Password</label>
                            <input type="password" name="current_password" class="form-input" required
                                placeholder="••••••••">
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-size: 0.85rem; color: #666; font-weight: 600;">New Password</label>
                            <input type="password" name="new_password" class="form-input" required minlength="12"
                                placeholder="Minimum 12 characters">
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-size: 0.85rem; color: #666; font-weight: 600;">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-input" required minlength="12"
                                placeholder="Repeat new password">
                        </div>
                        <button type="submit" class="btn-save-pass">
                            Save New Password
                        </button>
                    </form>
                </div>

            <?php elseif ($currentTab === 'orders'): ?>
                <!-- Orders Tab Content -->
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    📦 My Orders
                </h3>

                <?php
                $orderStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
                $orderStmt->bind_param("i", $userId);
                $orderStmt->execute();
                $ordersResult = $orderStmt->get_result();

                if ($ordersResult && $ordersResult->num_rows > 0):
                    while ($order = $ordersResult->fetch_assoc()):
                        // Fetch items for this order
                        $itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                        $itemsStmt->bind_param("i", $order['id']);
                        $itemsStmt->execute();
                        $itemsResult = $itemsStmt->get_result();

                        // Fetch payment details
                        $paymentStmt = $conn->prepare("SELECT method, status, paid_at FROM payments WHERE order_id = ? LIMIT 1");
                        $paymentStmt->bind_param("i", $order['id']);
                        $paymentStmt->execute();
                        $paymentData = $paymentStmt->get_result()->fetch_assoc();

                        $statusClass = 'status-pending';
                        if ($order['status'] === 'paid')
                            $statusClass = 'status-paid';
                        if ($order['status'] === 'cancelled' || $order['status'] === 'failed')
                            $statusClass = 'status-cancelled';
                        ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h4 style="margin: 0; color: #333;">Order #<?php echo $order['id']; ?></h4>
                                    <small
                                        style="color: #888;"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></small>
                                </div>
                                <span class="order-status <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <?php while ($item = $itemsResult->fetch_assoc()): ?>
                                    <div class="order-item">
                                        <span><?php echo $item['quantity']; ?>x
                                            <?php echo htmlspecialchars($item['product_name']); ?></span>
                                        <span
                                            style="color: #27ae60; font-weight: 600;"><?php echo CURRENCY_SYMBOL . number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                <?php endwhile; ?>
                                <div class="order-item" style="border-top: 2px solid #ddd; border-bottom: none; font-weight: bold;">
                                    <span>Total Amount:</span>
                                    <span
                                        style="color: #27ae60;"><?php echo CURRENCY_SYMBOL . number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>

                            <div
                                style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9em; background: #fff; padding: 10px; border-radius: 8px;">
                                <div>
                                    <strong>Payment:</strong>
                                    <?php
                                    if ($paymentData) {
                                        echo ucfirst($paymentData['method']) . " (" . ucfirst($paymentData['status']) . ")";
                                    } elseif ($order['status'] === 'cancelled') {
                                        echo "Cancelled";
                                    } else {
                                        echo "Pending";
                                    }
                                    ?>
                                </div>

                                <?php
                                $createdAt = strtotime($order['created_at']);
                                $now = time();
                                $isCancellable = false;
                                $timeLeft = 0;

                                if ($order['status'] === 'pending') {
                                    $timeLeft = 600 - ($now - $createdAt);
                                    if ($timeLeft > 0)
                                        $isCancellable = true;
                                } elseif ($order['status'] === 'paid' && !empty($paymentData['paid_at'])) {
                                    $paidAt = strtotime($paymentData['paid_at']);
                                    $timeLeft = 1200 - ($now - $paidAt); // 20 minutes = 1200 seconds
                                    if ($timeLeft > 0)
                                        $isCancellable = true;
                                }
                                ?>

                                <?php if ($isCancellable): ?>
                                    <div style="text-align: right;">
                                        <form method="POST" style="margin:0;"
                                            onsubmit="return confirm('Are you sure you want to cancel this order? A refund will be issued automatically.');">
                                            <input type="hidden" name="action" value="cancel_order">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit"
                                                style="background:#dc3545; color:white; border:none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel
                                                Order</button>
                                        </form>
                                        <div class="cancel-timer" data-time-left="<?php echo $timeLeft; ?>"
                                            id="timer-<?php echo $order['id']; ?>">
                                            Time left to cancel: <?php echo floor($timeLeft / 60); ?>m <?php echo ($timeLeft % 60); ?>s
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    endwhile;
                else:
                    ?>
                    <div style="text-align: center; padding: 40px; color: #888; background: #f8f9fa; border-radius: 10px;">
                        <span style="font-size: 3rem;">🛍️</span>
                        <p style="margin-top: 15px;">You haven't placed any orders yet.</p>
                        <a href="shop.php" class="login-btn"
                            style="display: inline-block; text-decoration: none; margin-top: 10px;">Start Shopping</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($currentTab === 'addresses'): ?>
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    📍 My Addresses
                </h3>

                <?php
                $addrStmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
                $addrStmt->bind_param("i", $userId);
                $addrStmt->execute();
                $addrsResult = $addrStmt->get_result();

                if ($addrsResult && $addrsResult->num_rows > 0):
                    while ($addr = $addrsResult->fetch_assoc()):
                        ?>
                        <div
                            style="background: #f8f9fa; border: 1px solid #eee; border-radius: 12px; padding: 20px; margin-bottom: 15px; position: relative;">
                            <?php if ($addr['is_default']): ?>
                                <span
                                    style="position: absolute; top: 15px; right: 20px; background: #27ae60; color: white; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase;">Default</span>
                            <?php endif; ?>

                            <p style="margin: 0 0 10px; font-weight: 600; color: #333; line-height: 1.4;">
                                <?php echo htmlspecialchars($addr['location_description']); ?>
                            </p>
                            <p style="margin: 0 0 15px; color: #666; font-size: 0.9rem;">
                                <?php echo htmlspecialchars(!empty($addr['phone']) ? $addr['phone'] : ($user['phone'] ?? '')); ?>
                            </p>

                            <div style="display: flex; gap: 10px;">
                                <?php if (!$addr['is_default']): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="set_default_address">
                                        <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                                        <button type="submit"
                                            style="background: white; border: 1px solid #ddd; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer; color: #555;">Set
                                            as Default</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this address?');">
                                    <input type="hidden" name="action" value="delete_address">
                                    <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                                    <button type="submit"
                                        style="background: white; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer; color: #dc3545;">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                    <p style="text-align: center; color: #888; padding: 20px; background: #f8f9fa; border-radius: 10px;">No
                        addresses saved yet.</p>
                <?php endif; ?>

                <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee;">
                    <h4 style="margin-bottom: 20px;">➕ Add New Address</h4>
                    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="action" value="add_address">
                        <div>
                            <label
                                style="display: block; font-size: 0.85rem; color: #666; margin-bottom: 5px; font-weight: 600;">Delivery
                                Address</label>
                            <textarea name="location_description" class="form-control"
                                placeholder="Street, Building, Apartment, City..." required
                                style="min-height: 80px;"></textarea>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.85rem; color: #666; margin-bottom: 5px; font-weight: 600;">Contact
                                Phone <span style="font-weight: normal; color: #aaa;">(Optional: Defaults to profile
                                    phone)</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" value=""
                                minlength="11" maxlength="11" pattern="01[0125][0-9]{8}">
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="is_default" id="is_default" value="1">
                            <label for="is_default" style="font-size: 0.9rem; color: #444; cursor: pointer;">Set as default
                                address</label>
                        </div>
                        <button type="submit" class="btn-submit" style="margin-top: 10px;">Add Address</button>
                    </form>
                </div>

            <?php endif; // End of tabs switch ?>
        </div>
    </div>
</div>
</main>
<script>
    function updateTimers() {
        document.querySelectorAll('.cancel-timer').forEach(timer => {
            let timeLeft = parseInt(timer.getAttribute('data-time-left'));
            if (timeLeft > 0) {
                timeLeft--;
                timer.setAttribute('data-time-left', timeLeft);
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timer.textContent = `Time left to cancel: ${minutes}m ${seconds}s`;
            } else {
                const container = timer.closest('div');
                if (container && container.style.textAlign === 'right') {
                    container.style.display = 'none';
                }
            }
        });
    }
    setInterval(updateTimers, 1000);
</script>
</body>

</html>