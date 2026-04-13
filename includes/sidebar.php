<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user = currentUser();
?>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="p-3">
        <div class="d-flex align-items-center mb-4">
            <img src="assets/logo-sassi.png" alt="Sassi" width="38" height="38" class="rounded me-2">
            <div>
                <h6 class="text-white mb-0">Sassi Imóveis</h6>
                <small class="text-white-50">Reserva de Salas</small>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'reservas') ? 'active' : ''; ?>" href="reservas.php">
                    <i class="bi bi-calendar-check me-2"></i>
                    Reservas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'nova_reserva') ? 'active' : ''; ?>" href="nova_reserva.php">
                    <i class="bi bi-plus-circle me-2"></i>
                    Nova Reserva
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'recorrencias') ? 'active' : ''; ?>" href="recorrencias.php">
                    <i class="bi bi-arrow-repeat me-2"></i>
                    Recorrências
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'salas') ? 'active' : ''; ?>" href="salas.php">
                    <i class="bi bi-door-open me-2"></i>
                    Salas
                </a>
            </li>
            <?php if ($user['perfil'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'usuarios') ? 'active' : ''; ?>" href="usuarios.php">
                    <i class="bi bi-people me-2"></i>
                    Usuários
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <div class="mt-auto pt-4 border-top border-secondary">
            <div class="d-flex align-items-center mb-2 px-2">
                <i class="bi bi-person-circle text-white-50 me-2"></i>
                <small class="text-white-50"><?php echo htmlspecialchars($user['nome']); ?></small>
            </div>
            <a class="nav-link text-danger" href="logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>
                Sair
            </a>
        </div>
    </div>
</nav>