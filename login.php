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
        $stmt = $conn->prepare("SELECT id, nome, email, senha, perfil, ativo FROM usuarios WHERE email = ? LIMIT 1");
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        width: 100%;
        max-width: 400px;
    }
    
    .logo {
        width: 60px;
        height: 60px;
        background: var(--primary-color);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: bold;
        margin: 0 auto 1rem;
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

<div class="login-container">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="logo">
                <i class="bi bi-building"></i>
            </div>
            <h3 class="fw-bold text-dark">Sassi Imóveis</h3>
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
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
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
        
        <div class="text-center mt-3">
            <small class="text-muted">Login padrão: <strong>admin@sassi.pt</strong> / <strong>admin123</strong></small>
        </div>
        <div class="text-center mt-2">
            <small class="text-muted">&copy; 2024 Sassi Imóveis - Todos os direitos reservados</small>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>