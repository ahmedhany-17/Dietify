<?php
// ─────────────────────────────────────────────────────────────────────────────
// config.php  –  Single entry point for the entire application.
// Merges the old init.php + includes/db_connect.php into one place.
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/vendor/autoload.php';

// ── Environment variables (.env) ─────────────────────────────────────────────
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// ── Database connection ───────────────────────────────────────────────────────
$conn = new mysqli('localhost', 'root', '', 'healthyfood');


//i need to check this for logs 
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("A database error occurred. Please try again later.");
}

// Enforce UTC for both PHP and MySQL to prevent timezone sync issues
// note TOTP use unixtimestamp so it doesn't matter but it does affect 2FA email
date_default_timezone_set('UTC');
$conn->query("SET time_zone = '+00:00'");

// ── Automatic Order Status Transitions ───────────────────────────────────────
// 1. Auto-cancel pending orders after 10 minutes
$conn->query("UPDATE orders SET status = 'cancelled' WHERE status = 'pending' AND created_at < NOW() - INTERVAL 10 MINUTE");

// 2. Auto-transition paid orders to 'shipped' after 20 minutes (Locks cancellation)
$conn->query("
    UPDATE orders o
    JOIN payments p ON o.id = p.order_id
    SET o.status = 'shipped'
    WHERE o.status = 'paid' 
    AND p.status = 'completed'
    AND p.paid_at < NOW() - INTERVAL 20 MINUTE
");

// 3. Auto-complete shipped orders after 30 minutes (marked as 'delivered')
$conn->query("
    UPDATE orders o
    JOIN payments p ON o.id = p.order_id
    SET o.status = 'delivered'
    WHERE o.status = 'shipped' 
    AND p.status = 'completed'
    AND p.paid_at < NOW() - INTERVAL 30 MINUTE
");

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 604800, // 7 days
        'path' => '/',
        'httponly' => true
    ]);
    session_start();
}

// ── Global constants ──────────────────────────────────────────────────────────
define('SITE_NAME', $_ENV['SMTP_NAME'] ?? 'Dietify');
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');

// CURRENCY_CODE: Standard 3-letter ISO code required by payment gateways like Stripe (e.g. 'EGP')
// CURRENCY_SYMBOL: The visual symbol displayed to users on the frontend
define('CURRENCY_CODE', 'EGP');
define('CURRENCY_SYMBOL', 'EGP ');

// Base URL for Stripe and absolute redirects (from .env)
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/app/');

// ── Helper functions ──────────────────────────────────────────────────────────

/** Extracts direct image URL from Google imgres links, otherwise returns original. */
function getImageUrl($url)
{
    if (empty($url))
        return '';
    if (strpos($url, 'google.com/imgres') !== false) {
        $components = parse_url($url);
        if (isset($components['query'])) {
            parse_str($components['query'], $query);
            if (isset($query['imgurl']))
                return urldecode($query['imgurl']);
        }
    }
    return $url;
}

/** Returns true if the current user has the admin role. */
function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/** Returns true if a user is logged in. */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/** Returns the total number of items in the current user's cart. */
function getCartCount(mysqli $conn): int
{
    if (!isLoggedIn())
        return 0;

    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare(
        "SELECT SUM(ci.quantity) AS total
         FROM cart_items ci
         JOIN carts c ON ci.cart_id = c.id
         WHERE c.user_id = ?"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int) ($result['total'] ?? 0);
}




/**
 * Checks whether a given password has been found in known data breaches
 * using the :contentReference[oaicite:0]{index=0}.
 *
 * How it works:
 * - The password is hashed using :contentReference[oaicite:1]{index=1}.
 * - The hash is converted to uppercase and split into:
 *   - a 5-character prefix
 *   - the remaining suffix
 * - Only the prefix is sent to the API (k-anonymity), so the full hash
 *   and password are never exposed.
 * - The API returns a list of matching suffixes with breach counts.
 * - The function checks if the suffix exists in the response:
 *   - If found and count > 0 → password is compromised (returns true)
 *   - Otherwise → password not found (returns false)
 *
 * Notes:
 * - Communication is done over HTTPS (TLS), ensuring secure transmission.
 * - No plaintext password is ever sent over the network.
 */


/** Checks if a password exists in a data breach using the HIBP Pwned Passwords API. */
function isPasswordPwned($password): bool
{
    $hash = strtoupper(sha1($password));
    $prefix = substr($hash, 0, 5);
    $suffix = substr($hash, 5);
    //so i curl use tls by it self as docs say for ihavebeenpwnd
    $url = "https://api.pwnedpasswords.com/range/" . $prefix;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    // Use IPv4 and bypass SSL for stability in local dev if needed, 
    // but better keep defaults for security unless issues arise.
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return false;
    }

    $lines = explode("\n", $response);
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($matchedSuffix, $count) = explode(':', trim($line));
            if ($matchedSuffix === $suffix) {
                return (int) $count > 0;
            }
        }
    }

    return false;
}
?>