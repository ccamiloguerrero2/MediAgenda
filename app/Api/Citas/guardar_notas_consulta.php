<?php
/**
 * API Endpoint para Guardar Notas de Consulta Médica
 *
 * Permite a un médico autenticado guardar notas (diagnóstico/tratamiento)
 * asociadas a una cita específica que le pertenece.
 * Las notas se almacenan en la tabla 'HistorialMedico'.
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

// --- Verificar Autenticación y Rol Médico ---
if (!is_authenticated() || get_user_role() !== 'medico') {
    http_response_code(401); // Unauthorized o 403 Forbidden
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere rol de médico.']);
    exit;
}

$idUsuarioMedico = get_user_id();

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
// Espera 'idCitaActual' y 'diagnostico_tratamiento' del FormData enviado por JS
$idCita = filter_input(INPUT_POST, 'idCitaActual', FILTER_VALIDATE_INT);
$notas = trim($_POST['diagnostico_tratamiento'] ?? ''); // Contenido del textarea

$errors = [];
if (!$idCita) {
    $errors[] = "ID de cita inválido o faltante.";
}
if (empty($notas)) {
    $errors[] = "El campo de notas no puede estar vacío.";
}
// Podrías añadir una longitud máxima para las notas si es necesario.

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
    if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit;
}

// --- Lógica Principal (try...catch y transacción) ---
mysqli_begin_transaction($conexion);

try {
    // 1. Obtener idMedico del usuario en sesión
    $sql_get_medico_id = "SELECT idMedico FROM Medico WHERE idUsuario = ?";
    $stmt_get_medico_id = mysqli_prepare($conexion, $sql_get_medico_id);
    if (!$stmt_get_medico_id) throw new Exception("Error DB: Preparando obtención de ID de médico - " . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt_get_medico_id, "i", $idUsuarioMedico);
    mysqli_stmt_execute($stmt_get_medico_id);
    $res_medico_id = mysqli_stmt_get_result($stmt_get_medico_id);
    $medicoRow = mysqli_fetch_assoc($res_medico_id);
    mysqli_stmt_close($stmt_get_medico_id);

    if (!$medicoRow || !isset($medicoRow['idMedico'])) {
        throw new Exception("Perfil de médico no encontrado para el usuario actual.");
    }
    $idMedico = $medicoRow['idMedico'];

    // 2. Verificar que la Cita pertenece a este Médico y obtener idPaciente
    // También es buena idea verificar que la cita esté en un estado adecuado
    // (ej. Confirmada o Completada) antes de permitir guardar notas.
    $sql_verify_cita = "SELECT idPaciente, estado FROM Cita WHERE idCita = ? AND idMedico = ?";
    $stmt_verify_cita = mysqli_prepare($conexion, $sql_verify_cita);
    if (!$stmt_verify_cita) throw new Exception("Error DB: Preparando verificación de cita - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_verify_cita, "ii", $idCita, $idMedico);
    mysqli_stmt_execute($stmt_verify_cita);
    $res_verify_cita = mysqli_stmt_get_result($stmt_verify_cita);
    $citaRow = mysqli_fetch_assoc($res_verify_cita);
    mysqli_stmt_close($stmt_verify_cita);

    if (!$citaRow) {
        mysqli_rollback($conexion); // Revertir transacción
        http_response_code(404); // Not Found o 403 Forbidden
        echo json_encode(['success' => false, 'message' => 'La cita especificada no existe o no pertenece a este médico.']);
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }
    $idPaciente = $citaRow['idPaciente'];
    $estadoCitaActual = $citaRow['estado'];

    // Opcional: Validar estado de la cita
    // $estadosPermitidosParaNotas = ['Confirmada', 'Completada']; // Ajusta según tu lógica
    // if (!in_array($estadoCitaActual, $estadosPermitidosParaNotas)) {
    //     mysqli_rollback($conexion);
    //     http_response_code(400); // Bad Request
    //     echo json_encode(['success' => false, 'message' => "No se pueden añadir notas a una cita en estado '{$estadoCitaActual}'."]);
    //     if (isset($conexion) && $conexion) mysqli_close($conexion);
    //     exit;
    // }


    // 3. Insertar (o Actualizar) en HistorialMedico
    // Decisión: ¿Permitir múltiples notas por cita o sobrescribir?
    // Este código ASUME que se quiere INSERTAR una nueva nota cada vez.
    // Si se quisiera actualizar una nota existente para esa cita, se necesitaría un UPSERT o lógica UPDATE.
    $fechaActual = date('Y-m-d');
    // Separar notas en diagnóstico/tratamiento es complejo sin un formato estructurado.
    // Guardaremos todo en 'diagnostico' por simplicidad, asumiendo que 'tratamiento' puede ser NULL.
    $diagnostico_notas = $notas;
    $tratamiento_notas = null; // Dejar NULL o implementar lógica de parsing

    $sql_insert_historial = "INSERT INTO HistorialMedico (idPaciente, idMedico, idCita, fecha, diagnostico, tratamiento)
                             VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert_historial = mysqli_prepare($conexion, $sql_insert_historial);
    if (!$stmt_insert_historial) {
        throw new Exception("Error DB: Preparando inserción de historial - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt_insert_historial,
        "iiisss", // i: idPaciente, i: idMedico, i: idCita, s: fecha, s: diagnostico, s: tratamiento
        $idPaciente,
        $idMedico,
        $idCita,
        $fechaActual,
        $diagnostico_notas,
        $tratamiento_notas
    );

    if (!mysqli_stmt_execute($stmt_insert_historial)) {
        throw new Exception("Error DB: Ejecutando inserción de historial - " . mysqli_stmt_error($stmt_insert_historial));
    }

    if (mysqli_stmt_affected_rows($stmt_insert_historial) <= 0) {
        throw new Exception("Error DB: No se insertó el registro en HistorialMedico (affected_rows <= 0).");
    }
    mysqli_stmt_close($stmt_insert_historial);

    // 4. Confirmar Transacción
    mysqli_commit($conexion);
    error_log("Médico ID {$idUsuarioMedico} guardó notas para Cita ID {$idCita} (Paciente ID {$idPaciente})");

    // --- Respuesta Exitosa ---
    echo json_encode(['success' => true, 'message' => "Notas de consulta guardadas correctamente para la cita #{$idCita}."]);

} catch (Exception $e) {
    // --- Revertir Transacción en caso de error ---
    mysqli_rollback($conexion);
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " (Médico ID: {$idUsuarioMedico}, Cita ID: {$idCita}): " . $e->getMessage());
    // Enviar mensaje más específico si es posible, o genérico
    echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'Ocurrió un error interno al guardar las notas.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>