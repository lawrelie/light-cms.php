<?php
set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline): bool {
    if (error_reporting() & $errno) {
        throw new ErrorException($errstr, severity: $errno, filename: $errfile, line: $errline);
    }
    return true;
});
spl_autoload_register(function(string $className): void {
    $filename = sprintf('%s/src/%s.php', __DIR__, strtr($className, '\\', '/'));
    if (file_exists($filename)) {
        require_once $filename;
    }
});
