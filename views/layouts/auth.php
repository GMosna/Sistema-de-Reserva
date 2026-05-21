<!DOCTYPE html>
<html lang="pt-BR">
<head>
<?php include VIEWS_PATH . '/includes/head.php'; ?>
<style>
body { margin:0; min-height:100vh; overflow:hidden; }
.login-bg { position:fixed; inset:0; z-index:0; background:url("<?= ASSETS_BASE ?>/img/bg.png") center/cover no-repeat; filter:blur(5px); transform:scale(1.02); }
.login-overlay { position:fixed; inset:0; z-index:1; background:rgba(0,0,0,.5); }
.login-wrap { position:relative; z-index:2; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
.login-card {
  background:rgba(255,255,255,.12); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid rgba(255,255,255,.2); border-radius:20px;
  box-shadow:0 8px 40px rgba(0,0,0,.45), 0 20px 60px rgba(0,0,0,.30);
  padding:2.5rem 2rem; width:100%; max-width:420px;
}
.login-card h3, .login-card .form-label { color:#fff; }
.login-card .text-muted { color:rgba(255,255,255,.7) !important; }
.login-card .form-control {
  background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2);
  color:#fff; transition:border-color .2s, box-shadow .2s;
}
.login-card .form-control::placeholder { color:rgba(255,255,255,.45); }
.login-card .form-control:focus { background:rgba(255,255,255,.15); border-color:var(--primary); box-shadow:0 0 0 3px rgba(220,38,38,.3); color:#fff; }
.login-card .input-group-text { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.2); color:rgba(255,255,255,.7); }
.login-card .btn-outline-secondary { border:1px solid rgba(255,255,255,.2); color:rgba(255,255,255,.7); background:rgba(255,255,255,.08); }
.login-card .btn-outline-secondary:hover { background:rgba(255,255,255,.15); color:#fff; }
.login-card .btn-primary { background:#991B1B; border:none; padding:.65rem; font-weight:600; }
.login-card .btn-primary:hover { background:#7F1D1D; }
.login-card .alert-danger { background:rgba(239,68,68,.2); border:1px solid rgba(239,68,68,.4); color:#fee2e2; }
.login-footer { color:rgba(255,255,255,.5); }
.logo { width:56px; height:56px; border-radius:16px; overflow:hidden; margin:0 auto 1rem; box-shadow:0 4px 20px rgba(0,0,0,.45); }
.logo img { width:100%; height:100%; object-fit:cover; }
</style>
</head>
<body>
<div class="login-bg"></div>
<div class="login-overlay"></div>
<div class="login-wrap">
    <?= $content ?>
</div>
<?php include VIEWS_PATH . '/includes/footer.php'; ?>
</body>
</html>
