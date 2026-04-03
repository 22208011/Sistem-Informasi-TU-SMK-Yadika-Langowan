<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$row = $app->make('db')->selectOne(
    'select users.name, users.email, students.nis, students.nisn from users inner join students on students.id = users.student_id limit 1'
);

if (! $row) {
    echo "NO_STUDENT_USER\n";
    exit(0);
}

echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
