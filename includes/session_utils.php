<?php
// =================== SESIÓN Y ENLACES DINÁMICOS ===================
session_start();
$loggedIn = isset($_SESSION['idUsuario']) && !empty($_SESSION['idUsuario']);
$nombreUsuario = $loggedIn ? ($_SESSION['nombreUsuario'] ?? 'Usuario') : '';
$rolUsuario = $loggedIn ? strtolower($_SESSION['rolUsuario'] ?? '') : '';
// Determina el enlace al panel según el rol
$panelLink = 'index.php';
if ($loggedIn) {
    switch ($rolUsuario) {
        case 'paciente': $panelLink = 'perfil-usuario.php'; break;
        case 'medico': $panelLink = 'perfil-doctores.php'; break;
        case 'admin': $panelLink = 'panel-admin-sistema.php'; break;
    }
}
// Enlace para el botón "Agendar Cita"
$agendarCitaLink = (!$loggedIn) ? 'registro.php' : ($rolUsuario === 'paciente' ? 'perfil-usuario.php' : $panelLink);

// ========== FUNCIONES DE UTILIDAD PARA AUTENTICACIÓN Y ROLES ==========
function is_authenticated() {
    return isset($_SESSION['idUsuario']) && !empty($_SESSION['idUsuario']);
}
function is_admin() {
    return isset($_SESSION['rolUsuario']) && strtolower($_SESSION['rolUsuario']) === 'admin';
}
function get_user_id() {
    return $_SESSION['idUsuario'] ?? null;
}
function get_user_name() {
    return $_SESSION['nombreUsuario'] ?? '';
}
function get_user_role() {
    return isset($_SESSION['rolUsuario']) ? strtolower($_SESSION['rolUsuario']) : '';
}
