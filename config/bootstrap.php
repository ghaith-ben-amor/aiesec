<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'app');
define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('PYTHON_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'python');
define('DB_SCHEMA_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sql');

if (session_status() === PHP_SESSION_NONE) {
	$sessionPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'sessions';
	if (!is_dir($sessionPath)) {
		@mkdir($sessionPath, 0775, true);
	}
	if (is_dir($sessionPath) && is_writable($sessionPath)) {
		session_save_path($sessionPath);
	}
	session_start();
}

load_env(BASE_PATH . DIRECTORY_SEPARATOR . '.env');

function base_url(): string
{
	if (PHP_SAPI === 'cli') {
		return '';
	}

	$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
	return $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
}

function url_path(string $path = ''): string
{
	$path = '/' . ltrim($path, '/');
	return base_url() . ($path === '/' ? '' : $path);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/../controllers/BaseController.php';
require_once __DIR__ . '/../controllers/OpportunityController.php';
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Cv.php';
require_once __DIR__ . '/../models/Opportunity.php';
require_once __DIR__ . '/../models/MatchResult.php';

function load_env(string $path): void
{
	if (!is_file($path)) {
		return;
	}

	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines === false) {
		return;
	}

	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
			continue;
		}

		[$name, $value] = explode('=', $line, 2);
		$name = trim($name);
		$value = trim($value, " \t\n\r\0\x0B\"");
		if ($name !== '') {
			putenv($name . '=' . $value);
			$_ENV[$name] = $value;
		}
	}
}
