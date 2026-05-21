<?php
namespace App\Middleware;

class CsrfMiddleware
{
    public function handle(): void
    {
        // Accept both field names: new (csrf_token) and legacy (csrf)
        $token = $_POST['csrf_token'] ?? $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            exit('CSRF token inválido.');
        }
    }
}
