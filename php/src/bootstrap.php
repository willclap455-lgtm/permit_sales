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

// Look for the .env file in several sensible locations. The first one that
// exists wins. This matters on IIS, where the "current working directory"
// of the FastCGI worker is not necessarily the project root, and where
// operators sometimes drop .env next to public/index.php by accident.
$projectRoot = dirname(__DIR__);
$envCandidates = [
    $projectRoot . '/.env',
    $projectRoot . '/public/.env',
    $projectRoot . '/../.env',
];
if (($explicit = getenv('PERMITSALES_ENV_FILE')) !== false && $explicit !== '') {
    array_unshift($envCandidates, $explicit);
}
Env::load($envCandidates);

Session::start();
date_default_timezone_set('UTC');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
