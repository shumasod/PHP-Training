<?php

require_once __DIR__ . '/vendor/autoload.php';


use App\Controllers\TestController;

$app = new TestController;
$app->run();

use Carbon\Carbon;