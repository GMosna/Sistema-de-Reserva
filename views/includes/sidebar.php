<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath    = APP_BASE;

function navActive(string $path): string {
    global $currentPath, $basePath;
    return str_starts_with($currentPath, $basePath . $path) ? ' active' : '';
}
?>
<nav class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <img src="<?= ASSETS_BASE ?>/img/logo-s.png" alt="Sassi" onerror="this.style.display='none'">
        <div>
            <div class="brand-text">Sassi Imóveis</div>
            <div class="brand-sub">Reserva de Salas</div>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section">Menu</div>

        <a href="<?= APP_BASE ?>/dashboard" class="nav-link<?= navActive('/dashboard') ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>
        <a href="<?= APP_BASE ?>/reservas" class="nav-link<?= navActive('/reservas') ?>">
            <i class="bi bi-calendar-check"></i><span>Reservas</span>
        </a>
        <a href="<?= APP_BASE ?>/reservas/nova" class="nav-link<?= navActive('/reservas/nova') ?>">
            <i class="bi bi-plus-circle"></i><span>Nova Reserva</span>
        </a>
        <a href="<?= APP_BASE ?>/recorrencias" class="nav-link<?= navActive('/recorrencias') ?>">
            <i class="bi bi-arrow-repeat"></i><span>Recorrências</span>
        </a>
        <a href="<?= APP_BASE ?>/salas" class="nav-link<?= navActive('/salas') ?>">
            <i class="bi bi-door-open"></i><span>Salas</span>
        </a>
        <a href="<?= APP_BASE ?>/graficos" class="nav-link<?= navActive('/graficos') ?>">
            <i class="bi bi-bar-chart-line"></i><span>Gráficos</span>
        </a>

        <?php if (($_SESSION['usuario_perfil'] ?? '') === 'admin'): ?>
        <div class="nav-section">Administração</div>
        <a href="<?= APP_BASE ?>/usuarios" class="nav-link<?= navActive('/usuarios') ?>">
            <i class="bi bi-people"></i><span>Usuários</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-user">
        <?php if (!empty($user['foto']) && file_exists(ROOT_PATH . '/' . $user['foto'])): ?>
            <img src="<?= APP_BASE . '/' . e($user['foto']) ?>" alt="" style="object-fit:cover">
        <?php else: ?>
            <div class="avatar-placeholder"><i class="bi bi-person"></i></div>
        <?php endif; ?>
        <div style="overflow:hidden">
            <div class="user-name"><?= e($user['nome'] ?? '') ?></div>
            <div class="user-role"><?= ucfirst($user['perfil'] ?? '') ?></div>
        </div>
    </div>
</nav>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
