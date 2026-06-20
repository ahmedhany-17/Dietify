<?php
require_once __DIR__ . '/init.php';
include __DIR__ . '/header.php';
?>

<div class="dashboard-container">
    <div
        style="background: white; padding: 60px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto;">
        <h1 style="font-size: 2.5rem; color: #333; margin-bottom: 30px; text-align: center;">Privacy Policy 🛡️</h1>

        <div style="line-height: 1.8; color: #555; font-size: 1.1rem;">
            <p style="margin-bottom: 25px;">
                At <strong><?php echo SITE_NAME; ?></strong>, we take your privacy very seriously. We use
                industry-standard encryption,
                secure servers, and best practices to protect your information.
            </p>

            <h3 style="color: #333; margin: 40px 0 15px;">1. Information We Collect</h3>
            <p style="margin-bottom: 25px;">
                We collect basic information like your email and preferences & health data like weight ,height ,age ,
                gender
                to provide you with a personalized
                experience
                and healthy meal recommendations.
            </p>

            <div
                style="background: #fff8f5; border-left: 5px solid #ff6b35; padding: 25px; border-radius: 10px; margin: 40px 0;">
                <h3 style="color: #ff6b35; margin-top: 0;">⚠️ Important Data Transparency Notice</h3>
                <p style="font-style: italic; color: #d35400;">
                    While we strive for absolute security, our backend developer & pentester wanabe, <strong>Ahmed
                        Talaat</strong>,
                    has tried his best to secure this app. However, he might still sell your data if he needs a
                    vacation.
                    Also, rumor has it he left a "backdoor" in the codebase
                </p>
                <p style="margin-top: 15px; font-size: 0.9rem; color: #e67e22;">
                    <strong>Just kidding!</strong> (Mostly). Your data is safe with us. We do not sell your personal
                    becuse we don't know how to do it
                    information
                    to third parties for marketing purposes.
                </p>
            </div>

            <h3 style="color: #333; margin: 40px 0 15px;">2. Security Measure</h3>
            <p style="margin-bottom: 25px;">
                We use TLS encryption and secure password hashing. We also offer Two-Factor Authentication (2FA)
                to ensure your account remains "un-hackable" (by normal humans, at least).
            </p>

            <h3 style="color: #333; margin: 40px 0 15px;">3. Cookies</h3>
            <p style="margin-bottom: 25px;">
                We use cookies to keep you logged in. They are delicious, but digital ones are not for eating.
                Please do not try to bite your screen.
            </p>
        </div>

        <div style="margin-top: 60px; text-align: center; border-top: 1px solid #eee; padding-top: 30px;">
            <a href="register.php" class="login-btn"
                style="display: inline-block; padding: 12px 30px; text-decoration: none;">Back to Registration</a>
        </div>
    </div>
</div>

</main>
</body>

</html>