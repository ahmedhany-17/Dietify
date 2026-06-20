<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables from ROOT directory
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Email Configuration from Environment becuse i fear i might get ban or something pls setup your gmail for it to work
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.example.com');
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_PORT', (int) ($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM'] ?? 'no-reply@example.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_NAME'] ?? 'App Name');

/**
 * Internal helper to initialize a configured PHPMailer instance.
 * that did save about 30 lines of code
 * so i don't repate the same code in every function what will change is just the content of the email
 */
function initMailer(string $toEmail, string $toName, string $subject): PHPMailer
{
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = SMTP_HOST;
  $mail->SMTPAuth = true;
  $mail->Username = SMTP_USER;
  $mail->Password = SMTP_PASS;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = SMTP_PORT;
  $mail->CharSet = 'UTF-8';

  $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
  $mail->addAddress($toEmail, $toName);
  $mail->isHTML(true);
  $mail->Subject = $subject;

  return $mail;
}

function sendOtpEmail(mysqli $conn, int $userId, string $toEmail, string $toName, string $purpose = 'twofa'): bool
{
  $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $otpHash = password_hash($otp, PASSWORD_BCRYPT);

  $redis = new Predis\Client();
  $redis->setex("otp:$userId:$purpose", 600, $otpHash);

  // Customize content based on purpose
  $title = '🔐 Verification Code';
  $leadText = 'Your one-time login code is:';

  if ($purpose === 'password_reset') {
    $subject = 'Password Reset Verification Code';
    $title = '🔄 Password Reset';
    $leadText = 'Your password reset code is:';
  } else {
    // Default to twofa subject
    $subject = 'Your Login Verification Code';
  }

  $mail = initMailer($toEmail, $toName, $subject);

  $mail->Body = "
<div style='font-family:Inter,sans-serif;max-width:420px;margin:auto;
                     background:#ffffff;color:#333333;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid #eee;'>
  <div style='background:linear-gradient(135deg,#ff8a5c,#ff6b35);padding:28px 32px;color:white;'>
    <h2 style='margin:0;font-size:1.4rem'>$title</h2>
  </div>
  <div style='padding:28px 32px'>
    <p>Hi <strong>" . htmlspecialchars($toName) . "</strong>,</p>
    <p>$leadText</p>
    <div style='font-size:2.4rem;font-weight:700;letter-spacing:10px;
                        text-align:center;background:#fff9f6;border-radius:8px;
                        padding:18px;margin:20px 0;color:#ff6b35;border:2px dashed #ff6b35;'>$otp</div>
    <p style='font-size:.85rem;color:#888'>
      This code expires in <strong>10 minutes</strong>.<br>
      If you did not request this, please ignore this email.
    </p>
  </div>
</div>";
  $mail->AltBody = "$subject: $otp (expires in 10 minutes)";

  return $mail->send();
}

function sendSecurityAlertEmail(string $toEmail, string $toName): bool
{
  $mail = initMailer($toEmail, $toName, 'Security Alert: Multiple Failed Login Attempts');

  $mail->Body = "
<div style='font-family:Inter,sans-serif;max-width:420px;margin:auto;
                     background:#ffffff;color:#333333;border-radius:12px;overflow:hidden;border:1px solid #fecaca;box-shadow:0 4px 15px rgba(0,0,0,0.05);'>
  <div style='background:#ef4444;padding:28px 32px;text-align:center;color:white;'>
    <h2 style='margin:0;font-size:1.4rem'>⚠️ Security Alert</h2>
  </div>
  <div style='padding:28px 32px'>
    <p>Hi <strong>" . htmlspecialchars($toName) . "</strong>,</p>
    <p>We noticed over <strong>25 failed login attempts</strong> for your account in the last hour.</p>
    <div style='background:#fef2f2;border-radius:8px;padding:15px;margin:20px 0;text-align:center;color:#b91c1c;border:1px solid #fecaca;'>
      If this wasn't you, your account might be under a brute-force attack.
    </div>
    <p style='font-size:.85rem;color:#888'>
      Your account is still safe, but we recommend ensuring you have 2FA enabled and using a strong, unique password.
    </p>
  </div>
</div>";
  $mail->AltBody = "Security Alert: We noticed over 25 failed login attempts for your account in the last hour.";

  try {
    return $mail->send();
  } catch (Exception $e) {
    error_log("Security Alert Email Error: " . $e->getMessage());
    return false;
  }
}

function sendBackupCodesEmail(string $toEmail, string $toName, array $codes): bool
{
  $mail = initMailer($toEmail, $toName, 'Your Backup Codes for 2FA TOTP');

  // Build list safely
  $codesList = '';
  foreach ($codes as $code) {
    $codesList .= '<li>' . htmlspecialchars($code) . '</li>';
  }

  $mail->Body = "
<div style='font-family:Inter,sans-serif;max-width:420px;margin:auto;
                     background:#ffffff;color:#333333;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid #eee;'>
  <div style='background:linear-gradient(135deg,#ff8a5c,#ff6b35);padding:28px 32px;color:white;'>
    <h2 style='margin:0;font-size:1.4rem'>🛡️ Backup Codes</h2>
  </div>
  <div style='padding:28px 32px'>
    <p>Hi <strong>" . htmlspecialchars($toName) . "</strong>,</p>
    <p>Here are your backup recovery codes for <strong>2FA TOTP</strong>:</p>
    <div style='background:#fff9f6;border-radius:8px;padding:20px;margin:20px 0;color:#ff6b35;font-weight:600;font-size:1.1rem;'>
      <ul style='margin:0;padding-left:20px;'>
        $codesList
      </ul>
    </div>
    <p style='font-size:.85rem;color:#888'><strong>Note:</strong> Each code can only be used once.</p>
  </div>
</div>";

  $mail->AltBody = "Your backup codes: " . implode(', ', $codes);

  return $mail->send();
}

function sendVerificationEmail(string $toEmail, string $toName, string $token): bool
{
  $subject = 'Verify Your Email Address';
  $mail = initMailer($toEmail, $toName, $subject);

  $verificationLink = APP_URL . "verify_email.php?email=" . urlencode($toEmail) . "&token=" . urlencode($token);

  $mail->Body = "
<div style='font-family:Inter,sans-serif;max-width:420px;margin:auto;
                     background:#ffffff;color:#333333;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid #eee;'>
  <div style='background:linear-gradient(135deg,#27ae60,#2ecc71);padding:28px 32px;color:white;'>
    <h2 style='margin:0;font-size:1.4rem'>📧 Verify Email</h2>
  </div>
  <div style='padding:28px 32px'>
    <p>Hi <strong>" . htmlspecialchars($toName) . "</strong>,</p>
    <p>Welcome to <strong>Healthy Food!</strong> Please verify your email address to activate your account.</p>
    <div style='text-align:center;margin:30px 0;'>
      <a href='$verificationLink' style='background:#ff6b35;color:white;padding:14px 28px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;box-shadow:0 4px 10px rgba(255,107,53,0.3);'>Verify Email Address</a>
    </div>
    <p style='font-size:.85rem;color:#888'>
      This link expires in <strong>1 hour</strong>.<br>
      Or copy this link: <br><a href='$verificationLink' style='color:#ff6b35;word-break:break-all;'>$verificationLink</a>
    </p>
  </div>
</div>";
  $mail->AltBody = "Please verify your email address by visiting this link: $verificationLink (Expires in 1 hour)";

  return $mail->send();
}
?>