<?php
/**
 * API Endpoint para Programar una Nueva Cita (Paciente)
 *
 * Maneja las solicitudes POST enviadas desde el modal de agendamiento
 * del panel del paciente para crear una nueva cita en la base de datos.
 * Requiere autenticación de paciente.
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
require_once PROJECT_ROOT . '/app/Core/session_utils.php'; // Para obtener rol e ID de usuario

// --- Verificar Autenticación y Rol Paciente ---
if (!is_authenticated() || get_user_role() !== 'paciente') {
    http_response_code(401); // Unauthorized o 403 Forbidden
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Debes iniciar sesión como paciente.']);
    exit;
}

$idUsuarioPaciente = get_user_id();

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
// Nombres de campo esperados del formulario del modal (`#schedule-appointment-form`)
$idMedico = filter_input(INPUT_POST, 'idMedico', FILTER_VALIDATE_INT);
$fecha = trim($_POST['fecha'] ?? '');         // Espera YYYY-MM-DD
$hora = trim($_POST['hora'] ?? '');           // Espera HH:MM (o HH:MM:SS)
$motivo = trim($_POST['motivo'] ?? '');       // Opcional

$errors = [];
if (!$idMedico) {
    $errors[] = "Debes seleccionar un médico.";
}
if (empty($fecha)) {
    $errors[] = "La fecha de la cita es obligatoria.";
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $errors[] = "Formato de fecha inválido (se espera YYYY-MM-DD).";
} else {
    // Validación adicional: Fecha no debe ser pasada
    $fechaSeleccionada = strtotime($fecha);
    $fechaActual = strtotime(date('Y-m-d'));
    if ($fechaSeleccionada < $fechaActual) {
        $errors[] = "No puedes programar una cita en una fecha pasada.";
    }
}
if (empty($hora)) {
    $errors[] = "La hora de la cita es obligatoria.";
} elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) { // Acepta HH:MM o HH:MM:SS
     $errors[] = "Formato de hora inválido (se espera HH:MM).";
}
// Podría añadirse validación de longitud máxima para el motivo.

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
    if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit;
}

// --- Lógica Principal (try...catch y transacción) ---
mysqli_begin_transaction($conexion);

try {
    // 1. Obtener idPaciente del usuario en sesión
    $sql_get_paciente_id = "SELECT idPaciente FROM Paciente WHERE idUsuario = ?";
    $stmt_get_paciente_id = mysqli_prepare($conexion, $sql_get_paciente_id);
    if (!$stmt_get_paciente_id) throw new Exception("Error DB: Preparando obtención de ID de paciente - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_get_paciente_id, "i", $idUsuarioPaciente);
    mysqli_stmt_execute($stmt_get_paciente_id);
    $res_paciente_id = mysqli_stmt_get_result($stmt_get_paciente_id);
    $pacienteRow = mysqli_fetch_assoc($res_paciente_id);
    mysqli_stmt_close($stmt_get_paciente_id);

    if (!$pacienteRow || !isset($pacienteRow['idPaciente'])) {
        throw new Exception("Perfil de paciente no encontrado para el usuario actual.");
    }
    $idPaciente = $pacienteRow['idPaciente'];


    // 2. (CRUCIAL) Verificar Disponibilidad del Médico en esa Fecha y Hora
    // Esta es una verificación básica, una real sería más compleja (considerar duración, solapamientos).
    // Aquí verificamos si YA EXISTE una cita para ese médico en esa fecha y hora exacta.
    $sql_check_disponibilidad = "SELECT idCita FROM Cita
                                 WHERE idMedico = ? AND fecha = ? AND hora = ?
                                 AND estado NOT IN ('Cancelada Paciente', 'Cancelada Doctor') LIMIT 1"; // Excluir canceladas
    $stmt_check_disp = mysqli_prepare($conexion, $sql_check_disponibilidad);
    if (!$stmt_check_disp) throw new Exception("Error DB: Preparando verificación de disponibilidad - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_check_disp, "iss", $idMedico, $fecha, $hora);
    mysqli_stmt_execute($stmt_check_disp);
    mysqli_stmt_store_result($stmt_check_disp); // Necesario para num_rows

    if (mysqli_stmt_num_rows($stmt_check_disp) > 0) {
        mysqli_stmt_close($stmt_check_disp);
        mysqli_rollback($conexion); // Revertir aunque no se hizo nada aún
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'El horario seleccionado ya no está disponible para este médico. Por favor, elija otro horario o fecha.']);
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_close($stmt_check_disp);


    // 3. Insertar la Nueva Cita
    $estadoInicial = 'Programada'; // Estado por defecto al crear
    $motivo_db = !empty($motivo) ? $motivo : null; // Guardar NULL si el motivo está vacío

    $sql_insert_cita = "INSERT INTO Cita (idPaciente, idMedico, fecha, hora, motivo, estado)
                        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conexion, $sql_insert_cita);
    if (!$stmt_insert) {
        throw new Exception("Error DB: Preparando inserción de Cita - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt_insert,
        "iissss", // i: idPaciente, i: idMedico, s: fecha, s: hora, s: motivo, s: estado
        $idPaciente,
        $idMedico,
        $fecha,
        $hora,
        $motivo_db,
        $estadoInicial
    );

    if (!mysqli_stmt_execute($stmt_insert)) {
        // Podría fallar si idMedico no existe, o por otras restricciones
        throw new Exception("Error DB: Ejecutando inserción de Cita - " . mysqli_stmt_error($stmt_insert));
    }

    if (mysqli_stmt_affected_rows($stmt_insert) <= 0) {
        throw new Exception("Error DB: No se insertó el registro de Cita (affected_rows <= 0).");
    }

    $nuevaCitaId = mysqli_insert_id($conexion); // Obtener ID de la nueva cita
    mysqli_stmt_close($stmt_insert);


    // 4. Confirmar Transacción
    mysqli_commit($conexion);
    error_log("Paciente ID {$idUsuarioPaciente} programó Cita ID {$nuevaCitaId} con Médico ID {$idMedico} para {$fecha} {$hora}");
    // TODO: Aquí se podría añadir lógica para crear una Notificación para el médico.


    // --- Respuesta Exitosa ---
    echo json_encode(['success' => true, 'message' => "Cita programada correctamente para el {$fecha} a las {$hora}."]);

} catch (Exception $e) {
    // --- Revertir Transacción en caso de error ---
    mysqli_rollback($conexion);
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " (Usuario Paciente ID: {$idUsuarioPaciente}, Médico ID: {$idMedico}, Fecha: {$fecha}, Hora: {$hora}): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al intentar programar la cita.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>