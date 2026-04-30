<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use PermitSales\Controllers\AdminController;
use PermitSales\Controllers\AuthController;
use PermitSales\Controllers\CardController;
use PermitSales\Controllers\DashboardController;
use PermitSales\Controllers\OrderController;
use PermitSales\Controllers\PageController;
use PermitSales\Controllers\VehicleController;
use PermitSales\Router;
use PermitSales\View;

$router = new Router();

$page = new PageController();
$auth = new AuthController();
$dash = new DashboardController();
$vehicles = new VehicleController();
$cards = new CardController();
$orders = new OrderController();
$admin = new AdminController();

$router->get('/', [$page, 'home']);
$router->get('/solutions', [$page, 'solutions']);
$router->get('/fulfillment', [$page, 'fulfillment']);
$router->get('/management', [$page, 'management']);
$router->get('/enforcement', [$page, 'enforcement']);
$router->get('/contact', [$page, 'contact']);
$router->get('/day-pass', [$page, 'dayPass']);

$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);
$router->get('/register', [$auth, 'showRegister']);
$router->post('/register', [$auth, 'register']);
$router->post('/logout', [$auth, 'logout']);

$router->get('/dashboard', [$dash, 'index']);

$router->post('/vehicles', [$vehicles, 'create']);
$router->post('/vehicles/{id}/delete', [$vehicles, 'delete']);

$router->post('/cards', [$cards, 'create']);
$router->post('/cards/{id}/delete', [$cards, 'delete']);
$router->post('/cards/{id}/default', [$cards, 'setDefault']);

$router->post('/orders', [$orders, 'create']);

$router->get('/admin', [$admin, 'index']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (\Throwable $e) {
    http_response_code(500);
    if (PermitSales\Env::get('APP_ENV', 'dev') === 'dev') {
        header('Content-Type: text/plain');
        echo $e->getMessage() . "\n\n";
        // Surface .env loader state on every dev-mode error. This is the
        // single most common source of "the app won't start" reports on
        // IIS, where it is not always obvious which .env (if any) the
        // FastCGI worker actually picked up.
        echo PermitSales\Env::diagnostics() . "\n\n";
        echo $e->getTraceAsString();
        return;
    }
    View::render('pages/error', ['title' => 'Something went wrong']);
}
