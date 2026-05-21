<?php
$pageTitle    = 'Recorrências';
$pageSubtitle = 'Gerir reservas recorrentes';
require_once VIEWS_PATH . '/components/badge.php';
$uid      = (int)($_SESSION['usuario_id'] ?? 0);
$isAdmin  = ($_SESSION['usuario_perfil'] ?? '') === 'admin';
?>

<div class="d-flex justify-content-end mb-4">
    <a href="<?= APP_BASE ?>/reservas/nova" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nova Recorrente</a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Pesquisar</label>
                <input type="text" class="form-control" name="pesquisa" value="<?= e($f_pesquisa) ?>" placeholder="Assunto, sala…">
            </div>
            <div class="col-md-2">
                <label class="form-label">Frequência</label>
                <select class="form-select" name="frequencia">
                    <option value="">Todas</option>
                    <option value="semanal" <?= $f_tipo==='semanal'?'selected':'' ?>>Semanal</option>
                    <option value="mensal"  <?= $f_tipo==='mensal'?'selected':'' ?>>Mensal</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sala</label>
                <select class="form-select" name="sala">
                    <option value="">Todas</option>
                    <?php foreach ($salasFilter as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $f_sala==$s['id']?'selected':'' ?>><?= e($s['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <option value="">Todos</option>
                    <option value="ativa"   <?= $f_ativa==='ativa'?'selected':'' ?>>Ativa</option>
                    <option value="inativa" <?= $f_ativa==='inativa'?'selected':'' ?>>Inativa</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="<?= APP_BASE ?>/recorrencias" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Assunto</th><th>Sala</th><th>Tipo</th><th>Horário</th><th>Período</th><th>Responsável</th><th>Estado</th><th>Ações</th></tr>
                </thead>
                <tbody>
                <?php if (empty($recorrencias)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma recorrência encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($recorrencias as $rc):
                        $canManage = $isAdmin || (int)$rc['usuario_id'] === $uid;
                    ?>
                    <tr>
                        <td><?= e($rc['assunto']) ?></td>
                        <td><?= e($rc['sala_nome']) ?></td>
                        <td><span class="badge bg-info"><?= ucfirst($rc['tipo']) ?></span></td>
                        <td><?= substr($rc['hora_inicio'],0,5).' – '.substr($rc['hora_fim'],0,5) ?></td>
                        <td><?= date('d/m/Y',strtotime($rc['data_inicio'])).' → '.date('d/m/Y',strtotime($rc['data_fim'])) ?></td>
                        <td><?= e($rc['usuario_nome']) ?></td>
                        <td><?= $rc['ativa'] ? statusBadge('ativa') : statusBadge('inativa') ?></td>
                        <td>
                        <?php if ($canManage): ?>
                            <?php if ($rc['ativa']): ?>
                            <form method="post" action="<?= APP_BASE ?>/recorrencias/<?= $rc['id'] ?>/toggle" style="display:inline" onsubmit="return confirm('Pausar esta recorrência?')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-warning" title="Pausar"><i class="bi bi-pause"></i></button>
                            </form>
                            <form method="post" action="<?= APP_BASE ?>/recorrencias/<?= $rc['id'] ?>/excluir" style="display:inline" onsubmit="return confirm('Finalizar e cancelar reservas futuras?')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Finalizar"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php else: ?>
                            <form method="post" action="<?= APP_BASE ?>/recorrencias/<?= $rc['id'] ?>/toggle" style="display:inline" onsubmit="return confirm('Retomar esta recorrência?')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-success" title="Retomar"><i class="bi bi-play"></i></button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
