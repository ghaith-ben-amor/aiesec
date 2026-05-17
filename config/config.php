<?php
declare(strict_types=1);

return [
    'app_name' => 'AIESEC Opportunity Matcher',
    'base_url' => base_url(),
    'python_bin' => getenv('PYTHON_BIN') ?: ((defined('BASE_PATH') && is_file(BASE_PATH . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe')) ? BASE_PATH . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe' : 'python'),
    'groq_api_key' => getenv('GROQ_API_KEY') ?: '',
    'max_upload_size' => 8 * 1024 * 1024,
    'allowed_upload_types' => ['application/pdf'],
];
