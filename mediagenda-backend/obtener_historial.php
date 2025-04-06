<?php
// --- mediagenda-backend/obtener_historial.php ---

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');
include 'conexion.php';

// Verificar Sesión Paciente
if (!isset($_SESSION['idUsuario']) || ($_SESSION['rolUsuario'] ?? '') !== 'paciente') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Acceso no autorizado o sesión inválida."]);
    exit;
}

$idUsuario = $_SESSION['idUsuario'];
$historial = [];
$stmt_hist = null;
$stmt_get_id = null;

try {
    // Obtener idPaciente
    $sql_get_id = "SELECT idPaciente FROM Paciente WHERE idUsuario = ?";
    $stmt_get_id = mysqli_prepare($conexion, $sql_get_id);
    if (!$stmt_get_id) throw new Exception("Error preparando subconsulta idPaciente: " . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt_get_id, "i", $idUsuario);
    mysqli_stmt_execute($stmt_get_id);
    $res_id = mysqli_stmt_get_result($stmt_get_id);
    $pacienteRow = mysqli_fetch_assoc($res_id);
    mysqli_stmt_close($stmt_get_id);

    if (!$pacienteRow) {
        throw new Exception("Perfil de paciente no encontrado.");
    }
    $idPaciente = $pacienteRow['idPaciente'];

    // Obtener Historial Médico
    $sql_hist = "SELECT hm.idHistorial, hm.fecha, hm.diagnostico, hm.tratamiento,
                        u_medico.nombre AS nombreMedico
                 FROM HistorialMedico hm
                 JOIN Medico m ON hm.idMedico = m.idMedico
                 JOIN Usuario u_medico ON m.idUsuario = u_medico.idUsuario
                 WHERE hm.idPaciente = ?
                 ORDER BY hm.fecha DESC, hm.idHistorial DESC"; // Ordenar por fecha más reciente

    $stmt_hist = mysqli_prepare($conexion, $sql_hist);
    if ($stmt_hist === false) {
        throw new Exception("Error al preparar consulta historial: " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_hist, "i", $idPaciente);
    mysqli_stmt_execute($stmt_hist);
    $resultado = mysqli_stmt_get_result($stmt_hist);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $historial[] = $fila;
    }

    echo json_encode(["success" => true, "historial" => $historial]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en obtener_historial.php (Usuario: $idUsuario): " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error del servidor al obtener el historial."]);
} finally {
    if ($stmt_hist) mysqli_stmt_close($stmt_hist);
    mysqli_close($conexion);
}
exit;
?>