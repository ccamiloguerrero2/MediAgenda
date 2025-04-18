<?php
/**
 * Middleware de Autenticación y Autorización para Administradores
 *
 * Este script se incluye (`require_once`) al principio de cualquier endpoint
 * de la API que deba ser accesible únicamente por usuarios con el rol 'admin'.
 *
 * Verifica si existe una sesión activa y si el rol del usuario en sesión
 * es 'admin'. Si alguna de estas condiciones no se cumple, detiene la
 * ejecución del script que lo incluyó, envía una respuesta JSON de error
 * con el código HTTP apropiado (401 o 403), y termina.
 *
 * Si la verificación es exitosa, el script que lo incluyó continúa su ejecución
 * normal.
 *
 * Depende de que `session_start()` ya haya sido llamado y que las variables
 * de sesión `$_SESSION['idUsuario']` y `$_SESSION['rolUsuario']` estén definidas
 * correctamente durante el login.
 *
 * @package MediAgenda\App\Core
 */

// --- Modo Estricto ---
declare(strict_types=1);

// --- Asegurar que la Sesión Esté Iniciada ---
// Aunque los scripts que lo incluyen deberían iniciarla, es una buena práctica verificar.
if (session_status() === PHP_SESSION_NONE) {
    // Si no se inició, es un error de configuración o flujo.
    // Loguear este caso es importante.
    error_log("Error Crítico en auth_middleware.php: La sesión no fue iniciada antes de incluir el middleware.");
    // Enviar un error 500 genérico, ya que es un fallo interno.
    http_response_code(500);
    // Asegurar que la salida sea JSON si es posible
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor [Auth Config].']);
    exit; // Detener ejecución inmediatamente
}

// --- Verificar Autenticación ---
// Usando la función helper si está disponible (requiere incluir session_utils ANTES en el script principal)
// O directamente verificando la variable de sesión. Usaremos la variable directa aquí
// para que este middleware no dependa explícitamente de session_utils.php (aunque en la práctica sí lo hará).
if (!isset($_SESSION['idUsuario']) || empty($_SESSION['idUsuario'])) {
    http_response_code(401); // Unauthorized
    // Limpiar buffer de salida si existe
    if (ob_get_level() > 0) ob_end_clean();
    // Enviar respuesta JSON de error
    header('Content-Type: application/json'); // Reasegurar header
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere iniciar sesión.']);
    // Cerrar conexión a BD si ya se estableció en el script principal (opcional pero buena práctica)
    // if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit; // Detener ejecución
}

// --- Verificar Rol de Administrador ---
if (!isset($_SESSION['rolUsuario']) || strtolower($_SESSION['rolUsuario']) !== 'admin') {
    http_response_code(403); // Forbidden
    // Loguear el intento de acceso no autorizado
    $userIdAttempt = $_SESSION['idUsuario'];
    $userRoleAttempt = $_SESSION['rolUsuario'] ?? 'desconocido';
    error_log("Acceso denegado (Rol no admin): Usuario ID {$userIdAttempt} con rol '{$userRoleAttempt}' intentó acceder a un recurso de administrador.");
    // Limpiar buffer de salida
    if (ob_get_level() > 0) ob_end_clean();
    // Enviar respuesta JSON de error
    header('Content-Type: application/json'); // Reasegurar header
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere rol de administrador.']);
    // if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit; // Detener ejecución
}

// --- Verificación Exitosa ---
// Si el script llega hasta aquí, significa que el usuario está autenticado
// y tiene el rol 'admin'. El script que incluyó este archivo puede continuar.
// Opcional: Loguear acceso permitido para auditoría
// error_log("Acceso de administrador permitido para Usuario ID: {$_SESSION['idUsuario']}");

// No añadir ?>