<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Router;

$config = require __DIR__ . '/config/settings.php';

// Ensure content directory exists
if (!is_dir($config['base_dir'])) {
    mkdir($config['base_dir'], 0755, true);
}

$router = new Router($config);
$router->dispatch();
