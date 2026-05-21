<?php
namespace App\Controllers;

use App\Controller;
use App\Helpers\Logger;
use App\Models\Usuario;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            $this->redirect('/dashboard');
        }
        $this->render('pages.login', ['erro' => get_flash('error') ?? ''], 'auth');
    }

    public function login(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            $this->redirect('/dashboard');
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['password'] ?? '';

        if ($email === '' || $senha === '') {
            flash('error', 'Por favor, preencha todos os campos.');
            $this->redirect('/login');
        }

        $user = Usuario::authenticate($email, $senha);

        if (!$user) {
            Logger::security('login_failed', null, ['email' => $email]);
            flash('error', 'E-mail ou palavra-passe incorretos.');
            $this->redirect('/login');
        }

        // Successful login
        AuthMiddleware::regenerateSession();
        RateLimitMiddleware::clear($_SERVER['REMOTE_ADDR'] ?? '');

        $_SESSION['usuario_id']     = $user->id;
        $_SESSION['usuario_nome']   = $user->nome;
        $_SESSION['usuario_email']  = $user->email;
        $_SESSION['usuario_perfil'] = $user->perfil;
        $_SESSION['usuario_foto']   = $user->foto ?? '';

        Logger::security('login_success', $user->id, ['email' => $email, 'perfil' => $user->perfil]);
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Logger::security('logout', $_SESSION['usuario_id'] ?? null);
        AuthMiddleware::regenerateSession();
        session_unset();
        session_destroy();
        $this->redirect('/login');
    }
}
