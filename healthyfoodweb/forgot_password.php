<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/send_otp_email.php';

$error = '';
$success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    // Cloudflare Turnstile Verification
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    $turnstile_secret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';

    $data = array(
        'secret' => $turnstile_secret,
        'response' => $turnstile_response
    );
    $verify = curl_init();
    curl_setopt($verify, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
    curl_setopt($verify, CURLOPT_POST, true);
    curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($verify);
    $response_data = json_decode($response);

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (empty($turnstile_response) || !$response_data->success || ($response_data->action ?? '') !== 'forgot_password') {
        $error = "Please complete the security check.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            try {
                sendOtpEmail($conn, (int) $row['id'], $email, $row['username'], 'password_reset');
                session_start();
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_user_id'] = $row['id'];
                header("Location: reset_password.php");
                exit();
            } catch (Exception $e) {
                $error = "Failed to send reset email. Please try again.";
            }
        } else {
            // For security, don't reveal if the email exists or not that would be good why to enumarete users
            $error = "If that email matches an account, you'll receive a code shortly.";
            $success = "If that email is in our system, we've sent a 6-digit code.";
            $error = '';
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Healthy Food</title>
    <link rel="stylesheet" href="/app/assets/css/styles.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>

<body>
    <div class="page-wrapper">
        <div class="illustration-section">
            <div class="illustration-content">
                <h2>Forgot Password?</h2>
                <p>No worries, it happens to the best of us.</p>
            </div>
        </div>
        <div class="form-section">
            <div class="login-card">
                <div class="card-header">
                    <h1>Reset Password</h1>
                    <p>Enter your email to receive a reset code</p>
                </div>

                <form class="login-form" method="POST" action="forgot_password.php">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required autofocus>
                    </div>

                    <?php if ($success): ?>
                        <span class="server-success"
                            style="color:#28a745;text-align:center;display:block;margin-bottom:15px;">
                            <?php echo $success; ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <span class="server-error"
                            style="color:#ff4d4d;text-align:center;display:block;margin-bottom:15px;">
                            <?php echo htmlspecialchars($error); ?>
                        </span>
                    <?php endif; ?>

                    <div style="margin-bottom: 20px;">
                        <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($_ENV['TURNSTILE_SITE_KEY'] ?? ''); ?>" data-action="forgot_password"></div>
                    </div>

                    <button type="submit" class="login-btn"><span>Send Reset Code</span></button>

                    <div class="signup-link">
                        <p><a href="login.php">Back to Sign In</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>