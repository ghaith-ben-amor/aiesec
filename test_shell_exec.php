<?php
declare(strict_types=1);

require 'config/bootstrap.php';

$path = 'uploads/cv_69f4f1896ef126.35033781.pdf';
$command = escapeshellarg(config()['python_bin']) . ' ' . escapeshellarg(PYTHON_PATH . '/parse_cv.py') . ' ' . escapeshellarg($path) . ' 2>&1';

echo "Command: $command\n";
echo "=== Raw Output ===\n";
$output = shell_exec($command);
echo $output;
echo "\n=== End Output ===\n\n";

$lines = explode("\n", (string) $output);
echo "Lines: " . count($lines) . "\n";
foreach ($lines as $i => $line) {
    echo "Line $i: " . json_encode($line) . "\n";
    if ($line && trim($line) && $line[0] === '{') {
        echo "  -> Found JSON start at line $i\n";
    }
}
