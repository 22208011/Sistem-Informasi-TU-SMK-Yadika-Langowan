<?php

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => __DIR__ . '/cookies-student.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/cookies-student.txt',
]);

$loginPage = curl_exec($ch);
if ($loginPage === false) {
    echo 'GET_LOGIN_FAILED: ' . curl_error($ch) . PHP_EOL;
    exit(1);
}

preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"\s*\/>/i', $loginPage, $metaMatches);
preg_match('/name="_token"[\s\S]*?value="([^"]+)"/i', $loginPage, $inputMatches);

$csrf = $metaMatches[1] ?? ($inputMatches[1] ?? '');

if ($csrf === '') {
    echo "CSRF_NOT_FOUND\n";
    exit(1);
}

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $csrf,
        'login' => '2024001',
        'password' => 'password',
    ]),
    CURLOPT_FOLLOWLOCATION => true,
]);

$response = curl_exec($ch);
if ($response === false) {
    echo 'POST_LOGIN_FAILED: ' . curl_error($ch) . PHP_EOL;
    exit(1);
}

$effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

$hasStudentDashboard = strpos($response, 'Dashboard Siswa') !== false;
$hasSidebar = strpos($response, 'data-flux-sidebar') !== false;

echo 'EFFECTIVE_URL: ' . $effective . PHP_EOL;
echo 'HAS_STUDENT_DASHBOARD_TEXT: ' . ($hasStudentDashboard ? 'YES' : 'NO') . PHP_EOL;
echo 'HAS_SIDEBAR: ' . ($hasSidebar ? 'YES' : 'NO') . PHP_EOL;
echo 'LEN: ' . strlen($response) . PHP_EOL;

file_put_contents(__DIR__ . '/dashboard_student_auth.html', $response);
echo "Saved to dashboard_student_auth.html\n";

curl_close($ch);
