<?php
//v2 is the same but without the h-captcha
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/send_otp_email.php';

$error = '';
$success = '';
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $terms = isset($_POST['terms']);

    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 12) {
        $error = "Password must be at least 12 characters.";
    } elseif (isPasswordPwned($password)) {
        $error = "This password has been found in a data breach. Please choose a more secure password.";
    } elseif (!$terms) {
        $error = "You must agree to the terms and conditions.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers, and underscores (no @ allowed).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email or username already exists using Prepared Statement
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $checkStmt->bind_param("ss", $email, $username);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            // Insert user using Prepared Statement
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $isVerified = 0; // Default to unverified
            $insertStmt = $conn->prepare("INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("sssi", $username, $email, $hashedPassword, $isVerified);

            if ($insertStmt->execute() === TRUE) {
                // Generate verification token
                $token = bin2hex(random_bytes(32));

                // Store token in Redis with 1-hour expiration
                $redis = new Predis\Client();
                $redis->setex("email_verification:" . strtolower($email), 3600, $token);

                // Send verification email
                try {
                    sendVerificationEmail($email, $username, $token);
                    // Redirect to login page with verification message
                    header("Location: login.php?verify_sent=1");
                    exit();
                } catch (Exception $e) {
                    $error = "Account created, but failed to send verification email. Please contact support.";
                }
            } else {
                $error = "Error: " . $insertStmt->error;
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register V2 - Healthy Food</title>
    <link rel="stylesheet" href="/app/assets/css/styles.css">
    <style>
        .server-error {
            color: #ff4d4d;
            font-size: 0.9rem;
            margin-top: 10px;
            text-align: center;
            display: block;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="illustration-section">
            <div class="illustration-content">
                <h2>Join Us! (V2)</h2>
                <p>Start your healthy journey today without captcha</p>
            </div>
        </div>
        <div class="form-section">
            <div class="register-card">
                <div class="card-header">
                    <h1>Create Account</h1>
                    <p>Join us and start your healthy journey</p>
                </div>

                <form class="register-form" method="POST" action="register_v2.php">


                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username"
                            value="<?php echo htmlspecialchars($username); ?>" required>
                        <span class="error-message" id="usernameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                            required>
                        <span class="error-message" id="emailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <span class="error-message" id="passwordError"></span>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                        <span class="error-message" id="confirmPasswordError"></span>
                    </div>

                    <?php if ($error): ?>
                        <span class="server-error"><?php echo $error; ?></span>
                    <?php endif; ?>

                  

                    <button type="submit" class="register-btn"><span>Create Account</span></button>

                    <div class="login-link">
                        <p>Already have an account? <a href="login.php">Sign in here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
