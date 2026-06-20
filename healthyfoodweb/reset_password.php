<?php
require_once __DIR__ . '/config.php';

if (empty($_SESSION['reset_email']) || empty($_SESSION['reset_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$userId = (int) $_SESSION['reset_user_id'];
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['code'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($code) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 12) {
        $error = "Password must be at least 12 characters.";
    } elseif (isPasswordPwned($newPassword)) {
        $error = "This password has been found in a data breach. Please choose a more secure password.";
    } else {
        // Verify OTP
        $redis = new Predis\Client();
        $otpHash = $redis->get("otp:$userId:password_reset");

        if ($otpHash && password_verify($code, $otpHash)) {
            // Update Password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $hashedPassword, $userId);

            if ($upd->execute()) {
                // Delete OTP from Redis
                $redis->del("otp:$userId:password_reset");

                // Clear session and redirect
                session_destroy();
                header("Location: login.php?reset=1");
                exit();
            } else {
                $error = "Failed to update password. Please try again.";
            }
            $upd->close();
        } else {
            $error = "Invalid or expired code.";
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Healthy Food</title>
    <link rel="stylesheet" href="/app/assets/css/styles.css">
</head>

<body>
    <div class="page-wrapper">
        <div class="illustration-section">
            <div class="illustration-content">
                <h2>New Password</h2>
                <p>Verify your code and choose a strong password.</p>
            </div>
        </div>
        <div class="form-section">
            <div class="login-card">
                <div class="card-header">
                    <h1>Set New Password</h1>
                    <p>Code sent to: <strong>
                            <?php echo htmlspecialchars($email); ?>
                        </strong></p>
                </div>

                <form class="login-form" method="POST" action="reset_password.php">
                    <div class="form-group">
                        <label for="code">6-Digit Code</label>
                        <input type="text" id="code" name="code" maxlength="6" pattern="\d{6}" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div style="position: relative;">
                            <input type="password" id="new_password" name="new_password" minlength="12" required style="padding-right: 60px;">
                            <button type="button" onclick="togglePassword('new_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #ff6b35; font-weight: 600; font-size: 0.85rem;">Show</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div style="position: relative;">
                            <input type="password" id="confirm_password" name="confirm_password" minlength="12" required style="padding-right: 60px;">
                            <button type="button" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #ff6b35; font-weight: 600; font-size: 0.85rem;">Show</button>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <span class="server-error"
                            style="color:#ff4d4d;text-align:center;display:block;margin-bottom:15px;">
                            <?php echo htmlspecialchars($error); ?>
                        </span>
                    <?php endif; ?>

                    <button type="submit" class="login-btn"><span>Reset Password</span></button>

                    <div class="signup-link">
                        <p><a href="forgot_password.php">Didn't get a code?</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Hide';
            } else {
                input.type = 'password';
                btn.textContent = 'Show';
            }
        }
    </script>
</body>

</html>