<?php
// --- mediagenda-backend/obtener_citas.php ---

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');
include 'conexion.php';

// 1. Verificar Sesión
if (!isset($_SESSION['idUsuario'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión no iniciada."]);
    exit;
}

$idUsuario = $_SESSION['idUsuario'];
// El rol para el que se piden las citas viene por GET (?rol=paciente o ?rol=medico)
$rolSolicitado = $_GET['rol'] ?? null;

// Validación simple del rol solicitado
if ($rolSolicitado !== 'paciente' && $rolSolicitado !== 'medico') {
     http_response_code(400); // Bad Request
     echo json_encode(["success" => false, "message" => "Rol solicitado inválido."]);
     exit;
}

$citas = [];
$sql = "";
$stmt = null;

try {
    // 2. Preparar Consulta SQL según el Rol Solicitado
    if ($rolSolicitado === 'paciente') {
        // Obtener idPaciente a partir del idUsuario en sesión
        $sql_get_id = "SELECT idPaciente FROM Paciente WHERE idUsuario = ?";
        $stmt_get_id = mysqli_prepare($conexion, $sql_get_id);
        if(!$stmt_get_id) throw new Exception("Error preparando subconsulta idPaciente: ".mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt_get_id, "i", $idUsuario);
        mysqli_stmt_execute($stmt_get_id);
        $res_id = mysqli_stmt_get_result($stmt_get_id);
        $pacienteRow = mysqli_fetch_assoc($res_id);
        mysqli_stmt_close($stmt_get_id);

        if (!$pacienteRow) {
             // Si no es un paciente, no tiene citas como paciente
             echo json_encode(["success" => true, "citas" => []]);
             mysqli_close($conexion);
             exit;
        }
        $idPaciente = $pacienteRow['idPaciente'];

        // Consulta principal para citas del paciente
        $sql = "SELECT c.idCita, c.fecha, c.hora, c.motivo, c.estado,
                       u_medico.nombre AS nombreMedico, m.especialidad AS especialidadMedico
                FROM Cita c
                JOIN Medico m ON c.idMedico = m.idMedico
                JOIN Usuario u_medico ON m.idUsuario = u_medico.idUsuario
                WHERE c.idPaciente = ?
                ORDER BY c.fecha DESC, c.hora DESC"; // Ordenar por más reciente primero
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt === false) throw new Exception("Error al preparar consulta citas paciente: " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt, "i", $idPaciente);

    } else { // rolSolicitado === 'medico'
         // Obtener idMedico a partir del idUsuario en sesión
        $sql_get_id = "SELECT idMedico FROM Medico WHERE idUsuario = ?";
        $stmt_get_id = mysqli_prepare($conexion, $sql_get_id);
        if(!$stmt_get_id) throw new Exception("Error preparando subconsulta idMedico: ".mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt_get_id, "i", $idUsuario);
        mysqli_stmt_execute($stmt_get_id);
        $res_id = mysqli_stmt_get_result($stmt_get_id);
        $medicoRow = mysqli_fetch_assoc($res_id);
        mysqli_stmt_close($stmt_get_id);

         if (!$medicoRow) {
             // Si no es un médico, no tiene citas como médico
             echo json_encode(["success" => true, "citas" => []]);
             mysqli_close($conexion);
             exit;
        }
        $idMedico = $medicoRow['idMedico'];

        // Consulta principal para citas del médico
        $sql = "SELECT c.idCita, c.fecha, c.hora, c.motivo, c.estado,
                       u_paciente.nombre AS nombrePaciente, p.telefono AS telefonoPaciente
                FROM Cita c
                JOIN Paciente p ON c.idPaciente = p.idPaciente
                JOIN Usuario u_paciente ON p.idUsuario = u_paciente.idUsuario
                WHERE c.idMedico = ?
                ORDER BY c.fecha ASC, c.hora ASC"; // Ordenar por próximas primero
        $stmt = mysqli_prepare($conexion, $sql);
         if ($stmt === false) throw new Exception("Error al preparar consulta citas médico: " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt, "i", $idMedico);
    }

    // 3. Ejecutar Consulta y Obtener Datos
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $citas[] = $fila; // Añadir cada fila al array de citas
    }

    // 4. Devolver Datos Exitosamente
    echo json_encode(["success" => true, "citas" => $citas]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en obtener_citas.php (rol: $rolSolicitado, idUsuario: $idUsuario): " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error del servidor al obtener las citas."]);
} finally {
    // 5. Limpiar
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);
}

exit;
?>