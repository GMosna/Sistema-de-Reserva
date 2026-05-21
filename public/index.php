<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Detect app base path using __DIR__ (reliable even with mod_rewrite)
$docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$pubDir     = rtrim(str_replace('\\', '/', __DIR__), '/');
$assetsBase = '/' . ltrim(substr($pubDir, strlen($docRoot)), '/'); // /reserva-salas/public
$appBase    = rtrim(dirname($assetsBase), '/');                    // /reserva-salas
define('ASSETS_BASE', $assetsBase === '/' ? '' : $assetsBase);
define('APP_BASE',    $appBase  === '/' ? '' : $appBase);

require_once CONFIG_PATH . '/routes.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip app base prefix so routes are relative (e.g. /dashboard not /reserva-salas/dashboard)
if (APP_BASE !== '' && str_starts_with($uri, APP_BASE)) {
    $uri = substr($uri, strlen(APP_BASE));
}

\App\Route::dispatch($method, $uri ?: '/');
