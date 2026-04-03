<?php

// Initialize cURL
$ch = curl_init();

// Get login page for CSRF
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => __DIR__ . '/cookies.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/cookies.txt',
]);
$loginPage = curl_exec($ch);

// Extract CSRF
preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"\s*\/>/i', $loginPage, $metaMatches);
preg_match('/name="_token"[\s\S]*?value="([^"]+)"/i', $loginPage, $inputMatches);
$csrf = $metaMatches[1] ?? ($inputMatches[1] ?? '');
echo "CSRF: {$csrf}\n";

// Login POST
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $csrf,
        'login' => 'admin@smk.sch.id',
        'password' => 'password',
    ]),
    CURLOPT_FOLLOWLOCATION => true,
]);

$dashboard = curl_exec($ch);
echo "Contains sidebar: " . (strpos($dashboard, 'data-flux-sidebar') !== false ? 'YES' : 'NO') . "\n";
echo "Contains ui-disclosure: " . (strpos($dashboard, 'ui-disclosure') !== false ? 'YES' : 'NO') . "\n";
echo "Length: " . strlen($dashboard) . "\n";

// Save dashboard HTML
file_put_contents(__DIR__ . '/dashboard_auth.html', $dashboard);
echo "Saved to dashboard_auth.html\n";

curl_close($ch);
