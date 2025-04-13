<?php
// --- mediagenda-backend/programar_cita.php ---

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');
include 'conexion.php';

// 1. Verificar Sesión y Método POST
if (!isset($_SESSION['idUsuario'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión no iniciada."]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

$idUsuario = $_SESSION['idUsuario'];
$rolUsuario = $_SESSION['rolUsuario'] ?? null;

// Solo los pacientes pueden programar citas (por ahora)
if ($rolUsuario !== 'paciente') {
     http_response_code(403); // Prohibido
     echo json_encode(["success" => false, "message" => "Solo los pacientes pueden programar citas."]);
     exit;
}

// 2. Obtener y Validar Datos de POST (¡Ajusta los nombres según tu form HTML!)
$idMedico = filter_input(INPUT_POST, 'idMedico', FILTER_VALIDATE_INT); // Espera name="idMedico" (debería ser un <select>)
$fecha = trim($_POST['fecha'] ?? '');         // Espera name="fecha" (input type="date")
$hora = trim($_POST['hora'] ?? '');           // Espera name="hora" (input type="time" o <select>)
$motivo = trim($_POST['motivo'] ?? '');         // Espera name="motivo" (textarea opcional)

// Validación básica
if (!$idMedico || empty($fecha) || empty($hora)) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (Médico, Fecha, Hora)."]);
    exit;
}

// Validación de formato de fecha y hora (simplificada)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
     echo json_encode(["success" => false, "message" => "Formato de fecha inválido (YYYY-MM-DD)."]);
     exit;
}
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) { // Acepta HH:MM o HH:MM:SS
     echo json_encode(["success" => false, "message" => "Formato de hora inválido (HH:MM)."]);
     exit;
}

// Validación adicional (ej. fecha no pasada, médico existe, hora disponible - más complejo)
// Por ahora, asumimos que los datos son válidos si el formato es correcto

try {
    // 3. Obtener idPaciente del usuario en sesión
    $sql_get_id = "SELECT idPaciente FROM Paciente WHERE idUsuario = ?";
    $stmt_get_id = mysqli_prepare($conexion, $sql_get_id);
    if(!$stmt_get_id) throw new Exception("Error preparando subconsulta idPaciente: ".mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt_get_id, "i", $idUsuario);
    mysqli_stmt_execute($stmt_get_id);
    $res_id = mysqli_stmt_get_result($stmt_get_id);
    $pacienteRow = mysqli_fetch_assoc($res_id);
    mysqli_stmt_close($stmt_get_id);

    if (!$pacienteRow) {
         throw new Exception("No se encontró el perfil de paciente para el usuario actual.");
    }
    $idPaciente = $pacienteRow['idPaciente'];

    // 4. Insertar la Nueva Cita
    $estadoInicial = 'Programada';
    $sql_insert = "INSERT INTO Cita (fecha, hora, motivo, idPaciente, idMedico, estado)
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conexion, $sql_insert);
    if ($stmt_insert === false) throw new Exception("Error al preparar insert Cita: " . mysqli_error($conexion));

    // Preparar motivo (NULL si está vacío)
    $motivo_db = !empty($motivo) ? $motivo : null;

    mysqli_stmt_bind_param($stmt_insert, "sssiss", $fecha, $hora, $motivo_db, $idPaciente, $idMedico, $estadoInicial);

    if (mysqli_stmt_execute($stmt_insert)) {
        if (mysqli_stmt_affected_rows($stmt_insert) > 0) {
            // 5. Éxito
            echo json_encode(["success" => true, "message" => "Cita programada correctamente."]);
        } else {
            // Raro que no afecte filas si la ejecución fue ok, pero por si acaso
            throw new Exception("La cita no se insertó, aunque no hubo error SQL.");
        }
    } else {
        // Error al ejecutar (podría ser por FK inválida, duplicado si hubiera UNIQUE, etc.)
        throw new Exception("Error al registrar la cita: " . mysqli_stmt_error($stmt_insert));
    }

} catch (Exception $e) {
    http_response_code(500);
    // Loguear el error específico para depuración interna
    error_log("Error en programar_cita.php (idUsuario: $idUsuario, idMedico: $idMedico, Fecha: $fecha, Hora: $hora): " . $e->getMessage());
    // Enviar mensaje genérico y seguro al usuario final
    echo json_encode(["success" => false, "message" => "Ocurrió un error inesperado al intentar programar la cita. Por favor, inténtalo más tarde."]);
} finally {
    // 6. Limpiar
    if (isset($stmt_insert) && $stmt_insert) {
        mysqli_stmt_close($stmt_insert);
    }
    mysqli_close($conexion);
}

exit;
?>