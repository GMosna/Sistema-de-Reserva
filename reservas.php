<?php
require_once 'includes/auth.php';
requireLogin();

$user = currentUser();

// Handle cancel action
if (isset($_GET['cancelar']) && is_numeric($_GET['cancelar'])) {
    if (!verifyCsrf()) { header('Location: reservas.php'); exit(); }
    $id = (int)$_GET['cancelar'];
    $chk = $conn->prepare("SELECT usuario_id FROM reservas WHERE id = ?");
    if (!$chk) die('SQL error (check cancelar): ' . $conn->error);
    $chk->bind_param('i', $id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row) {
        setFlash('error', 'Reserva não encontrada.');
    } elseif (!canManageReservation($row['usuario_id'], $user['id'], $user['perfil'])) {
        setFlash('error', 'Sem permissão para cancelar esta reserva.');
    } else {
        $stmt = $conn->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ? AND status != 'cancelada'");
        if (!$stmt) die('SQL error (cancelar): ' . $conn->error);
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) die('SQL error (cancelar exec): ' . $stmt->error);
        if ($stmt->affected_rows > 0) {
            setFlash('success', 'Reserva cancelada com sucesso.');
        } else {
            setFlash('error', 'Reserva já se encontra cancelada.');
        }
        $stmt->close();
    }
    header('Location: reservas.php');
    exit();
}

// Handle confirm action (admin only)
if (isset($_GET['confirmar']) && is_numeric($_GET['confirmar'])) {
    if (!verifyCsrf()) { header('Location: reservas.php'); exit(); }
    $id = (int)$_GET['confirmar'];
    if (!isAdmin()) {
        setFlash('error', 'Apenas administradores podem confirmar reservas.');
        header('Location: reservas.php');
        exit();
    }
    $stmt = $conn->prepare("UPDATE reservas SET status = 'confirmada' WHERE id = ?");
    if (!$stmt) die('SQL error (confirmar): ' . $conn->error);
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) die('SQL error (confirmar exec): ' . $stmt->error);
    $stmt->close();
    setFlash('success', 'Reserva confirmada.');
    header('Location: reservas.php');
    exit();
}

// Handle delete action (only cancelled reservations)
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    if (!verifyCsrf()) { header('Location: reservas.php'); exit(); }
    $id = (int)$_GET['excluir'];
    // Fetch the reservation to validate status and ownership
    $chk = $conn->prepare("SELECT usuario_id, status FROM reservas WHERE id = ?");
    if (!$chk) die('SQL error (check excluir): ' . $conn->error);
    $chk->bind_param('i', $id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row) {
        setFlash('error', 'Reserva não encontrada.');
    } elseif ($row['status'] !== 'cancelada') {
        setFlash('error', 'Só é possível excluir reservas canceladas.');
    } elseif (!canManageReservation($row['usuario_id'], $user['id'], $user['perfil'])) {
        setFlash('error', 'Sem permissão para excluir esta reserva.');
    } else {
        $del = $conn->prepare("DELETE FROM reservas WHERE id = ? AND status = 'cancelada'");
        if (!$del) die('SQL error (delete): ' . $conn->error);
        $del->bind_param('i', $id);
        if (!$del->execute()) die('SQL error (excluir exec): ' . $del->error);
        $del->close();
        setFlash('success', 'Reserva excluída permanentemente.');
    }
    header('Location: reservas.php');
    exit();
}

// Filter parameters
$f_pesquisa   = trim($_GET['pesquisa'] ?? '');
$f_data_ini   = $_GET['data_ini'] ?? '';
$f_data_fim   = $_GET['data_fim'] ?? '';
$f_sala       = $_GET['sala'] ?? '';
$f_status     = $_GET['estado'] ?? '';

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = "1=1";
$params = [];
$types  = '';

if ($f_pesquisa !== '') {
    $where .= " AND (r.assunto LIKE ? OR u.nome LIKE ? OR s.nome LIKE ?)";
    $like = "%{$f_pesquisa}%";
    $params[] = &$like; $params[] = &$like; $params[] = &$like;
    $types .= 'sss';
}
if ($f_data_ini !== '') {
    $where .= " AND r.data_reserva >= ?";
    $params[] = &$f_data_ini;
    $types .= 's';
}
if ($f_data_fim !== '') {
    $where .= " AND r.data_reserva <= ?";
    $params[] = &$f_data_fim;
    $types .= 's';
}
if ($f_sala !== '') {
    $where .= " AND r.sala_id = ?";
    $f_sala_int = (int)$f_sala;
    $params[] = &$f_sala_int;
    $types .= 'i';
}
if ($f_status !== '') {
    $where .= " AND r.status = ?";
    $params[] = &$f_status;
    $types .= 's';
}

// Count total
$countSql = "SELECT COUNT(*) AS total FROM reservas r JOIN salas s ON s.id = r.sala_id JOIN usuarios u ON u.id = r.usuario_id WHERE {$where}";
$countStmt = $conn->prepare($countSql);
if (!$countStmt) die('SQL error (count): ' . $conn->error);
if ($types !== '') $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = max(1, ceil($total / $limit));

// Fetch data
$dataSql = "SELECT r.*, s.nome AS sala_nome, u.nome AS usuario_nome
            FROM reservas r
            JOIN salas s ON s.id = r.sala_id
            JOIN usuarios u ON u.id = r.usuario_id
            WHERE {$where}
            ORDER BY r.data_reserva DESC, r.hora_inicio DESC
            LIMIT {$limit} OFFSET {$offset}";
$dataStmt = $conn->prepare($dataSql);
if (!$dataStmt) die('SQL error (data): ' . $conn->error);
if ($types !== '') $dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$reservas = $dataStmt->get_result();
$dataStmt->close();

// Rooms for filter dropdown
$salasFilter = $conn->query("SELECT id, nome FROM salas ORDER BY nome");

$page_title = "Reservas";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="header d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-outline-secondary d-md-none me-3" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <h4 class="mb-0">Reservas</h4>
            <small class="text-muted">Gerir todas as reservas</small>
        </div>
        <a href="nova_reserva.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nova Reserva</a>
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
                        <label class="form-label">Data Início</label>
                        <input type="date" class="form-control" name="data_ini" value="<?php echo htmlspecialchars($f_data_ini); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data Fim</label>
                        <input type="date" class="form-control" name="data_fim" value="<?php echo htmlspecialchars($f_data_fim); ?>">
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
                            <option value="confirmada" <?php echo ($f_status === 'confirmada') ? 'selected' : ''; ?>>Confirmada</option>
                            <option value="pendente" <?php echo ($f_status === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                            <option value="cancelada" <?php echo ($f_status === 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Reservas</h5>
                <span class="text-muted small"><?php echo $total; ?> resultado(s)</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Sala</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Responsável</th>
                                <th>Assunto</th>
                                <th>Estado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reservas->num_rows === 0): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma reserva encontrada</td></tr>
                            <?php else: ?>
                                <?php while ($r = $reservas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['sala_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($r['data_reserva'])); ?></td>
                                    <td><?php echo substr($r['hora_inicio'], 0, 5) . ' - ' . substr($r['hora_fim'], 0, 5); ?></td>
                                    <td><?php echo htmlspecialchars($r['usuario_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($r['assunto']); ?></td>
                                    <td>
                                        <?php
                                        $badge = match($r['status']) {
                                            'confirmada' => 'bg-success',
                                            'pendente'   => 'bg-warning',
                                            'cancelada'  => 'bg-danger',
                                            default      => 'bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($r['status']); ?></span>
                                    </td>
                                    <td>
                                        <?php $canManage = canManageReservation($r['usuario_id'], $user['id'], $user['perfil']); ?>
                                        <?php if ($r['status'] === 'pendente' && isAdmin()): ?>
                                            <a href="reservas.php?confirmar=<?php echo $r['id']; ?>&csrf=<?php echo csrfToken(); ?>" class="btn btn-sm btn-outline-success me-1" title="Confirmar" onclick="return confirm('Confirmar esta reserva?')"><i class="bi bi-check-lg"></i></a>
                                        <?php endif; ?>
                                        <?php if ($r['status'] !== 'cancelada' && $canManage): ?>
                                            <a href="reservas.php?cancelar=<?php echo $r['id']; ?>&csrf=<?php echo csrfToken(); ?>" class="btn btn-sm btn-outline-danger" title="Cancelar" onclick="return confirm('Cancelar esta reserva?')"><i class="bi bi-x-lg"></i></a>
                                        <?php endif; ?>
                                        <?php if ($r['status'] === 'cancelada' && $canManage): ?>
                                            <a href="reservas.php?excluir=<?php echo $r['id']; ?>&csrf=<?php echo csrfToken(); ?>" class="btn btn-sm btn-outline-secondary" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta reserva cancelada?')"><i class="bi bi-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Próximo</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
