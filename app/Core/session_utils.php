<?php
/**
 * Utilidades de Sesión y Autenticación
 *
 * Este script se incluye al principio de las páginas y endpoints que
 * necesitan interactuar con la sesión del usuario.
 *
 * Responsabilidades:
 * 1. Inicia o reanuda la sesión PHP de forma segura.
 * 2. Define variables globales convenientes para verificar el estado de login,
 *    obtener datos básicos del usuario (nombre, rol) y determinar enlaces
 *    dinámicos (panel de usuario, botón de agendar).
 * 3. Proporciona funciones helper reutilizables para verificar autenticación y roles.
 *
 * @package MediAgenda\App\Core
 */

// --- Modo Estricto ---
declare(strict_types=1);

// Iniciar (o reanudar) la sesión
if (session_status() === PHP_SESSION_NONE) {
    if (!session_start()) {
        // Fallo crítico al iniciar sesión
        error_log("Error Crítico: session_start() falló en session_utils.php");
        // Evitar mostrar error directo, podría fallar en API endpoints que esperan JSON
        exit;
    }
}

// --- Variables Globales Derivadas de la Sesión ---

/**
 * Indica si el usuario actual está autenticado.
 * @var bool $loggedIn
 */
$loggedIn = isset($_SESSION['idUsuario']) && !empty($_SESSION['idUsuario']);

/**
 * Nombre del usuario autenticado (o string vacío si no está logueado).
 * @var string $nombreUsuario
 */
$nombreUsuario = $loggedIn ? htmlspecialchars($_SESSION['nombreUsuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') : '';

/**
 * Rol del usuario autenticado en minúsculas (o string vacío si no está logueado).
 * @var string $rolUsuario ('paciente', 'medico', 'admin', '')
 */
$rolUsuario = $loggedIn ? strtolower($_SESSION['rolUsuario'] ?? '') : '';

/**
 * Enlace dinámico al panel correspondiente del usuario logueado,
 * o a la página de inicio si no está logueado.
 * Usa rutas absolutas desde la raíz pública.
 * @var string $panelLink
 */
$panelLink = '/index.php'; // Default si no está logueado o rol desconocido
if ($loggedIn) {
    switch ($rolUsuario) {
        case 'paciente':
            $panelLink = '/perfil-usuario.php';
            break;
        case 'medico':
            $panelLink = '/perfil-doctores.php';
            break;
        case 'admin':
            $panelLink = '/panel-admin-sistema.php';
            break;
        // No default necesario, ya se estableció a index.php
    }
}

/**
 * Enlace dinámico para el botón/enlace "Agendar Cita".
 * - Si no está logueado, va a la página de registro.
 * - Si es paciente, va a su panel (donde está el modal o sección).
 * - Si es médico, va a su panel (no debería agendar para sí mismo).
 * - Si es admin, va a su panel (no agenda citas).
 * Usa rutas absolutas desde la raíz pública.
 * @var string $agendarCitaLink
 */
$agendarCitaLink = '/registro.php'; // Default si no está logueado
if ($loggedIn) {
    // Si está logueado, el enlace generalmente va a su propio panel,
    // excepto si es admin (que no agenda). La lógica de mostrar/ocultar
    // el botón debe estar en el menú/vista.
    $agendarCitaLink = $panelLink; // Apunta al panel correspondiente por defecto

    // Podrías refinarlo si quieres que vaya a una sección específica:
    // if ($rolUsuario === 'paciente') {
    //     $agendarCitaLink = '/perfil-usuario.php#schedule'; // Ejemplo con ancla
    // }
}

// --- Funciones Helper de Autenticación y Roles ---

/**
 * Verifica si el usuario actual está autenticado.
 * @return bool True si el usuario tiene una sesión válida, False en caso contrario.
 */
function is_authenticated(): bool
{
    // Re-verifica por si acaso, aunque $loggedIn ya existe.
    return isset($_SESSION['idUsuario']) && !empty($_SESSION['idUsuario']);
}

/**
 * Verifica si el usuario actual tiene el rol de administrador.
 * @return bool True si el rol es 'admin', False en caso contrario.
 */
function is_admin(): bool
{
    // Compara en minúsculas para evitar problemas de case-sensitivity.
    return isset($_SESSION['rolUsuario']) && strtolower($_SESSION['rolUsuario']) === 'admin';
}

/**
 * Obtiene el ID del usuario autenticado.
 * @return int|null El ID del usuario o null si no está autenticado.
 */
function get_user_id(): ?int
{
    // Devuelve int o null para tipado más estricto.
    return isset($_SESSION['idUsuario']) ? (int)$_SESSION['idUsuario'] : null;
}

/**
 * Obtiene el nombre del usuario autenticado.
 * @param bool $sanitize Define si sanitizar la salida con htmlspecialchars (predeterminado: true).
 * @return string El nombre del usuario o un string vacío.
 */
function get_user_name(bool $sanitize = true): string
{
    $name = $_SESSION['nombreUsuario'] ?? '';
    return $sanitize ? htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : $name;
}

/**
 * Obtiene el rol del usuario autenticado en minúsculas.
 * @return string El rol ('paciente', 'medico', 'admin') o un string vacío.
 */
function get_user_role(): string
{
    // Devuelve siempre en minúsculas.
    return isset($_SESSION['rolUsuario']) ? strtolower($_SESSION['rolUsuario']) : '';
}

?>