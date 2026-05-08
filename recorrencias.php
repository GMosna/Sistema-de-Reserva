<?php
require_once 'includes/auth.php';
requireLogin();
$user = currentUser();

function canManageRecurrence($conn, $id, $user) {
    if (isAdmin()) return true;
    $stmt = $conn->prepare("SELECT usuario_id FROM recorrencias WHERE id = ?");
    if (!$stmt) return false;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row && (int)$row['usuario_id'] === (int)$user['id'];
}

// Handle pause (set ativa = 0)
if (isset($_GET['pausar']) && is_numeric($_GET['pausar'])) {
    if (!verifyCsrf()) { header('Location: recorrencias.php'); exit(); }
    $id = (int)$_GET['pausar'];
    if (!canManageRecurrence($conn, $id, $user)) {
        setFlash('error', 'Sem permissão para alterar esta recorrência.');
    } else {
        $stmt = $conn->prepare("UPDATE recorrencias SET ativa = 0 WHERE id = ?");
        if (!$stmt) die('SQL error (pausar): ' . $conn->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Recorrência pausada.');
    }
    header('Location: recorrencias.php');
    exit();
}

// Handle resume (set ativa = 1)
if (isset($_GET['retomar']) && is_numeric($_GET['retomar'])) {
    if (!verifyCsrf()) { header('Location: recorrencias.php'); exit(); }
    $id = (int)$_GET['retomar'];
    if (!canManageRecurrence($conn, $id, $user)) {
        setFlash('error', 'Sem permissão para alterar esta recorrência.');
    } else {
        $stmt = $conn->prepare("UPDATE recorrencias SET ativa = 1 WHERE id = ?");
        if (!$stmt) die('SQL error (retomar): ' . $conn->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Recorrência retomada.');
    }
    header('Location: recorrencias.php');
    exit();
}

// Handle delete (deactivate + cancel all future reservations)
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    if (!verifyCsrf()) { header('Location: recorrencias.php'); exit(); }
    $id = (int)$_GET['eliminar'];
    if (!canManageRecurrence($conn, $id, $user)) {
        setFlash('error', 'Sem permissão para alterar esta recorrência.');
    } else {
        $hoje = date('Y-m-d');
        $stmt = $conn->prepare("UPDATE reservas SET status = 'cancelada' WHERE recorrencia_id = ? AND data_reserva >= ? AND status != 'cancelada'");
        if (!$stmt) die('SQL error (eliminar reservas): ' . $conn->error);
        $stmt->bind_param('is', $id, $hoje);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE recorrencias SET ativa = 0 WHERE id = ?");
        if (!$stmt) die('SQL error (eliminar recorrencia): ' . $conn->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Recorrência finalizada e reservas futuras canceladas.');
    }
    header('Location: recorrencias.php');
    exit();
}

// Filters
$f_pesquisa = trim($_GET['pesquisa'] ?? '');
$f_tipo     = $_GET['frequencia'] ?? '';
$f_sala     = $_GET['sala'] ?? '';
$f_ativa    = $_GET['estado'] ?? '';

$where = "1=1";
$params = [];
$types  = '';

if ($f_pesquisa !== '') {
    $where .= " AND (rc.assunto LIKE ? OR u.nome LIKE ? OR s.nome LIKE ?)";
    $like = "%{$f_pesquisa}%";
    $params[] = &$like; $params[] = &$like; $params[] = &$like;
    $types .= 'sss';
}
if ($f_tipo !== '') {
    $where .= " AND rc.tipo = ?";
    $params[] = &$f_tipo;
    $types .= 's';
}
if ($f_sala !== '') {
    $where .= " AND rc.sala_id = ?";
    $f_sala_int = (int)$f_sala;
    $params[] = &$f_sala_int;
    $types .= 'i';
}
if ($f_ativa !== '') {
    $where .= " AND rc.ativa = ?";
    $f_ativa_int = ($f_ativa === 'ativa') ? 1 : 0;
    $params[] = &$f_ativa_int;
    $types .= 'i';
}

$sql = "SELECT rc.*, s.nome AS sala_nome, u.nome AS usuario_nome
        FROM recorrencias rc
        JOIN salas s ON s.id = rc.sala_id
        JOIN usuarios u ON u.id = rc.usuario_id
        WHERE {$where}
        ORDER BY rc.ativa DESC, rc.data_inicio DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) die('SQL error (list recorrencias): ' . $conn->error);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$recorrencias = $stmt->get_result();
$stmt->close();

// Rooms for filter
$salasFilter = $conn->query("SELECT id, nome FROM salas ORDER BY nome");

$page_title = "Recorrências";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="header d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-outline-secondary d-md-none me-3" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <h4 class="mb-0">Recorrências</h4>
            <small class="text-muted">Gerir reservas recorrentes</small>
        </div>
        <a href="nova_reserva.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nova Reserva Recorrente</a>
    </div>

    <div class="container-fluid px-4">
        <?php renderFlash(); ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Pesquisar</label>
                        <input type="text" class="form-control" name="pesquisa" value="<?php echo htmlspecialchars($f_pesquisa); ?>" placeholder="Nome, sala, responsável...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Frequência</label>
                        <select class="form-select" name="frequencia">
                            <option value="">Todas</option>
                            <option value="semanal" <?php echo ($f_tipo === 'semanal') ? 'selected' : ''; ?>>Semanal</option>
                            <option value="mensal" <?php echo ($f_tipo === 'mensal') ? 'selected' : ''; ?>>Mensal</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sala</label>
                        <select class="form-select" name="sala">
                            <option value="">Todas</option>
                            <?php while ($sf = $salasFilter->fetch_assoc()): ?>
                                <option value="<?php echo $sf['id']; ?>" <?php echo ($f_sala == $sf['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sf['nome']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="">Todos</option>
                            <option value="ativa" <?php echo ($f_ativa === 'ativa') ? 'selected' : ''; ?>>Ativa</option>
                            <option value="inativa" <?php echo ($f_ativa === 'inativa') ? 'selected' : ''; ?>>Inativa</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Reservas Recorrentes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Assunto</th>
                                <th>Sala</th>
                                <th>Tipo</th>
                                <th>Horário</th>
                                <th>Responsável</th>
                                <th>Período</th>
                                <th>Estado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recorrencias->num_rows === 0): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma recorrência encontrada</td></tr>
                            <?php else: ?>
                                <?php while ($rc = $recorrencias->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rc['assunto']); ?></td>
                                    <td><?php echo htmlspecialchars($rc['sala_nome']); ?></td>
                                    <td>
                                        <?php
                                        $fBadge = match($rc['tipo']) {
                                            'semanal'   => 'bg-primary',
                                            'quinzenal' => 'bg-secondary',
                                            'mensal'    => 'bg-info',
                                            default     => 'bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?php echo $fBadge; ?>"><?php echo ucfirst($rc['tipo']); ?></span>
                                    </td>
                                    <td><?php echo substr($rc['hora_inicio'], 0, 5) . ' - ' . substr($rc['hora_fim'], 0, 5); ?></td>
                                    <td><?php echo htmlspecialchars($rc['usuario_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($rc['data_inicio'])) . ' — ' . date('d/m/Y', strtotime($rc['data_fim'])); ?></td>
                                    <td>
                                        <?php if ($rc['ativa']): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $canManage = canManageReservation($rc['usuario_id'], $user['id'], $user['perfil']); ?>
                                        <?php if ($canManage): ?>
                                        <div class="btn-group" role="group">
                                            <?php if ($rc['ativa']): ?>
                                                <a href="recorrencias.php?pausar=<?php echo $rc['id']; ?>&csrf=<?php echo csrfToken(); ?>" class="btn btn-sm btn-outline-warning" title="Pausar" onclick="return confirm('Pausar esta recorrência?')"><i class="bi bi-pause"></i></a>
                                            <?php else: ?>
                                                <a href="recorrencias.php?retomar=<?php echo $rc['id']; ?>&csrf=<?php echo csrfToken(); ?>" class="btn btn-sm btn-outline-success" title="Retomar" onclick="return confirm('Retomar esta recorrência?')"><i class="bi bi-play"></i></a>
                                            <?php endif; ?>
                                            <?php if ($rc['ativa']): ?>
                                                <a href="recorrencias.php?eliminar=<?php echo $rc['id']; ?>&csrf=<?php echo csrfToken(); ?>" class="btn btn-sm btn-outline-danger" title="Finalizar e cancelar futuras" onclick="return confirm('Finalizar esta recorrência e cancelar todas as reservas futuras?')"><i class="bi bi-trash"></i></a>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
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

<?php require_once 'includes/footer.php'; ?>
