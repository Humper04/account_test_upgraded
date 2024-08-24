<?php
function getUserIP() {
    $ipKeys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    $foundIPs = []; // Store found IPs for logging
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER)) {
            $ips = explode(',', $_SERVER[$key]);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                $foundIPs[$key][] = $ip; // Log all IPs found
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }

    // Log all collected IP data for debugging
    file_put_contents('ip_debug.log', date('Y-m-d H:i:s') . " - IPs checked: " . json_encode($foundIPs) . "\n", FILE_APPEND);
    
    return 'UNKNOWN';
}

$ipAddress = getUserIP();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Test</title>
</head>
<body>
    <h1>IP Test Result</h1>
    <p>Your IP Address: <?= htmlspecialchars($ipAddress) ?></p>
    <h2>Raw IP Data Logged</h2>
    <p>Check the <code>ip_debug.log</code> file for detailed IP data.</p>
</body>
</html>
