<?php
// --- mediagenda-backend/obtener_pacientes_hoy.php ---

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');
include 'conexion.php';

// Verificar Sesión Médico
if (!isset($_SESSION['idUsuario']) || ($_SESSION['rolUsuario'] ?? '') !== 'medico') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Acceso no autorizado o sesión inválida."]);
    exit;
}

$idUsuarioMedico = $_SESSION['idUsuario'];
$pacientesHoy = [];
$stmt_pac = null;
$stmt_get_id = null;

try {
    // Obtener idMedico
    $sql_get_id = "SELECT idMedico FROM Medico WHERE idUsuario = ?";
    $stmt_get_id = mysqli_prepare($conexion, $sql_get_id);
    if (!$stmt_get_id) throw new Exception("Error preparando subconsulta idMedico: ".mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt_get_id, "i", $idUsuarioMedico);
    mysqli_stmt_execute($stmt_get_id);
    $res_id = mysqli_stmt_get_result($stmt_get_id);
    $medicoRow = mysqli_fetch_assoc($res_id);
    mysqli_stmt_close($stmt_get_id);
    if (!$medicoRow) throw new Exception("Perfil de médico no encontrado.");
    $idMedico = $medicoRow['idMedico'];

    // Obtener Citas de Hoy para este Médico
    $fechaHoy = date('Y-m-d');
    $sql_pac = "SELECT c.idCita, c.hora, u_paciente.nombre AS nombrePaciente, c.motivo, c.estado
                FROM Cita c
                JOIN Paciente p ON c.idPaciente = p.idPaciente
                JOIN Usuario u_paciente ON p.idUsuario = u_paciente.idUsuario
                WHERE c.idMedico = ? AND c.fecha = ?
                ORDER BY c.hora ASC"; // Ordenar por hora

    $stmt_pac = mysqli_prepare($conexion, $sql_pac);
    if ($stmt_pac === false) {
        throw new Exception("Error al preparar consulta pacientes hoy: " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_pac, "is", $idMedico, $fechaHoy);
    mysqli_stmt_execute($stmt_pac);
    $resultado = mysqli_stmt_get_result($stmt_pac);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $pacientesHoy[] = $fila;
    }

    echo json_encode(["success" => true, "pacientes" => $pacientesHoy]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en obtener_pacientes_hoy.php (Medico: $idUsuarioMedico): " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error del servidor al obtener los pacientes del día."]);
} finally {
    if ($stmt_pac) mysqli_stmt_close($stmt_pac);
    mysqli_close($conexion);
}
exit;
?>