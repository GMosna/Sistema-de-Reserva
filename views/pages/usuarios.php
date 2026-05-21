<?php
$pageTitle    = 'Usuários';
$pageSubtitle = 'Gestão de utilizadores';
$uid = (int)($_SESSION['usuario_id'] ?? 0);
?>

<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="newUser()">
        <i class="bi bi-person-plus me-2"></i>Novo Usuário
    </button>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Pesquisar</label>
                <input type="text" class="form-control" name="pesquisa" value="<?= e($f_pesquisa) ?>" placeholder="Nome, e-mail…">
            </div>
            <div class="col-md-2">
                <label class="form-label">Perfil</label>
                <select class="form-select" name="perfil">
                    <option value="">Todos</option>
                    <option value="admin"  <?= $f_perfil==='admin'?'selected':'' ?>>Admin</option>
                    <option value="lider"  <?= $f_perfil==='lider'?'selected':'' ?>>Líder</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select class="form-select" name="ativo">
                    <option value="">Todos</option>
                    <option value="1" <?= $f_ativo==='1'?'selected':'' ?>>Ativo</option>
                    <option value="0" <?= $f_ativo==='0'?'selected':'' ?>>Inativo</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                <a href="<?= APP_BASE ?>/usuarios" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
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
                    <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Estado</th><th>Ações</th></tr>
                </thead>
                <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($u['foto']) && file_exists(ROOT_PATH.'/'.$u['foto'])): ?>
                                    <img src="<?= APP_BASE.'/'.e($u['foto']) ?>" class="rounded-circle" style="width:32px;height:32px;object-fit:cover" alt="">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.85rem;color:#6b7280"><i class="bi bi-person"></i></div>
                                <?php endif; ?>
                                <?= e($u['nome']) ?>
                            </div>
                        </td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge <?= $u['perfil']==='admin'?'bg-primary':'bg-secondary' ?>"><?= ucfirst($u['perfil']) ?></span></td>
                        <td><span class="badge <?= $u['ativo']?'bg-success':'bg-danger' ?>"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    onclick='editUser(<?= json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS) ?>)'
                                    data-bs-toggle="modal" data-bs-target="#userModal" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ((int)$u['id'] !== $uid): ?>
                                <?php if (!$u['ativo']): ?>
                                <form method="post" action="<?= APP_BASE ?>/usuarios/<?= $u['id'] ?>/toggle" style="display:inline" onsubmit="return confirm('Ativar este usuário?')">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-success" title="Ativar"><i class="bi bi-check"></i></button>
                                </form>
                                <?php else: ?>
                                <form method="post" action="<?= APP_BASE ?>/usuarios/<?= $u['id'] ?>/toggle" style="display:inline" onsubmit="return confirm('Desativar este usuário?')">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Desativar"><i class="bi bi-x-lg"></i></button>
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

<!-- Create/Edit Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= APP_BASE ?>/usuarios" id="userForm" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="_uid" id="formUid" value="0">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" name="userName" id="fNome" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail *</label>
                            <input type="email" class="form-control" name="userEmail" id="fEmail" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Perfil</label>
                            <select class="form-select" name="userRole" id="fPerfil">
                                <option value="lider">Líder</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="userStatus" id="fAtivo">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Palavra-passe <span id="pwdHint" class="text-muted small">(opcional na edição)</span></label>
                            <input type="password" class="form-control" name="userPassword" id="fPwd" autocomplete="new-password">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto (JPG/PNG/WebP, máx 5MB)</label>
                            <input type="file" class="form-control" name="userFoto" accept=".jpg,.jpeg,.png,.webp">
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

<?php $inlineJs = '
var base="'.APP_BASE.'";
function newUser(){
  document.getElementById("userModalTitle").textContent="Novo Usuário";
  document.getElementById("userForm").action=base+"/usuarios";
  document.getElementById("formUid").value="0";
  document.getElementById("fNome").value="";
  document.getElementById("fEmail").value="";
  document.getElementById("fPerfil").value="lider";
  document.getElementById("fAtivo").value="ativo";
  document.getElementById("fPwd").required=true;
  document.getElementById("pwdHint").style.display="none";
}
function editUser(u){
  document.getElementById("userModalTitle").textContent="Editar Usuário";
  document.getElementById("userForm").action=base+"/usuarios/"+u.id;
  document.getElementById("formUid").value=u.id;
  document.getElementById("fNome").value=u.nome;
  document.getElementById("fEmail").value=u.email;
  document.getElementById("fPerfil").value=u.perfil;
  document.getElementById("fAtivo").value=u.ativo=="1"?"ativo":"inativo";
  document.getElementById("fPwd").required=false;
  document.getElementById("pwdHint").style.display="";
}
'; ?>
