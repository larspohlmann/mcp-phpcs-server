<?php
declare(strict_types = 1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Mcp\\PhpcsServer\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);

    if (0 !== strncmp($prefix, $class, $len)) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (!is_file($file)) {
        return;
    }

    require_once $file;
});
