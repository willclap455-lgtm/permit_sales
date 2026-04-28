<?php

declare(strict_types=1);

namespace PermitSales;

spl_autoload_register(static function (string $class): void {
    $prefix = 'PermitSales\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $relative = str_replace('\\', '/', $relative);
    $file = __DIR__ . '/' . $relative . '.php';
    if (is_readable($file)) {
        require $file;
    }
});

Env::load(dirname(__DIR__) . '/.env');
Session::start();
date_default_timezone_set('UTC');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
