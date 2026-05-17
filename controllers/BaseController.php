<?php
declare(strict_types=1);

abstract class BaseController
{
    protected function view(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/views/layouts/header.php';
        require BASE_PATH . '/views/' . $template . '.php';
        require BASE_PATH . '/views/layouts/footer.php';
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url_path($path));
        exit;
    }

    protected function request(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
