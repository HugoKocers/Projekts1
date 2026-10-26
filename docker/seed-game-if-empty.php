<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::table('scenarios')->doesntExist() && DB::table('users')->doesntExist()) {
    Artisan::call('db:seed', ['--force' => true]);
    echo Artisan::output();
}
