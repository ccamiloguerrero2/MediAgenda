<?php
// --- mediagenda-backend/cambiar_estado_cita.php ---

error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al usuario
ini_set('log_errors', 1);     // Registrar errores en el log de PHP

session_start();
header('Content-Type: application/json');
include 'conexion.php'; // Asegura que $conexion esté disponible

// 1. Verificar Sesión y Método POST
if (!isset($_SESSION['idUsuario'])) {
    http_response_code(401); // No autorizado
    echo json_encode(["success" => false, "message" => "Sesión no iniciada."]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

$idUsuario = $_SESSION['idUsuario'];
$rolUsuario = $_SESSION['rolUsuario'] ?? null;

if (!$rolUsuario) {
     http_response_code(500);
     error_log("Error crítico: Rol de usuario no encontrado en la sesión para idUsuario: " . $idUsuario);
     echo json_encode(["success" => false, "message" => "Error interno: Rol de usuario no definido."]);
     exit;
}

// 2. Obtener y Validar Datos de POST
$idCita = filter_input(INPUT_POST, 'idCita', FILTER_VALIDATE_INT);
$nuevoEstado = trim($_POST['nuevoEstado'] ?? '');

if (!$idCita || empty($nuevoEstado)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (idCita, nuevoEstado)."]);
    exit;
}

// 3. Definir Estados y Transiciones Permitidas
// Lista de todos los estados válidos posibles en la BD
$estadosValidos = ['Programada', 'Confirmada', 'Cancelada Paciente', 'Cancelada Doctor', 'Completada', 'No Asistió']; // Añade más si los usas

// Validar que el nuevo estado sea uno de los permitidos
if (!in_array($nuevoEstado, $estadosValidos)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Estado solicitado inválido: " . htmlspecialchars($nuevoEstado)]);
    exit;
}

// Lógica de permisos: Quién puede cambiar a qué estado
$permitido = false;
if ($rolUsuario === 'medico') {
    // Médico puede confirmar, cancelar (como doctor), completar, marcar no asistió
    if (in_array($nuevoEstado, ['Confirmada', 'Cancelada Doctor', 'Completada', 'No Asistió'])) {
        $permitido = true;
    }
} elseif ($rolUsuario === 'paciente') {
    // Paciente solo puede cancelar (como paciente)
    if ($nuevoEstado === 'Cancelada Paciente') {
        $permitido = true;
    }
}
// Podrías añadir lógica para 'admin' o 'recepcionista' aquí si pueden cambiar estados

if (!$permitido) {
    http_response_code(403); // Prohibido
    error_log("Intento de cambio de estado no permitido por rol '$rolUsuario' a '$nuevoEstado' para cita $idCita (Usuario $idUsuario)");
    echo json_encode(["success" => false, "message" => "No tiene permiso para establecer este estado."]);
    exit;
}

// (Opcional) Podrías añadir validación del estado *actual* de la cita antes de permitir el cambio
// Ejemplo: No se puede 'Completar' una cita 'Cancelada'

$idPerfil = null; // Guardará idMedico o idPaciente
$campoVerificacion = null; // Guardará 'idMedico' o 'idPaciente' para la query

try {
    // 4. Obtener idMedico o idPaciente del usuario en sesión
    if ($rolUsuario === 'medico') {
        $sql_get_id = "SELECT idMedico FROM Medico WHERE idUsuario = ?";
        $campoVerificacion = 'idMedico';
    } elseif ($rolUsuario === 'paciente') {
        $sql_get_id = "SELECT idPaciente FROM Paciente WHERE idUsuario = ?";
        $campoVerificacion = 'idPaciente';
    } else {
        // Si hubiera un admin, podríamos no necesitar verificar idPerfil, pero sí loguear quién hizo el cambio
         throw new Exception("Rol no manejado para obtención de ID de perfil."); // O manejar lógica admin
    }

    $stmt_get_id = mysqli_prepare($conexion, $sql_get_id);
    if (!$stmt_get_id) throw new Exception("Error preparando subconsulta ID perfil: " . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt_get_id, "i", $idUsuario);
    mysqli_stmt_execute($stmt_get_id);
    $res_id = mysqli_stmt_get_result($stmt_get_id);
    $perfilRow = mysqli_fetch_assoc($res_id);
    mysqli_stmt_close($stmt_get_id);

    if (!$perfilRow) {
         throw new Exception("No se encontró el perfil ($rolUsuario) para el usuario actual.");
    }
    // Extraemos el valor del ID (ya sea idMedico o idPaciente)
    $idPerfil = $perfilRow[$campoVerificacion];

    // 5. Actualizar el Estado de la Cita Verificando Propiedad
    $sql_update = "UPDATE Cita SET estado = ? WHERE idCita = ? AND $campoVerificacion = ?";
    $stmt_update = mysqli_prepare($conexion, $sql_update);
    if ($stmt_update === false) {
        throw new Exception("Error al preparar update Cita: " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_update, "sii", $nuevoEstado, $idCita, $idPerfil);

    if (mysqli_stmt_execute($stmt_update)) {
        // 6. Verificar si la fila fue afectada (si no, no era dueño o la cita no existía)
        if (mysqli_stmt_affected_rows($stmt_update) > 0) {
            // Éxito
            echo json_encode(["success" => true, "message" => "Estado de la cita actualizado a '" . htmlspecialchars($nuevoEstado) . "'."]);
            // Aquí podrías disparar una notificación si tuvieras esa lógica
        } else {
            // No se actualizó ninguna fila: Cita no encontrada O no pertenece al usuario
            http_response_code(404); // O 403 si sospechas más de permisos
            error_log("No se actualizó estado para cita $idCita por usuario $idUsuario (Rol $rolUsuario, idPerfil $idPerfil). Cita no encontrada o sin permisos.");
            echo json_encode(["success" => false, "message" => "No se pudo actualizar la cita. Verifique si la cita existe y si tiene permisos."]);
        }
    } else {
        // Error SQL al ejecutar
        throw new Exception("Error al actualizar estado de la cita: " . mysqli_stmt_error($stmt_update));
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en cambiar_estado_cita.php (idCita: $idCita, nuevoEstado: $nuevoEstado, Usuario: $idUsuario): " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error del servidor al actualizar el estado."]); // Mensaje genérico
} finally {
    // 7. Limpiar
    if (isset($stmt_update) && $stmt_update) {
        mysqli_stmt_close($stmt_update);
    }
    mysqli_close($conexion);
}

exit;
?>