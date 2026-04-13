<?php
require_once 'includes/auth.php';
requireAdmin();

// Handle create/update user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid       = (int)($_POST['user_id'] ?? 0);
    $nome      = trim($_POST['userName'] ?? '');
    $email     = trim($_POST['userEmail'] ?? '');
    $perfil    = $_POST['userRole'] ?? 'utilizador';
    $ativo_val = ($_POST['userStatus'] ?? 'ativo') === 'ativo' ? 1 : 0;
    $senha_raw = $_POST['userPassword'] ?? '';

    if (!in_array($perfil, ['admin', 'lider', 'utilizador'], true)) $perfil = 'utilizador';

    if ($nome === '' || $email === '') {
        setFlash('error', 'Nome e e-mail são obrigatórios.');
    } else {
        if ($uid > 0) {
            // Update
            if ($senha_raw !== '') {
                $senhaHash = password_hash($senha_raw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, perfil=?, ativo=?, senha=? WHERE id=?");
                if (!$stmt) die('SQL error (update user+pwd): ' . $conn->error);
                $stmt->bind_param('sssisi', $nome, $email, $perfil, $ativo_val, $senhaHash, $uid);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, perfil=?, ativo=? WHERE id=?");
                if (!$stmt) die('SQL error (update user): ' . $conn->error);
                $stmt->bind_param('sssii', $nome, $email, $perfil, $ativo_val, $uid);
            }
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Usuário atualizado com sucesso.');
        } else {
            // Create - password required
            if ($senha_raw === '') {
                setFlash('error', 'Palavra-passe é obrigatória para novos usuários.');
                header('Location: usuarios.php');
                exit();
            }
            // Check duplicate email
            $chk = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            if (!$chk) die('SQL error (check email): ' . $conn->error);
            $chk->bind_param('s', $email);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $chk->close();
                setFlash('error', 'Já existe um usuário com este e-mail.');
                header('Location: usuarios.php');
                exit();
            }
            $chk->close();

            $senhaHash = password_hash($senha_raw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) die('SQL error (insert user): ' . $conn->error);
            $stmt->bind_param('ssssi', $nome, $email, $senhaHash, $perfil, $ativo_val);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Usuário criado com sucesso.');
        }
    }
    header('Location: usuarios.php');
    exit();
}

// Handle deactivate
if (isset($_GET['desativar']) && is_numeric($_GET['desativar'])) {
    $id = (int)$_GET['desativar'];
    if ($id === (int)$_SESSION['usuario_id']) {
        setFlash('error', 'Não pode desativar a sua própria conta.');
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
        if (!$stmt) die('SQL error (desativar user): ' . $conn->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Usuário desativado.');
    }
    header('Location: usuarios.php');
    exit();
}

// Handle activate
if (isset($_GET['ativar']) && is_numeric($_GET['ativar'])) {
    $stmt = $conn->prepare("UPDATE usuarios SET ativo = 1 WHERE id = ?");
    if (!$stmt) die('SQL error (ativar user): ' . $conn->error);
    $id = (int)$_GET['ativar'];
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Usuário ativado.');
    header('Location: usuarios.php');
    exit();
}

// Filters
$f_pesquisa = trim($_GET['pesquisa'] ?? '');
$f_perfil   = $_GET['perfil'] ?? '';
$f_ativo    = $_GET['estado'] ?? '';

$where = "1=1";
$params = [];
$types  = '';

if ($f_pesquisa !== '') {
    $where .= " AND (nome LIKE ? OR email LIKE ?)";
    $like = "%{$f_pesquisa}%";
    $params[] = &$like; $params[] = &$like;
    $types .= 'ss';
}
if ($f_perfil !== '') {
    $where .= " AND perfil = ?";
    $params[] = &$f_perfil;
    $types .= 's';
}
if ($f_ativo !== '') {
    $where .= " AND ativo = ?";
    $f_ativo_int = ($f_ativo === 'ativo') ? 1 : 0;
    $params[] = &$f_ativo_int;
    $types .= 'i';
}

$sql = "SELECT * FROM usuarios WHERE {$where} ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) die('SQL error (list users): ' . $conn->error);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$usuarios = $stmt->get_result();
$stmt->close();

$page_title = "Usuários";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="header d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-outline-secondary d-md-none me-3" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <h4 class="mb-0">Usuários</h4>
            <small class="text-muted">Gerir usuários do sistema</small>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="clearUserForm()">
            <i class="bi bi-plus-circle me-2"></i>Novo Usuário
        </button>
    </div>

    <div class="container-fluid px-4">
        <?php renderFlash(); ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Pesquisar</label>
                        <input type="text" class="form-control" name="pesquisa" value="<?php echo htmlspecialchars($f_pesquisa); ?>" placeholder="Nome ou e-mail...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Perfil</label>
                        <select class="form-select" name="perfil">
                            <option value="">Todos</option>
                            <option value="admin" <?php echo ($f_perfil === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="lider" <?php echo ($f_perfil === 'lider') ? 'selected' : ''; ?>>Líder</option>
                            <option value="utilizador" <?php echo ($f_perfil === 'utilizador') ? 'selected' : ''; ?>>Usuário</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="">Todos</option>
                            <option value="ativo" <?php echo ($f_ativo === 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo ($f_ativo === 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lista de Usuários</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Estado</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($usuarios->num_rows === 0): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado</td></tr>
                            <?php else: ?>
                                <?php while ($u = $usuarios->fetch_assoc()): ?>
                                <?php
                                    $parts = explode(' ', $u['nome']);
                                    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                                    $colors = ['bg-primary','bg-info','bg-success','bg-warning','bg-danger','bg-dark'];
                                    $color = $colors[$u['id'] % count($colors)];
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar <?php echo $color; ?> me-3"><?php echo $initials; ?></div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($u['nome']); ?></div>
                                                <small class="text-muted">ID: #<?php echo str_pad($u['id'], 3, '0', STR_PAD_LEFT); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <?php
                                        $pBadge = match($u['perfil']) {
                                            'admin'       => 'bg-danger',
                                            'lider'       => 'bg-primary',
                                            'utilizador'  => 'bg-secondary',
                                            default       => 'bg-secondary',
                                        };
                                        $pLabel = match($u['perfil']) {
                                            'admin'       => 'Administrador',
                                            'lider'       => 'Líder',
                                            'utilizador'  => 'Usuário',
                                            default       => $u['perfil'],
                                        };
                                        ?>
                                        <span class="badge <?php echo $pBadge; ?>"><?php echo $pLabel; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($u['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $u['criado_em'] ? date('d/m/Y H:i', strtotime($u['criado_em'])) : '-'; ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php $uSafe = $u; unset($uSafe['senha']); ?>
                                            <button class="btn btn-sm btn-outline-secondary" title="Editar" data-bs-toggle="modal" data-bs-target="#userModal" onclick='editUser(<?php echo htmlspecialchars(json_encode($uSafe), ENT_QUOTES, "UTF-8"); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if (!$u['ativo']): ?>
                                                <a href="usuarios.php?ativar=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-success" title="Ativar" onclick="return confirm('Ativar este usuário?')"><i class="bi bi-check"></i></a>
                                            <?php endif; ?>
                                            <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                                <?php if ($u['ativo']): ?>
                                                    <a href="usuarios.php?desativar=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" title="Desativar" onclick="return confirm('Desativar este usuário?')"><i class="bi bi-x-lg"></i></a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="usuarios.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userName" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="userName" name="userName" placeholder="Ex: João Silva" required>
                        </div>
                        <div class="col-md-6">
                            <label for="userEmail" class="form-label">E-mail *</label>
                            <input type="email" class="form-control" id="userEmail" name="userEmail" placeholder="joao.silva@sassi.pt" required>
                        </div>
                        <div class="col-md-6">
                            <label for="userPassword" class="form-label">Palavra-passe <span id="pwdHint" class="text-muted small">(obrigatória)</span></label>
                            <input type="password" class="form-control" id="userPassword" name="userPassword" placeholder="Mínimo 6 caracteres">
                        </div>
                        <div class="col-md-6">
                            <label for="userRole" class="form-label">Perfil *</label>
                            <select class="form-select" id="userRole" name="userRole" required>
                                <option value="">Selecione...</option>
                                <option value="admin">Administrador</option>
                                <option value="lider">Líder</option>
                                <option value="utilizador">Usuário</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="userStatus" class="form-label">Estado</label>
                            <select class="form-select" id="userStatus" name="userStatus">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="userSubmitBtn">Criar Usuário</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$additional_scripts = '
<script>
    function clearUserForm() {
        document.getElementById("userModalLabel").textContent = "Novo Usuário";
        document.getElementById("userSubmitBtn").textContent = "Criar Usuário";
        document.getElementById("user_id").value = "0";
        document.getElementById("userName").value = "";
        document.getElementById("userEmail").value = "";
        document.getElementById("userPassword").value = "";
        document.getElementById("userRole").value = "";
        document.getElementById("userStatus").value = "ativo";
        document.getElementById("pwdHint").textContent = "(obrigatória)";
        document.getElementById("userPassword").required = true;
    }
    
    function editUser(u) {
        document.getElementById("userModalLabel").textContent = "Editar Usuário";
        document.getElementById("userSubmitBtn").textContent = "Guardar Alterações";
        document.getElementById("user_id").value = u.id;
        document.getElementById("userName").value = u.nome;
        document.getElementById("userEmail").value = u.email;
        document.getElementById("userPassword").value = "";
        document.getElementById("userRole").value = u.perfil;
        document.getElementById("userStatus").value = u.ativo == 1 ? "ativo" : "inativo";
        document.getElementById("pwdHint").textContent = "(deixe vazio para manter)";
        document.getElementById("userPassword").required = false;
    }
</script>';
require_once 'includes/footer.php';
?>
