<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

$employees = DB::table('employees')->limit(5)->get();
foreach ($employees as $e) {
    echo "Number: {$e->number} | Last Date: {$e->last_date} | Balance: {$e->total_balance}\n";
}
