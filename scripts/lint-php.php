<?php
declare(strict_types=1);

$roots = ['app', 'bootstrap', 'database', 'routes'];
$failed = 0;

foreach ($roots as $root) {
    if (! is_dir($root)) {
        fwrite(STDERR, "Missing directory: {$root}\n");
        exit(2);
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        passthru('php -l ' . escapeshellarg($path), $code);
        if ($code !== 0) {
            $failed++;
        }
    }
}

exit($failed > 0 ? 1 : 0);
