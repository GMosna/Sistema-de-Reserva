<!DOCTYPE html>
<html lang="pt-BR">
<head><?php include VIEWS_PATH . '/includes/head.php'; ?></head>
<body>
<div class="app-shell">
    <?php include VIEWS_PATH . '/includes/sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <div class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle" title="Ocultar menu">
                    <i class="bi bi-list" style="font-size:1.1rem"></i>
                </button>
                <div>
                    <div class="fw-600" style="font-size:.9rem;line-height:1.2"><?= e($pageTitle ?? '') ?></div>
                    <?php if (!empty($pageSubtitle)): ?>
                    <div class="text-muted" style="font-size:.75rem"><?= e($pageSubtitle) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <?php if (!empty($user['foto']) && file_exists(ROOT_PATH . '/' . $user['foto'])): ?>
                        <img src="<?= APP_BASE . '/' . e($user['foto']) ?>" class="rounded-circle" style="width:28px;height:28px;object-fit:cover" alt="">
                    <?php else: ?>
                        <i class="bi bi-person-circle" style="font-size:1.2rem"></i>
                    <?php endif; ?>
                    <span class="d-none d-sm-inline"><?= e($user['nome'] ?? '') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text text-muted small"><?= e($user['email'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a href="<?= APP_BASE ?>/logout" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-2"></i>Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="p-4">
            <?php include VIEWS_PATH . '/components/alert.php'; ?>
            <?= $content ?>
        </div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index:1100"></div>
<?php include VIEWS_PATH . '/includes/footer.php'; ?>
</body>
</html>
