<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(1);

echo "User: " . $user->name . PHP_EOL;
echo "Email: " . $user->email . PHP_EOL;
echo "Role ID: " . $user->role_id . PHP_EOL;
echo "Role: " . ($user->role ? $user->role->name : 'NULL') . PHP_EOL;
echo "Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . PHP_EOL;
echo PHP_EOL;

// Check all users
echo "All Users:" . PHP_EOL;
$users = App\Models\User::all();
foreach ($users as $u) {
    echo "  - {$u->name} (ID: {$u->id}, Role: " . ($u->role ? $u->role->name : 'NULL') . ")" . PHP_EOL;
}

echo PHP_EOL;
echo "All Roles:" . PHP_EOL;
$roles = App\Models\Role::all();
foreach ($roles as $r) {
    echo "  - {$r->name} (ID: {$r->id})" . PHP_EOL;
}
