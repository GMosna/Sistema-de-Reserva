<?php
session_start();

require_once __DIR__ . '/../config/conexao.php';

/**
 * Require the user to be logged in.
 * Redirects to login.php if no valid session.
 */
function requireLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require the logged-in user to have the 'admin' role.
 * Redirects to dashboard.php with an error if not admin.
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['usuario_perfil'] !== 'admin') {
        $_SESSION['flash_error'] = 'Acesso restrito a administradores.';
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Return the currently logged-in user's data from session.
 */
function currentUser() {
    return [
        'id'          => $_SESSION['usuario_id']     ?? null,
        'nome'        => $_SESSION['usuario_nome']    ?? '',
        'email'       => $_SESSION['usuario_email']   ?? '',
        'perfil'      => $_SESSION['usuario_perfil']  ?? '',
        'foto'        => $_SESSION['usuario_foto']    ?? '',
    ];
}

function isAdmin() {
    return ($_SESSION['usuario_perfil'] ?? '') === 'admin';
}

function isLider() {
    return ($_SESSION['usuario_perfil'] ?? '') === 'lider';
}

/**
 * Check whether the logged-in user can manage (edit/cancel/delete) a reservation.
 * Admin can manage any reservation; lider can only manage their own.
 */
function canManageReservation($reservationUserId, $loggedUserId, $perfil) {
    if ($perfil === 'admin') return true;
    return (int)$reservationUserId === (int)$loggedUserId;
}

/**
 * Set a flash message into session.
 */
function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Retrieve and clear a flash message.
 */
function getFlash($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Render a Bootstrap alert if a flash message exists.
 */
function renderFlash() {
    $success = getFlash('success');
    $error   = getFlash('error');
    $warning = getFlash('warning');

    if ($success) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
           . '<i class="bi bi-check-circle me-2"></i>' . htmlspecialchars($success)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    if ($error) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
           . '<i class="bi bi-exclamation-triangle me-2"></i>' . htmlspecialchars($error)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    if ($warning) {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">'
           . '<i class="bi bi-exclamation-circle me-2"></i>' . htmlspecialchars($warning)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf() {
    $token = $_GET['csrf'] ?? $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        setFlash('error', 'Ação inválida. Tente novamente.');
        return false;
    }
    return true;
}
