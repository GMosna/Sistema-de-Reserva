<?php
$pageTitle    = 'Salas';
$pageSubtitle = 'Salas de reunião disponíveis';
$isAdmin = ($_SESSION['usuario_perfil'] ?? '') === 'admin';
?>

<div class="row g-4">
    <?php if (empty($salas)): ?>
    <div class="col-12"><div class="alert alert-info">Nenhuma sala registada.</div></div>
    <?php endif; ?>

    <?php foreach ($salas as $s): ?>
    <div class="col-lg-4 col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-600"><?= e($s->nome) ?></span>
                <?= $s->status === 'ativa'
                    ? '<span class="badge bg-success">Ativa</span>'
                    : '<span class="badge bg-secondary">Inativa</span>' ?>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-people me-2"></i>
                    <span><?= $s->capacidade ?> pessoas</span>
                </div>
            </div>
            <?php if ($isAdmin): ?>
            <div class="card-footer">
                <button class="btn btn-outline-secondary btn-sm w-100"
                        onclick='editRoom(<?= json_encode($s->toArray(), JSON_HEX_TAG|JSON_HEX_APOS) ?>)'
                        data-bs-toggle="modal" data-bs-target="#roomModal">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Edit Modal -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Sala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= APP_BASE ?>/salas/0" id="roomForm">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" name="roomName" id="roomName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacidade *</label>
                        <input type="number" class="form-control" name="roomCapacity" id="roomCapacity" min="1" required>
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
<?php $inlineJs = '
function editRoom(s){
  document.getElementById("roomName").value=s.nome;
  document.getElementById("roomCapacity").value=s.capacidade;
  document.getElementById("roomForm").action="'.APP_BASE.'/salas/"+s.id;
}
'; ?>
<?php endif; ?>
