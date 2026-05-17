<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

$router = new Router();
$router->get('/', [OpportunityController::class, 'home']);
$router->get('/upload', [OpportunityController::class, 'create']);
$router->post('/upload', [OpportunityController::class, 'store']);
$router->get('/results', [OpportunityController::class, 'results']);
$router->get('/dashboard', [OpportunityController::class, 'dashboard']);

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$basePath = base_url();
if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
	$requestPath = substr($requestPath, strlen($basePath));
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $requestPath ?: '/');
