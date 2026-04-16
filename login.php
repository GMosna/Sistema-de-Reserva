<?php
session_start();
require_once 'config/conexao.php';

// If already logged in, go to dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['password'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        $stmt = $conn->prepare("SELECT id, nome, email, senha, perfil, ativo, foto FROM usuarios WHERE email = ? LIMIT 1");
        if (!$stmt) {
            $erro = 'Erro interno: ' . $conn->error;
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $erro = 'E-mail ou palavra-passe incorretos.';
            } elseif (!$user['ativo']) {
                $erro = 'A sua conta está inativa. Contacte o administrador.';
            } elseif (!password_verify($senha, $user['senha'])) {
                $erro = 'E-mail ou palavra-passe incorretos.';
            } else {
                session_regenerate_id(true);
                $_SESSION['usuario_id']    = $user['id'];
                $_SESSION['usuario_nome']  = $user['nome'];
                $_SESSION['usuario_email'] = $user['email'];
                $_SESSION['usuario_perfil'] = $user['perfil'];
                $_SESSION['usuario_foto']  = $user['foto'] ?? '';

                header('Location: dashboard.php');
                exit();
            }
        }
    }
}

$page_title = "Login";
$additional_styles = '
<style>
    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        overflow: hidden;
    }

    .main-content {
        margin-left: 0;
    }

    .login-bg {
        position: fixed;
        inset: 0;
        z-index: 0;
        background: url("public/img/bg.png") center / cover no-repeat;
        filter: blur(5px);
        transform: scale(1.02);
    }

    .login-overlay {
        position: fixed;
        inset: 0;
        z-index: 1;
        background: rgba(0, 0, 0, 0.5);
    }

    .login-container {
        position: relative;
        z-index: 2;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .login-card {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
        padding: 2.5rem 2rem;
        width: 100%;
        max-width: 420px;
    }

    .login-card h3 {
        color: #fff;
    }

    .login-card p.text-muted,
    .login-card .form-label {
        color: rgba(255, 255, 255, 0.75) !important;
    }

    .login-card .form-check-label {
        color: rgba(255, 255, 255, 0.7);
    }

    .login-card .form-control {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .login-card .form-control::placeholder {
        color: rgba(255, 255, 255, 0.45);
    }

    .login-card .form-control:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.3);
        color: #fff;
    }

    .login-card .input-group-text {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.7);
    }

    .login-card .btn-outline-secondary {
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.7);
        background: rgba(255, 255, 255, 0.08);
    }

    .login-card .btn-outline-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }

    .login-card .btn-primary {
        background: var(--primary-color);
        border: none;
        padding: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        transition: background 0.2s, transform 0.15s;
    }

    .login-card .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    .login-card .form-check-input {
        background-color: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .login-card .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .login-card .alert-danger {
        background: rgba(220, 53, 69, 0.2);
        border: 1px solid rgba(220, 53, 69, 0.4);
        color: #f8d7da;
    }

    .login-card .alert-danger .btn-close {
        filter: invert(1) grayscale(1) brightness(2);
    }

    .login-footer {
        color: rgba(255, 255, 255, 0.5);
    }

    .login-footer strong {
        color: rgba(255, 255, 255, 0.7);
    }

    .logo {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        box-shadow: 0 4px 20px rgba(220, 53, 69, 0.5);
        overflow: hidden;
    }

    .logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    @media (max-width: 480px) {
        .login-card {
            padding: 2rem 1.25rem;
            border-radius: 16px;
        }
    }
</style>';

$additional_scripts = '
<script>
    document.getElementById("togglePassword").addEventListener("click", function() {
        const password = document.getElementById("password");
        const icon = this.querySelector("i");
        if (password.type === "password") {
            password.type = "text";
            icon.classList.replace("bi-eye", "bi-eye-slash");
        } else {
            password.type = "password";
            icon.classList.replace("bi-eye-slash", "bi-eye");
        }
    });
</script>';

require_once 'includes/header.php';
?>

<div class="login-bg"></div>
<div class="login-overlay"></div>

<div class="login-container">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="logo">
                <img src="public/img/logo-s.png" alt="Sassi">
            </div>
            <h3 class="fw-bold">Sassi Imóveis</h3>
            <p class="text-muted">Sistema de Reserva de Salas</p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($erro); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="seu@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Palavra-passe</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Lembrar-me</label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Entrar
            </button>
        </form>
        
        <div class="text-center mt-3 login-footer">
            <small>&copy; 2026 Sassi Imóveis - Todos os direitos reservados</small>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>