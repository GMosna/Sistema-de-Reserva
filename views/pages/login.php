<?php $pageTitle = 'Login'; ?>
<div class="login-card">
    <div class="text-center mb-4">
        <div class="logo"><img src="<?= ASSETS_BASE ?>/img/logo-s.png" alt="Sassi" onerror="this.style.display='none'"></div>
        <h3 class="fw-bold">Sassi Imóveis</h3>
        <p class="text-muted mb-0">Sistema de Reserva de Salas</p>
    </div>

    <?php if (!empty($erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= e($erro) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1) brightness(2)"></button>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= APP_BASE ?>/login">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" placeholder="seu@email.com" required>
            </div>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Palavra-passe</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
        </button>
    </form>

    <div class="text-center mt-3 login-footer">
        <small>&copy; <?= date('Y') ?> Sassi Imóveis</small>
    </div>
</div>
<?php $inlineJs = '
document.getElementById("togglePwd").addEventListener("click",function(){
    var p=document.getElementById("password"), i=this.querySelector("i");
    p.type=p.type==="password"?"text":"password";
    i.classList.toggle("bi-eye"); i.classList.toggle("bi-eye-slash");
});
'; ?>
