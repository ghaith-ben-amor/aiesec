<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

$router = new Router();
$router->get('/', [AuthController::class, 'landing']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/signup', [AuthController::class, 'signupForm']);
$router->post('/signup', [AuthController::class, 'signup']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->post('/admin/sync-opportunities', [AdminController::class, 'syncOpportunities']);
$router->get('/admin/logout', [AdminController::class, 'logout']);
$router->get('/ep-management', [EpController::class, 'dashboard']);
$router->post('/ep-management', [EpController::class, 'store']);
$router->post('/ep-management/update', [EpController::class, 'update']);
$router->post('/ep-management/status', [EpController::class, 'updateStatus']);
$router->post('/ep-management/document', [EpController::class, 'uploadDocument']);
$router->get('/ep-management/download', [EpController::class, 'downloadFolder']);
$router->get('/ep-management/status-data', [EpController::class, 'statusData']);
$router->get('/home', [OpportunityController::class, 'home']);
$router->get('/cv-builder', [OpportunityController::class, 'cvBuilder']);
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
