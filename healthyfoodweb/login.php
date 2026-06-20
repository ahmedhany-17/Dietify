<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/RateLimiter.php';

$error = '';
$success = '';
$identity = '';

if (check_rate_limit($_SERVER['REMOTE_ADDR'])) {
    //when i did the rate limit it did't return code 429 so that why i add it http_response_code
    http_response_code(429);
    exit("Too many login attempts. Please try again later.");
}

// Note: Logout check moved to logout.php
// when this code get get request with registered=1 it will show success message
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registration successful! Please sign in.";
}
if (isset($_GET['verify_sent']) && $_GET['verify_sent'] == 1) {
    $success = "Account created successfully! Please check your email to verify your account before signing in.";
}

// when this code get get request with reset=1 it will show success message
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    $success = "Password successfully reset! Please sign in.";
}
//if server get post from login form it will be sent to server to process it
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

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

    if (empty($identity) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (empty($turnstile_response) || !$response_data->success || ($response_data->action ?? '') !== 'login') {
        $error = "Please complete the security check.";
    } else {
        //prepare statment to prevent sql injection (: i will test with sqlmap later on 
        //TO DO prepared sql stmt you have The query must consist of a single SQL statement
        $stmt = $conn->prepare(
            "SELECT id, username, email, password, twofa_method, role, is_verified
             FROM users WHERE email = ? OR username = ?"
        );
        //bind_param "ss" means two strings for the OR condition
        $stmt->bind_param("ss", $identity, $identity);
        //execute the prepared statement (: nothing new
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {

                if ($row['is_verified'] == 0) {
                    $error = "Please verify your email address before logging in.";
                } else {
                    // ── 2FA check ──────────────────────────────────────────────
                    if ($row['twofa_method'] === 'email') {
                        // Generate & email OTP
                        require_once __DIR__ . '/includes/send_otp_email.php';
                        try {
                            sendOtpEmail($conn, (int) $row['id'], $row['email'], $row['username']);
                            $_SESSION['2fa_user_id'] = $row['id'];
                            header("Location: /app/verify_2fa.php?method=email");
                            exit();
                        } catch (Exception $e) {
                            $error = "Failed to send OTP email. Please try again.";
                        }

                    } elseif ($row['twofa_method'] === 'totp') {
                        $_SESSION['2fa_user_id'] = $row['id'];
                        header("Location: verify_2fa.php?method=totp");
                        exit();

                    } else {
                        // No 2FA – log in directly
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['role'] = $row['role'];

                        // If user is admin, redirect to admin dashboard
                        if ($row['role'] === 'admin') {
                            header("Location: admin/AdminPanel.php");
                        } else {
                            header("Location: profile.php");
                        }
                        exit();
                    }
                    // ──────────────────────────────────────────────────────────
                }

            } else {
                $error = "Invalid password.";

                // Track failure for this identity
                $failCount = record_login_failure($identity);
                if ($failCount >= 25) {
                    require_once __DIR__ . '/includes/send_otp_email.php';
                    sendSecurityAlertEmail((string) $row['email'], (string) $row['username']);
                }
            }
        } else {
            $error = "User not found.";
            // Note: We don't have an email to send to if user doesn't exist,
            // but we could still record the failure if we want to track attempts against non-existent users but that feel stupid 

            record_login_failure($identity);
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
    <title>Login - Healthy Food</title>
    <link rel="stylesheet" href="/app/assets/css/styles.css">
    <style>
        .server-error {
            color: #ff4d4d;
            font-size: 0.9rem;
            margin-top: 10px;
            text-align: center;
            display: block;
        }

        .server-success {
            color: #28a745;
            font-size: 0.9rem;
            margin-top: 10px;
            text-align: center;
            display: block;
        }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>

<body>
    <div class="page-wrapper">
        <div class="illustration-section">
            <div class="illustration-content">
                <h2>Welcome Back!</h2>
                <p>Sign in to continue your healthy journey</p>
            </div>
        </div>
        <div class="form-section">
            <div class="login-card">
                <div class="card-header">
                    <h1>Sign In</h1>
                    <p>Sign in to your account</p>
                </div>

                <form class="login-form" method="POST" action="login.php">
                    <div class="form-group">
                        <label for="identity">Email or Username</label>
                        <input type="text" id="identity" name="identity"
                            value="<?php echo htmlspecialchars($identity); ?>" required>
                        <span class="error-message" id="identityError"></span>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" required style="padding-right: 60px;">
                            <button type="button" onclick="togglePassword('password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #ff6b35; font-weight: 600; font-size: 0.85rem;">Show</button>
                        </div>
                        <span class="error-message" id="passwordError"></span>
                    </div>

                    <?php if ($success): ?>
                        <span class="server-success"><?php echo $success; ?></span>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <span class="server-error"><?php echo htmlspecialchars($error); ?></span>
                    <?php endif; ?>

                    <div class="form-options">
                        <!-- Remember Me feature (currently disabled)
                        <label class="checkbox-container">
                            <input type="checkbox" id="rememberMe" name="rememberMe">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        -->
                        <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <div class="cf-turnstile"
                            data-sitekey="<?php echo htmlspecialchars($_ENV['TURNSTILE_SITE_KEY'] ?? ''); ?>"
                            data-action="login"></div>
                    </div>

                    <button type="submit" class="login-btn"><span>Sign In</span></button>

                    <div class="signup-link">
                        <p>Don't have an account? <a href="register.php">Sign up here</a></p>
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