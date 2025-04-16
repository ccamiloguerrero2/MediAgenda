<?php
/**
 * API Endpoint para Cerrar la Sesión del Usuario
 *
 * Destruye la sesión PHP actual del usuario y lo redirige a la
 * página principal (index.php) con un parámetro GET para indicar
 * que el logout fue exitoso (útil para mostrar notificaciones).
 *
 * @package MediAgenda\App\Api\Auth
 */

// --- Inicio de Sesión (Necesario para acceder y destruir la sesión) ---
// Es importante iniciarla antes de intentar destruirla.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Destruir Variables de Sesión ---
// Elimina todas las variables almacenadas en $_SESSION.
$_SESSION = array();
error_log("Variables de sesión limpiadas para ID: " . (session_id() ?: 'N/A')); // Log opcional

// --- Invalidar Cookie de Sesión ---
// Si se usan cookies para la sesión (comportamiento por defecto),
// se recomienda eliminar la cookie del navegador estableciendo una
// fecha de expiración pasada.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Tiempo en el pasado
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"] // Asegura que se use la misma config que al crearla
    );
    error_log("Cookie de sesión invalidada para ID: " . (session_id() ?: 'N/A')); // Log opcional
}

// --- Destruir la Sesión Completamente ---
// Elimina el archivo de sesión del servidor.
session_destroy();
error_log("Sesión destruida para ID: " . (session_id() ?: 'N/A')); // Log opcional

// --- Redirección ---
// Redirige al usuario a la página principal.
// La ruta '../..' sube dos niveles desde app/Api/Auth/ para llegar a la raíz
// y luego entra a /public/index.php. La URL final será simplemente '/index.php'
// porque public/ es el DocumentRoot.
// Añadimos '?logout=success' para que scripts.js pueda detectar el logout
// y mostrar una notificación.
header('Location: /index.php?logout=success'); // Ruta absoluta desde DocumentRoot
exit(); // Detiene la ejecución del script inmediatamente después de la redirección.

?>