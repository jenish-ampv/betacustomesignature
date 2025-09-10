<?php
function getUserIP() {
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    $ip_list = [];

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            foreach ($ips as $ip) {
                $ip = trim($ip); // Remove spaces
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ip_list[$key][] = $ip;
                }
            }
        }
    }

    return $ip_list;
}

$all_ips = getUserIP();
echo "<h2>Your IP Information</h2>";
if (empty($all_ips)) {
    echo "No IP address found.";
} else {
    echo "<ul>";
    foreach ($all_ips as $source => $ips) {
        foreach ($ips as $ip) {
            echo "<li><strong>$source:</strong> $ip</li>";
        }
    }
    echo "</ul>";
}
?>
