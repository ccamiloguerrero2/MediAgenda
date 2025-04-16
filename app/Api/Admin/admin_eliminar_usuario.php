<?php
/**
 * API Endpoint para Eliminar un Usuario (Admin)
 *
 * Este script maneja las solicitudes POST enviadas desde el panel de administración
 * para eliminar un usuario específico del sistema.
 * Requiere autenticación de administrador. La eliminación en la tabla 'Usuario'
 * debería propagarse (CASCADE) a las tablas relacionadas (Paciente, Medico, Cita, etc.)
 * si las claves foráneas están configuradas correctamente en la BD.
 *
 * @package MediAgenda\App\Api\Admin
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// --- Inicio de Sesión ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Encabezado de Respuesta ---
header('Content-Type: application/json');

// --- Definir Ruta Raíz ---
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
require_once PROJECT_ROOT . '/app/Core/database.php';       // Conexión a la BD ($conexion)
require_once PROJECT_ROOT . '/app/Core/auth_middleware.php'; // Verifica si el usuario es admin

// --- Verificar Método HTTP ---
// Es crucial usar POST para acciones destructivas para prevenir CSRF accidental vía GET.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar ID de Usuario ---
$idUsuario = filter_input(INPUT_POST, 'idUsuario', FILTER_VALIDATE_INT);

if (!$idUsuario) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido o faltante.']);
    exit;
}

// --- Prevención de Auto-eliminación ---
$idUsuarioAdminActual = $_SESSION['idUsuario'] ?? null; // Obtener ID del admin que realiza la acción
if ($idUsuario === $idUsuarioAdminActual) {
     http_response_code(403); // Forbidden
     echo json_encode(['success' => false, 'message' => 'Acción no permitida: No puedes eliminar tu propia cuenta de administrador.']);
     // Cerrar conexión si existe
     if (isset($conexion) && $conexion) mysqli_close($conexion);
     exit;
}

// --- Lógica de Eliminación (dentro de try...catch) ---
mysqli_begin_transaction($conexion); // Usar transacción por si hay operaciones adicionales o triggers

try {
    // NOTA: Asumimos que las claves foráneas con ON DELETE CASCADE están configuradas
    // en la BD (ej., en Paciente, Medico, Cita referenciando Usuario.idUsuario).
    // Si no es así, necesitarías eliminar manualmente las filas relacionadas ANTES
    // de eliminar el usuario para evitar errores de restricción de FK.

    // 1. Eliminar de la tabla Usuario
    $sql_delete_usuario = "DELETE FROM Usuario WHERE idUsuario = ?";
    $stmt_delete_usuario = mysqli_prepare($conexion, $sql_delete_usuario);
    if (!$stmt_delete_usuario) {
        throw new Exception("Error DB: Preparando eliminación de Usuario - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_delete_usuario, "i", $idUsuario);

    if (!mysqli_stmt_execute($stmt_delete_usuario)) {
        // Podría fallar si hay restricciones FK que NO son CASCADE y hay datos relacionados
        throw new Exception("Error DB: Ejecutando eliminación de Usuario - " . mysqli_stmt_error($stmt_delete_usuario));
    }

    $affected_rows = mysqli_stmt_affected_rows($stmt_delete_usuario);
    mysqli_stmt_close($stmt_delete_usuario);

    // 2. Verificar si se eliminó algo
    if ($affected_rows > 0) {
        // Éxito, confirmar transacción
        mysqli_commit($conexion);
        error_log("Admin ID {$_SESSION['idUsuario']} eliminó Usuario ID: {$idUsuario}"); // Log de auditoría
        echo json_encode(['success' => true, 'message' => "Usuario ID {$idUsuario} eliminado correctamente."]);
    } else {
        // No se encontró el usuario (o quizás un error raro)
        mysqli_rollback($conexion); // Revertir aunque probablemente no hizo nada
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => "No se encontró el usuario con ID {$idUsuario} o ya había sido eliminado."]);
    }

} catch (Exception $e) {
    // --- Manejo de Errores ---
    mysqli_rollback($conexion); // Revertir transacción en caso de cualquier error
    http_response_code(500); // Internal Server Error
    error_log("Error en " . basename(__FILE__) . " al intentar eliminar Usuario ID {$idUsuario}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al intentar eliminar el usuario.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>