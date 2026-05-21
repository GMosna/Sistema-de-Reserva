<?php
namespace App\Middleware;

class AuthMiddleware
{
    private bool $requireAdmin;

    public function __construct(bool $requireAdmin = false)
    {
        $this->requireAdmin = $requireAdmin;
    }

    public function handle(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . APP_BASE . '/login');
            exit;
        }

        if ($this->requireAdmin && ($_SESSION['usuario_perfil'] ?? '') !== 'admin') {
            header('Location: ' . APP_BASE . '/dashboard');
            exit;
        }
    }

    // Call on login: swap session ID to prevent session fixation
    public static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }
}
