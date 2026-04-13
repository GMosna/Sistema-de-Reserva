<?php
require_once 'includes/auth.php';
requireLogin();
$user = currentUser();
$isAdmin = ($user['perfil'] === 'admin');

// Handle update room (admin only, no creation allowed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        setFlash('error', 'Apenas administradores podem editar salas.');
        header('Location: salas.php');
        exit();
    }
    $room_id    = (int)($_POST['room_id'] ?? 0);
    $nome       = trim($_POST['roomName'] ?? '');
    $capacidade = (int)($_POST['roomCapacity'] ?? 0);

    if ($room_id <= 0) {
        setFlash('error', 'Operação não permitida.');
    } elseif ($nome === '' || $capacidade < 1) {
        setFlash('error', 'Nome e capacidade são obrigatórios.');
    } else {
        $stmt = $conn->prepare("UPDATE salas SET nome=?, capacidade=? WHERE id=?");
        if (!$stmt) die('SQL error (update sala): ' . $conn->error);
        $stmt->bind_param('sii', $nome, $capacidade, $room_id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Sala atualizada com sucesso.');
    }
    header('Location: salas.php');
    exit();
}

// Fetch rooms
$salas = $conn->query("SELECT * FROM salas ORDER BY nome ASC");
if (!$salas) die('SQL error (list salas): ' . $conn->error);

$page_title = "Salas";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="header d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-outline-secondary d-md-none me-3" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <h4 class="mb-0">Salas</h4>
            <small class="text-muted">Salas de reunião disponíveis</small>
        </div>
    </div>

    <div class="container-fluid px-4">
        <?php renderFlash(); ?>

        <div class="row">
            <?php if ($salas->num_rows === 0): ?>
                <div class="col-12"><div class="alert alert-info">Nenhuma sala registada.</div></div>
            <?php endif; ?>

            <?php while ($s = $salas->fetch_assoc()): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card room-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($s['nome']); ?></h5>
                        <span class="badge bg-success">Ativa</span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-people me-2 text-muted"></i>
                            <span><?php echo $s['capacidade']; ?> pessoas</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100" role="group">
                            <?php if ($isAdmin): ?>
                            <button class="btn btn-outline-secondary btn-sm" onclick="editRoom(<?php echo htmlspecialchars(json_encode($s)); ?>)" data-bs-toggle="modal" data-bs-target="#roomModal">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <?php endif; ?>
                            <a href="nova_reserva.php" class="btn btn-outline-success btn-sm"><i class="bi bi-calendar-plus"></i> Reservar</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Edit Room Modal (admin only) -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="salas.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalLabel">Editar Sala</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="room_id" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="roomName" class="form-label">Nome da Sala *</label>
                            <input type="text" class="form-control" id="roomName" name="roomName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="roomCapacity" class="form-label">Capacidade *</label>
                            <input type="number" class="form-control" id="roomCapacity" name="roomCapacity" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$additional_scripts = '
<script>
    function editRoom(sala) {
        document.getElementById("room_id").value = sala.id;
        document.getElementById("roomName").value = sala.nome;
        document.getElementById("roomCapacity").value = sala.capacidade;
    }
</script>';
require_once 'includes/footer.php';
?>
