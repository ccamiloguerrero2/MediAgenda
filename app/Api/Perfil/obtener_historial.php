<?php
/**
 * API Endpoint para Obtener el Historial Médico de un Paciente
 *
 * Recupera los registros de la tabla 'HistorialMedico' asociados al
 * paciente autenticado. Incluye el nombre del médico que realizó
 * cada registro.
 * Requiere autenticación de paciente.
 *
 * @package MediAgenda\App\Api\Perfil
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

// --- Lógica Principal (try...catch) ---
$historial = []; // Array para almacenar los registros

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
        // Si no tiene perfil de paciente, no tendrá historial
        error_log("Advertencia: No se encontró perfil Paciente para Usuario ID {$idUsuarioPaciente} con rol 'paciente'.");
        echo json_encode(['success' => true, 'historial' => []]); // Devolver lista vacía
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }
    $idPaciente = $pacienteRow['idPaciente'];

    // 2. Preparar la Consulta SQL para obtener el historial
    // Seleccionar los campos relevantes del historial y unir con Medico->Usuario
    // para obtener el nombre del médico que registró la nota.
    // Ordenar por fecha descendente para mostrar lo más reciente primero.
    $sql_historial = "SELECT
                          hm.idHistorial,
                          hm.fecha,
                          hm.diagnostico,
                          hm.tratamiento,
                          hm.idCita, /* Opcional: ID de la cita asociada */
                          u_medico.nombre AS nombreMedico
                      FROM HistorialMedico hm
                      INNER JOIN Medico m ON hm.idMedico = m.idMedico
                      INNER JOIN Usuario u_medico ON m.idUsuario = u_medico.idUsuario
                      WHERE hm.idPaciente = ?
                      ORDER BY hm.fecha DESC, hm.idHistorial DESC"; // Orden más reciente primero

    $stmt_historial = mysqli_prepare($conexion, $sql_historial);
    if (!$stmt_historial) {
        throw new Exception("Error DB: Preparando consulta de historial médico - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_historial, "i", $idPaciente);

    // 3. Ejecutar Consulta y Recolectar Resultados
    if (!mysqli_stmt_execute($stmt_historial)) {
        throw new Exception("Error DB: Ejecutando consulta de historial médico - " . mysqli_stmt_error($stmt_historial));
    }
    $resultado = mysqli_stmt_get_result($stmt_historial);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Opcional: Formatear o limpiar datos si es necesario antes de enviar
        $historial[] = $fila;
    }
    mysqli_stmt_close($stmt_historial); // Cerrar statement

    // 4. Enviar Respuesta Exitosa
    echo json_encode(['success' => true, 'historial' => $historial]);

} catch (Exception $e) {
    // --- Manejo de Errores Inesperados ---
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " (Usuario Paciente ID: {$idUsuarioPaciente}): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al obtener el historial médico.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>