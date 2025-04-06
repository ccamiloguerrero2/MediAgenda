<?php
// --- mediagenda-backend/guardar_notas_consulta.php ---

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');
include 'conexion.php';

// Verificar Sesión Médico y Método POST
if (!isset($_SESSION['idUsuario']) || ($_SESSION['rolUsuario'] ?? '') !== 'medico') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Acceso no autorizado o sesión inválida."]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

$idUsuarioMedico = $_SESSION['idUsuario'];

// Obtener y Validar Datos POST
// Esperamos 'idCitaActual' (del form JS) y 'diagnostico_tratamiento' (del textarea)
$idCita = filter_input(INPUT_POST, 'idCitaActual', FILTER_VALIDATE_INT);
$notas = trim($_POST['diagnostico_tratamiento'] ?? '');

if (!$idCita || empty($notas)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta ID de cita o las notas de la consulta."]);
    exit;
}

$stmt_verify = null;
$stmt_insert = null;
$stmt_get_id = null;

mysqli_begin_transaction($conexion); // Usar transacción por si acaso

try {
    // Obtener idMedico del usuario en sesión
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

    // Verificar que la cita pertenece a este médico y obtener idPaciente
    $sql_verify = "SELECT idPaciente FROM Cita WHERE idCita = ? AND idMedico = ?";
    $stmt_verify = mysqli_prepare($conexion, $sql_verify);
    if (!$stmt_verify) throw new Exception("Error preparando verificación de cita: " . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt_verify, "ii", $idCita, $idMedico);
    mysqli_stmt_execute($stmt_verify);
    $res_verify = mysqli_stmt_get_result($stmt_verify);
    $citaRow = mysqli_fetch_assoc($res_verify);
    mysqli_stmt_close($stmt_verify);

    if (!$citaRow) {
         http_response_code(403); // Prohibido o no encontrado
         throw new Exception("La cita especificada no existe o no pertenece a este médico.");
    }
    $idPaciente = $citaRow['idPaciente'];

    // Insertar o actualizar historial (simplificado: siempre inserta, podrías hacer UPSERT)
    $fechaActual = date('Y-m-d');
    // Dividimos notas en diagnóstico/tratamiento si es posible (o guardar todo en uno)
    // Aquí guardamos todo en 'diagnostico' por simplicidad
    $diagnostico = $notas;
    $tratamiento = null; // O intentar parsear

    $sql_insert = "INSERT INTO HistorialMedico (idPaciente, idMedico, idCita, fecha, diagnostico, tratamiento)
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conexion, $sql_insert);
    if ($stmt_insert === false) {
        throw new Exception("Error al preparar insert historial: " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_insert, "iiisss", $idPaciente, $idMedico, $idCita, $fechaActual, $diagnostico, $tratamiento);

    if (!mysqli_stmt_execute($stmt_insert)) {
        throw new Exception("Error al guardar las notas: " . mysqli_stmt_error($stmt_insert));
    }

    if (mysqli_stmt_affected_rows($stmt_insert) > 0) {
        mysqli_commit($conexion);
        echo json_encode(["success" => true, "message" => "Notas de consulta guardadas correctamente."]);
    } else {
        throw new Exception("No se insertaron las notas (Affected Rows = 0).");
    }

} catch (Exception $e) {
    mysqli_rollback($conexion);
    // Determinar código HTTP basado en el mensaje podría ser útil
    $errorCode = ($e->getCode() == 403) ? 403 : 500;
    http_response_code($errorCode);
    error_log("Error en guardar_notas_consulta.php (Medico: $idUsuarioMedico, Cita: $idCita): " . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]); // Mostrar mensaje de error específico
} finally {
    if ($stmt_insert) mysqli_stmt_close($stmt_insert);
    mysqli_close($conexion);
}
exit;
?>