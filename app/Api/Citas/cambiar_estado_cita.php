<?php
/**
 * API Endpoint para Cambiar el Estado de una Cita
 *
 * Permite a usuarios autenticados (pacientes o médicos) cambiar el estado
 * de una cita específica, validando los permisos según el rol y el estado solicitado.
 *
 * @package MediAgenda\App\Api\Citas
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
// No se necesita auth_middleware aquí, la autorización es por rol y propiedad de la cita.
require_once PROJECT_ROOT . '/app/Core/session_utils.php'; // Para obtener rol e ID de usuario

// --- Verificar Autenticación y Rol Básico ---
if (!is_authenticated()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Debes iniciar sesión.']);
    exit;
}

$idUsuario = get_user_id();
$rolUsuario = get_user_role(); // 'paciente', 'medico', 'admin'

if ($rolUsuario !== 'paciente' && $rolUsuario !== 'medico') {
     // Por ahora, solo pacientes y médicos pueden cambiar estados
     // Si el admin pudiera, se añadiría lógica aquí o en un endpoint separado.
    http_response_code(403); // Forbidden
    error_log("Intento de cambio de estado por rol no permitido: '{$rolUsuario}' (Usuario ID: {$idUsuario})");
    echo json_encode(['success' => false, 'message' => 'Tu rol no permite realizar esta acción.']);
    exit;
}


// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
$idCita = filter_input(INPUT_POST, 'idCita', FILTER_VALIDATE_INT);
$nuevoEstado = trim($_POST['nuevoEstado'] ?? '');

$errors = [];
if (!$idCita) {
    $errors[] = "ID de cita inválido o faltante.";
}
// Lista de estados válidos definidos en la BD (ENUM)
$estadosValidos = ['Programada', 'Confirmada', 'Cancelada Paciente', 'Cancelada Doctor', 'Completada', 'No Asistió'];
if (empty($nuevoEstado)) {
    $errors[] = "El nuevo estado es obligatorio.";
} elseif (!in_array($nuevoEstado, $estadosValidos)) {
    $errors[] = "Estado solicitado inválido: '{$nuevoEstado}'.";
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
    if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit;
}


// --- Lógica de Permisos y Actualización (try...catch) ---
$idPerfil = null; // idMedico o idPaciente
$campoVerificacion = ''; // Nombre de la columna FK en Cita para verificar propiedad

try {
    // 1. Obtener el ID del perfil (Paciente o Medico) asociado al idUsuario en sesión
    $sql_get_id = "";
    if ($rolUsuario === 'paciente') {
        $sql_get_id = "SELECT idPaciente FROM Paciente WHERE idUsuario = ?";
        $campoVerificacion = 'idPaciente';
    } elseif ($rolUsuario === 'medico') {
        $sql_get_id = "SELECT idMedico FROM Medico WHERE idUsuario = ?";
        $campoVerificacion = 'idMedico';
    }

    $stmt_get_id = mysqli_prepare($conexion, $sql_get_id);
    if (!$stmt_get_id) throw new Exception("Error DB: Preparando obtención de ID de perfil - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_get_id, "i", $idUsuario);
    mysqli_stmt_execute($stmt_get_id);
    $res_id = mysqli_stmt_get_result($stmt_get_id);
    $perfilRow = mysqli_fetch_assoc($res_id);
    mysqli_stmt_close($stmt_get_id);

    if (!$perfilRow || !isset($perfilRow[$campoVerificacion])) {
        // Esto no debería ocurrir si el usuario está logueado con un rol válido,
        // indica un problema de integridad de datos.
        error_log("Error crítico: No se encontró el perfil {$campoVerificacion} para el usuario ID {$idUsuario} con rol {$rolUsuario}");
        throw new Exception("No se pudo verificar la identidad del usuario.");
    }
    $idPerfil = $perfilRow[$campoVerificacion];


    // 2. Validar Permiso de Cambio de Estado según Rol y Estado Solicitado
    $permitido = false;
    $estadoActual = null; // Se podría obtener para validaciones más complejas

    if ($rolUsuario === 'paciente') {
        // Paciente solo puede cancelar si está Programada o Confirmada
        if ($nuevoEstado === 'Cancelada Paciente') {
             // Opcional: Obtener estado actual para asegurar que solo cancela citas activas
             // $estadoActual = ... (requiere otra query)
             // if ($estadoActual === 'Programada' || $estadoActual === 'Confirmada') {
                  $permitido = true;
             // }
        }
    } elseif ($rolUsuario === 'medico') {
        // Médico puede confirmar, cancelar (como doctor), completar, marcar no asistió
        if (in_array($nuevoEstado, ['Confirmada', 'Cancelada Doctor', 'Completada', 'No Asistió'])) {
             // Opcional: Añadir lógica basada en estado actual
             // Ej: Solo puede 'Confirmar' si está 'Programada'
             // Ej: Solo puede 'Completar' o 'No Asistió' si está 'Confirmada'
             $permitido = true;
        }
    }

    if (!$permitido) {
        http_response_code(403); // Forbidden
        error_log("Intento de cambio de estado NO PERMITIDO por rol '{$rolUsuario}' a '{$nuevoEstado}' para cita {$idCita} (Usuario ID: {$idUsuario})");
        echo json_encode(['success' => false, 'message' => "No tienes permiso para establecer este estado para esta cita."]);
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }


    // 3. Actualizar el Estado en la Base de Datos
    // La consulta verifica que la cita exista Y pertenezca al médico/paciente que realiza la acción
    $sql_update = "UPDATE Cita SET estado = ? WHERE idCita = ? AND {$campoVerificacion} = ?";
    $stmt_update = mysqli_prepare($conexion, $sql_update);
    if (!$stmt_update) {
        throw new Exception("Error DB: Preparando actualización de estado de cita - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_update, "sii", $nuevoEstado, $idCita, $idPerfil);

    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Error DB: Ejecutando actualización de estado de cita - " . mysqli_stmt_error($stmt_update));
    }

    // 4. Verificar si la Actualización tuvo Éxito
    $affected_rows = mysqli_stmt_affected_rows($stmt_update);
    mysqli_stmt_close($stmt_update);

    if ($affected_rows > 0) {
        // --- Éxito ---
        error_log("Usuario ID {$idUsuario} ({$rolUsuario}) cambió estado de Cita ID {$idCita} a '{$nuevoEstado}'");
        // TODO: Aquí se podría añadir lógica para enviar notificaciones (si se implementa)
        echo json_encode(['success' => true, 'message' => "Estado de la cita actualizado a '" . htmlspecialchars($nuevoEstado, ENT_QUOTES, 'UTF-8') . "'."]);
    } else {
        // No se actualizó: Cita no encontrada O no pertenece al usuario
        http_response_code(404); // Not Found (o 403 Forbidden si es más probable que sea permiso)
        error_log("Fallo al actualizar estado de Cita ID {$idCita} a '{$nuevoEstado}' por Usuario ID {$idUsuario} ({$rolUsuario}, Perfil ID {$idPerfil}). Affected rows = 0.");
        // Consultar si la cita existe para dar un mensaje más preciso
        $sql_check_exists = "SELECT idCita FROM Cita WHERE idCita = ?";
        $stmt_check_exists = mysqli_prepare($conexion, $sql_check_exists);
        mysqli_stmt_bind_param($stmt_check_exists, "i", $idCita);
        mysqli_stmt_execute($stmt_check_exists);
        mysqli_stmt_store_result($stmt_check_exists);
        $cita_existe = mysqli_stmt_num_rows($stmt_check_exists) > 0;
        mysqli_stmt_close($stmt_check_exists);

        if ($cita_existe) {
             echo json_encode(['success' => false, 'message' => "No se pudo actualizar la cita. Puede que no tengas permiso sobre esta cita específica."]);
        } else {
             echo json_encode(['success' => false, 'message' => "No se pudo actualizar la cita. La cita no fue encontrada."]);
        }
    }

} catch (Exception $e) {
    // --- Manejo de Errores Inesperados ---
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " (Usuario ID: {$idUsuario}, Cita ID: {$idCita}, Nuevo Estado: {$nuevoEstado}): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al intentar actualizar el estado de la cita.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>