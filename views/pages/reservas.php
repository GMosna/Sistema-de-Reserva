<?php
$pageTitle    = 'Reservas';
$pageSubtitle = 'Gerir todas as reservas';
$extraCssCdn  = ['https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css'];
$extraJsCdn   = ['https://cdn.jsdelivr.net/npm/flatpickr'];
require_once VIEWS_PATH . '/components/badge.php';
$uid = (int)($_SESSION['usuario_id'] ?? 0);
$isPerfil = ($_SESSION['usuario_perfil'] ?? '') === 'admin';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="<?= APP_BASE ?>/reservas/nova" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nova Reserva</a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Pesquisar</label>
                <input type="text" class="form-control" name="pesquisa" value="<?= e($f_pesquisa) ?>" placeholder="Nome, sala…">
            </div>
            <div class="col-md-2">
                <label class="form-label">Data Início</label>
                <input type="text" class="form-control date-picker" name="data_ini" value="<?= e($f_data_ini) ?>" placeholder="AAAA-MM-DD">
            </div>
            <div class="col-md-2">
                <label class="form-label">Data Fim</label>
                <input type="text" class="form-control date-picker" name="data_fim" value="<?= e($f_data_fim) ?>" placeholder="AAAA-MM-DD">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sala</label>
                <select class="form-select" name="sala">
                    <option value="">Todas</option>
                    <?php foreach ($salasFilter as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $f_sala == $s['id']?'selected':'' ?>><?= e($s['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <option value="">Todos</option>
                    <option value="confirmada" <?= $f_status==='confirmada'?'selected':'' ?>>Confirmada</option>
                    <option value="pendente"   <?= $f_status==='pendente'?'selected':'' ?>>Pendente</option>
                    <option value="cancelada"  <?= $f_status==='cancelada'?'selected':'' ?>>Cancelada</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="<?= APP_BASE ?>/reservas" class="btn btn-outline-secondary btn-sm" title="Limpar"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Reservas <span class="badge bg-secondary ms-1"><?= $total ?></span></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Data</th><th>Sala</th><th>Horário</th><th>Assunto</th>
                        <th>Responsável</th><th>Estado</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma reserva encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservas as $r):
                        $canManage = $isPerfil || (int)$r['usuario_id'] === $uid;
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($r['data_reserva'])) ?></td>
                        <td><?= e($r['sala_nome']) ?></td>
                        <td><?= substr($r['hora_inicio'],0,5).' – '.substr($r['hora_fim'],0,5) ?></td>
                        <td><?= e($r['assunto']) ?></td>
                        <td><?= e($r['usuario_nome']) ?></td>
                        <td><?= statusBadge($r['status']) ?></td>
                        <td>
                            <?php if ($r['status']==='pendente' && $isPerfil): ?>
                            <form method="post" action="<?= APP_BASE ?>/reservas/<?= $r['id'] ?>/confirmar" style="display:inline" onsubmit="return confirm('Confirmar?')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-success me-1" title="Confirmar"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($r['status']!=='cancelada' && $canManage): ?>
                            <form method="post" action="<?= APP_BASE ?>/reservas/<?= $r['id'] ?>/cancelar" style="display:inline" onsubmit="return confirm('Cancelar esta reserva?')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Cancelar"><i class="bi bi-x-lg"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($r['status']==='cancelada' && $canManage): ?>
                            <form method="post" action="<?= APP_BASE ?>/reservas/<?= $r['id'] ?>/excluir" style="display:inline" onsubmit="return confirm('Excluir permanentemente?')">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary" title="Excluir"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Página <?= $page ?> de <?= $totalPages ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $totalPages; $p++):
                    $q = http_build_query(array_merge($_GET, ['page' => $p]));
                ?>
                <li class="page-item <?= $p===$page?'active':'' ?>">
                    <a class="page-link" href="?<?= $q ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
