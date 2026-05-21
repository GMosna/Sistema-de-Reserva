<?php
define('ROOT_PATH',    dirname(__DIR__));
define('APP_PATH',     ROOT_PATH . '/app');
define('VIEWS_PATH',   ROOT_PATH . '/views');
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// PSR-4 autoloader — namespace App\ maps to app/
spl_autoload_register(function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = APP_PATH . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Error handler — log to storage/logs/app.log
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    $msg = sprintf("[%s] ERROR %d: %s in %s:%d\n",
        date('Y-m-d H:i:s'), $errno, $errstr, $errfile, $errline);
    error_log($msg, 3, STORAGE_PATH . '/logs/app.log');
    if (in_array($errno, [E_ERROR, E_USER_ERROR], true)) {
        http_response_code(500);
        include VIEWS_PATH . '/errors/500.php';
        exit(1);
    }
    return true;
});

set_exception_handler(function (\Throwable $e): void {
    $msg = sprintf("[%s] EXCEPTION: %s in %s:%d\n%s\n",
        date('Y-m-d H:i:s'), $e->getMessage(), $e->getFile(), $e->getLine(),
        $e->getTraceAsString());
    error_log($msg, 3, STORAGE_PATH . '/logs/app.log');
    http_response_code(500);
    include VIEWS_PATH . '/errors/500.php';
    exit(1);
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP_PATH . '/Helpers/helpers.php';
require_once CONFIG_PATH . '/conexao.php';

// Register global $conn with the ORM
\App\Database::connect($conn);
