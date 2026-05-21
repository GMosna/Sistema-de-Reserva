<?php
namespace App;

abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data, EXTR_SKIP);
        $file = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: $file");
        }
        ob_start();
        require $file;
        $content = ob_get_clean();

        $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . APP_BASE . $path);
        exit;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function currentUser(): array
    {
        return [
            'id'     => $_SESSION['usuario_id']    ?? null,
            'nome'   => $_SESSION['usuario_nome']   ?? '',
            'email'  => $_SESSION['usuario_email']  ?? '',
            'perfil' => $_SESSION['usuario_perfil'] ?? '',
            'foto'   => $_SESSION['usuario_foto']   ?? '',
        ];
    }

    protected function isAdmin(): bool
    {
        return ($_SESSION['usuario_perfil'] ?? '') === 'admin';
    }

    protected function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            flash('error', 'Acesso restrito a administradores.');
            $this->redirect('/dashboard');
        }
    }
}
