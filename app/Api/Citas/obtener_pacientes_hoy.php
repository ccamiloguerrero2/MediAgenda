<?php
/**
 * API Endpoint para Obtener los Pacientes Agendados para Hoy (Médico)
 *
 * Recupera una lista de las citas programadas para la fecha actual
 * correspondientes al médico autenticado. Incluye información básica
 * del paciente y el estado de la cita.
 * Requiere autenticación de médico.
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

// --- Lógica Principal (try...catch) ---
$pacientesHoy = []; // Array para almacenar los resultados

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
        // Esto indica un problema de integridad si el rol es 'medico'
        error_log("Error crítico: No se encontró perfil Medico para Usuario ID {$idUsuarioMedico} con rol 'medico'");
        throw new Exception("Perfil de médico no encontrado.");
    }
    $idMedico = $medicoRow['idMedico'];

    // 2. Obtener la Fecha Actual
    $fechaHoy = date('Y-m-d');

    // 3. Preparar la Consulta SQL para obtener citas de hoy del médico
    // Seleccionar ID de la cita, hora, nombre del paciente y estado de la cita.
    // Filtrar por idMedico y fecha actual.
    // Ordenar por hora ascendente.
    $sql_pacientes = "SELECT
                          c.idCita,
                          c.hora,
                          c.estado, /* Incluir estado para mostrarlo */
                          u_paciente.nombre AS nombrePaciente
                      FROM Cita c
                      INNER JOIN Paciente p ON c.idPaciente = p.idPaciente
                      INNER JOIN Usuario u_paciente ON p.idUsuario = u_paciente.idUsuario
                      WHERE c.idMedico = ? AND c.fecha = ?
                      ORDER BY c.hora ASC";

    $stmt_pacientes = mysqli_prepare($conexion, $sql_pacientes);
    if (!$stmt_pacientes) {
        throw new Exception("Error DB: Preparando consulta de pacientes de hoy - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_pacientes, "is", $idMedico, $fechaHoy);

    // 4. Ejecutar Consulta y Recolectar Resultados
    if (!mysqli_stmt_execute($stmt_pacientes)) {
        throw new Exception("Error DB: Ejecutando consulta de pacientes de hoy - " . mysqli_stmt_error($stmt_pacientes));
    }
    $resultado = mysqli_stmt_get_result($stmt_pacientes);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Opcional: Podrías añadir más datos del paciente si fueran necesarios
        // (ej. teléfono, obtenido del JOIN con Paciente).
        $pacientesHoy[] = $fila;
    }
    mysqli_stmt_close($stmt_pacientes); // Cerrar statement

    // 5. Enviar Respuesta Exitosa
    echo json_encode(['success' => true, 'pacientes' => $pacientesHoy]);

} catch (Exception $e) {
    // --- Manejo de Errores Inesperados ---
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " (Médico ID: {$idUsuarioMedico}): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al obtener los pacientes del día.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>