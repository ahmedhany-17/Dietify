<?php
require_once __DIR__ . '/config.php';

$message = '';
$isSuccess = false;

if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = strtolower($_GET['email']);

    $redis = new Predis\Client();
    $storedToken = $redis->get("email_verification:$email");

    if ($storedToken && hash_equals($storedToken, $token)) {
        // Valid token, update database
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ? AND is_verified = 0");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $isSuccess = true;
            $message = "Your email has been successfully verified! You can now log in.";

            // Delete the token immediately so it can't be reused
            $redis->del("email_verification:$email");
        } else {
            // Check if they were already verified
            $checkStmt = $conn->prepare("SELECT is_verified FROM users WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result()->fetch_assoc();

            if ($result && $result['is_verified'] == 1) {
                $isSuccess = true;
                $message = "Your email is already verified. You can log in.";
                // Clean up redis anyway
                $redis->del("email_verification:$email");
            } else {
                $message = "Failed to update verification status. Please contact support.";
            }
        }
    } else {
        $message = "Invalid or expired verification link. Please register again or request a new link.";
    }
} else {
    $message = "Invalid request. Missing token or email.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Healthy Food</title>
    <link rel="stylesheet" href="/app/assets/css/styles.css">
    <style>
        .verification-container {
            max-width: 500px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 20px;
            color: white;
        }

        .success-icon {
            background: #27ae60;
        }

        .error-icon {
            background: #e74c3c;
        }
    </style>
</head>

<body style="background: #f0f2f5;">
    <div class="verification-container">
        <div class="icon-circle <?php echo $isSuccess ? 'success-icon' : 'error-icon'; ?>">
            <?php echo $isSuccess ? '✓' : '✗'; ?>
        </div>

        <h2 style="margin-bottom: 15px; color: #333;">
            <?php echo $isSuccess ? 'Verification Successful' : 'Verification Failed'; ?>
        </h2>

        <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px; line-height: 1.6;">
            <?php echo htmlspecialchars($message); ?>
        </p>

        <a href="login.php" class="login-btn" style="display: inline-block; text-decoration: none; max-width: 200px;">
            Go to Login
        </a>
    </div>
</body>

</html>