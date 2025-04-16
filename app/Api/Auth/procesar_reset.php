<?php
/**
 * API Endpoint para Procesar la Solicitud de Reseteo de Contraseña
 *
 * Verifica el token de reseteo proporcionado, valida la nueva contraseña
 * y, si todo es correcto, actualiza la contraseña del usuario en la base de datos
 * y elimina el token utilizado.
 *
 * @package MediAgenda\App\Api\Auth
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// --- Encabezado de Respuesta ---
header('Content-Type: application/json');

// --- Definir Ruta Raíz ---
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
// No se requiere sesión aquí, la autorización se basa en el token.
require_once PROJECT_ROOT . '/app/Core/database.php'; // Conexión a la BD ($conexion)

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
$token = trim($_POST['token'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

$errors = [];
if (empty($token)) {
    // Podríamos ser más específicos, pero un token inválido genérico es más seguro.
    $errors[] = "Solicitud inválida o token faltante.";
}
if (empty($newPassword)) {
    $errors[] = "La nueva contraseña es obligatoria.";
} elseif (strlen($newPassword) < 6) {
    $errors[] = "La nueva contraseña debe tener al menos 6 caracteres.";
}
if (empty($confirmPassword)) {
    $errors[] = "La confirmación de contraseña es obligatoria.";
} elseif ($newPassword !== $confirmPassword) {
    $errors[] = "Las contraseñas introducidas no coinciden.";
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
    if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit;
}

// --- Lógica Principal (dentro de try...catch y transacción) ---
mysqli_begin_transaction($conexion);

try {
    // 1. Verificar validez y expiración del token y obtener el email asociado
    $email = null;
    $currentTime = date('Y-m-d H:i:s');
    $sql_check_token = "SELECT email FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1";
    $stmt_check = mysqli_prepare($conexion, $sql_check_token);
    if (!$stmt_check) throw new Exception("Error DB: Preparando verificación de token - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_check, "ss", $token, $currentTime);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $tokenData = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if ($tokenData && isset($tokenData['email'])) {
        $email = $tokenData['email'];
        error_log("Token válido encontrado para procesar reseteo de contraseña para email: " . $email);
    } else {
        // Token no encontrado o expirado
        mysqli_rollback($conexion); // Revertir aunque no se hizo nada aún
        http_response_code(400); // Bad Request (o 404 Not Found)
        echo json_encode(['success' => false, 'message' => 'El enlace de restablecimiento es inválido o ha expirado. Por favor, solicita uno nuevo.']);
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }

    // 2. Hashear la Nueva Contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    if ($hashedPassword === false) {
        error_log("Error crítico al hashear nueva contraseña para email: " . $email);
        throw new Exception("Error interno al procesar la nueva contraseña.");
    }

    // 3. Actualizar Contraseña en la Tabla Usuario
    $sql_update_pass = "UPDATE Usuario SET password = ? WHERE email = ?";
    $stmt_update = mysqli_prepare($conexion, $sql_update_pass);
    if (!$stmt_update) throw new Exception("Error DB: Preparando actualización de contraseña - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_update, "ss", $hashedPassword, $email);
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Error DB: Ejecutando actualización de contraseña - " . mysqli_stmt_error($stmt_update));
    }

    // Verificar si se actualizó (opcional, podría no actualizarse si la nueva pass es igual a la vieja)
    $affected_rows = mysqli_stmt_affected_rows($stmt_update);
    mysqli_stmt_close($stmt_update);
    error_log("Contraseña actualizada para email: {$email}. Affected rows: {$affected_rows}");


    // 4. Eliminar el Token Usado de la Tabla password_resets
    $sql_delete_token = "DELETE FROM password_resets WHERE email = ?"; // Eliminar todos los tokens para ese email
    $stmt_delete = mysqli_prepare($conexion, $sql_delete_token);
    if (!$stmt_delete) throw new Exception("Error DB: Preparando eliminación de token - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_delete, "s", $email);
    if (!mysqli_stmt_execute($stmt_delete)) {
        // No lanzar excepción aquí, pero sí loguear, ya que la contraseña se actualizó
        error_log("ADVERTENCIA: No se pudo eliminar el token de reseteo para email {$email} - Error: " . mysqli_stmt_error($stmt_delete));
    } else {
        error_log("Token de reseteo eliminado para email: " . $email);
    }
    mysqli_stmt_close($stmt_delete);

    // 5. Confirmar Transacción
    mysqli_commit($conexion);

    // --- Respuesta Exitosa ---
    echo json_encode(['success' => true, 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.']);

} catch (Exception $e) {
    // --- Manejo de Errores (Revertir Transacción) ---
    mysqli_rollback($conexion);
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " para token {$token}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al actualizar la contraseña.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>