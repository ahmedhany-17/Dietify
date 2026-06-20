<?php
require_once __DIR__ . '/config.php';

use PragmaRX\Google2FA\Google2FA;

if (empty($_SESSION['2fa_user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['2fa_user_id'];
$method = $_GET['method'] ?? 'email';
$error = '';
$google2fa = new Google2FA();

$userStmt = $conn->prepare(
    "SELECT u.id, u.username, u.email, u.twofa_method, u.role, t.totp_secret 
     FROM users u
     LEFT JOIN user_totp t ON u.id = t.user_id
     WHERE u.id = ?"
);
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if ($method === 'email') {
        $redis = new Predis\Client();
        $otpHash = $redis->get("otp:$userId:twofa");

        if ($otpHash && password_verify($code, $otpHash)) {
            $redis->del("otp:$userId:twofa");

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            unset($_SESSION['2fa_user_id']);
            
            if ($user['role'] === 'admin') {
                header("Location: admin/AdminPanel.php");
            } else {
                header("Location: profile.php");
            }
            exit();
        } else {
            $error = "Invalid or expired code. Please try again.";
        }

    } elseif ($method === 'totp') {

        if ($google2fa->verifyKey($user['totp_secret'], $code)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            unset($_SESSION['2fa_user_id']);
            
            if ($user['role'] === 'admin') {
                header("Location: admin/AdminPanel.php");
            } else {
                header("Location: profile.php");
            }
            exit();
        } else {
            // Try backup codes
            $bcStmt = $conn->prepare("SELECT id, code_hash FROM backup_codes WHERE user_id = ? AND used_at IS NULL");
            $bcStmt->bind_param('i', $userId);
            $bcStmt->execute();
            $bcResult = $bcStmt->get_result();

            $foundBc = false;
            while ($bcRow = $bcResult->fetch_assoc()) {
                if (password_verify($code, $bcRow['code_hash'])) {
                    // Mark as used
                    $updBc = $conn->prepare("UPDATE backup_codes SET used_at = NOW() WHERE id = ?");
                    $updBc->bind_param('i', $bcRow['id']);
                    $updBc->execute();
                    $updBc->close();

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    unset($_SESSION['2fa_user_id']);
                    
                    if ($user['role'] === 'admin') {
                        header("Location: admin/AdminPanel.php");
                    } else {
                        header("Location: profile.php");
                    }
                    exit();
                }
            }
            $bcStmt->close();

            $error = "Invalid code. Please try again.";
        }
    }
}

if ($method === 'email' && isset($_GET['resend'])) {
    require_once __DIR__ . '/includes/send_otp_email.php';
    try {
        sendOtpEmail($conn, $userId, $user['email'], $user['username']);
        $resent = true;
    } catch (Exception $e) {
        $error = "Could not resend code. Please go back and try logging in again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity – Healthy Food</title>
    <link rel="stylesheet" href="/app/assets/css/styles.css">
</head>

<body>
    <div class="page-wrapper">
        <div class="illustration-section">
            <div class="illustration-content">
                <h2>Almost there!</h2>
                <p>Confirm your identity to continue</p>
            </div>
        </div>

        <div class="form-section">
            <div class="login-card">
                <div class="card-header">
                    <?php if ($method === 'email'): ?>
                        <h1>📧 Email Verification</h1>
                        <p>We sent a 6-digit code to <strong>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </strong></p>
                    <?php else: ?>
                        <h1>🔐 Authenticator Code</h1>
                        <p>Open your authenticator app and enter the current code</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($resent)): ?>
                    <span class="server-success" style="color:#28a745;display:block;text-align:center;margin-bottom:12px;">
                        A new code has been sent to your email.
                    </span>
                <?php endif; ?>

                <form class="login-form" method="POST"
                    action="verify_2fa.php?method=<?php echo htmlspecialchars($method); ?>">

                    <div class="form-group">
                        <label for="code">Verification Code</label>
                        <input type="text" id="code" name="code" class="otp-input" maxlength="8"
                            autocomplete="one-time-code" required autofocus>
                        <?php if ($method === 'totp'): ?>
                            <p style="font-size: 0.8rem; color: #64748b; margin-top: 5px;">
                                💡 Tip: You can also use a recovery backup code.
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if ($error): ?>
                        <span class="server-error"
                            style="color:#ff4d4d;display:block;text-align:center;margin-bottom:10px;">
                            <?php echo htmlspecialchars($error); ?>
                        </span>
                    <?php endif; ?>

                    <button type="submit" class="login-btn"><span>Verify</span></button>
                </form>

                <div style="text-align:center;margin-top:16px;font-size:.9rem;color:#94a3b8">
                    <?php if ($method === 'email'): ?>
                        Didn't receive it?
                        <a href="verify_2fa.php?method=email&resend=1" style="color:#6366f1">Resend code</a>
                        &nbsp;·&nbsp;
                    <?php endif; ?>
                    <a href="login.php" style="color:#6366f1">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>