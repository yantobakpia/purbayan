<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Fix untuk OpenSSL di Windows (XAMPP) agar library WebPush (Minishlink) bisa buat key
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && empty(getenv('OPENSSL_CONF'))) {
    $xamppOpenSslConf = 'C:\xampp\apache\conf\openssl.cnf';
    if (file_exists($xamppOpenSslConf)) {
        putenv("OPENSSL_CONF={$xamppOpenSslConf}");
    }
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
