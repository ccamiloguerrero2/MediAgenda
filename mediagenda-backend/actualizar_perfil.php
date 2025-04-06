<?php
// --- mediagenda-backend/actualizar_perfil.php ---

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

// 2. Obtener y Validar Datos de POST (¡Ajusta los nombres según tu HTML!)
$nombre = trim($_POST['nombre_completo'] ?? ''); // Espera name="nombre_completo" en el form
$email = trim($_POST['email'] ?? '');           // Espera name="email"
// Campos específicos de rol
$telefono = trim($_POST['telefono'] ?? '');       // Espera name="telefono" (para paciente)
$direccion = trim($_POST['direccion'] ?? '');     // Espera name="direccion" (para paciente)
$especialidad = trim($_POST['especialidad'] ?? ''); // Espera name="especialidad" (para medico)
$horario = trim($_POST['horario'] ?? '');         // Espera name="horario" (para medico) - Podría ser más complejo

// Validación básica (puedes añadir más)
if (empty($nombre) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Nombre y correo son obligatorios."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Formato de correo electrónico inválido."]);
    exit;
}
// Aquí podrías añadir validación de teléfono, etc.

// 3. Iniciar Transacción
mysqli_begin_transaction($conexion);

try {
    // 4. Actualizar Tabla Usuario (Nombre y Email)
    // Opcional: Verificar si el nuevo email ya existe para otro usuario
    // $sql_check_email = "SELECT idUsuario FROM Usuario WHERE email = ? AND idUsuario != ?"; ...

    $sql_update_usuario = "UPDATE Usuario SET nombre = ?, email = ? WHERE idUsuario = ?";
    $stmt_usuario = mysqli_prepare($conexion, $sql_update_usuario);
    if ($stmt_usuario === false) throw new Exception("Error al preparar update Usuario: " . mysqli_error($conexion));
    mysqli_stmt_bind_param($stmt_usuario, "ssi", $nombre, $email, $idUsuario);
    if (!mysqli_stmt_execute($stmt_usuario)) throw new Exception("Error al ejecutar update Usuario: " . mysqli_stmt_error($stmt_usuario));
    mysqli_stmt_close($stmt_usuario);

    // 5. Actualizar Tabla Específica del Rol
    if ($rolUsuario === 'paciente') {
        // Asegúrate que la tabla Paciente exista y tenga estas columnas
        $sql_update_paciente = "UPDATE Paciente SET telefono = ?, direccion = ? WHERE idUsuario = ?";
        $stmt_paciente = mysqli_prepare($conexion, $sql_update_paciente);
        if ($stmt_paciente === false) throw new Exception("Error al preparar update Paciente: " . mysqli_error($conexion));
        // Asumiendo que telefono y direccion pueden ser NULL si están vacíos
        $telefono_db = !empty($telefono) ? $telefono : null;
        $direccion_db = !empty($direccion) ? $direccion : null;
        mysqli_stmt_bind_param($stmt_paciente, "ssi", $telefono_db, $direccion_db, $idUsuario);
        if (!mysqli_stmt_execute($stmt_paciente)) throw new Exception("Error al ejecutar update Paciente: " . mysqli_stmt_error($stmt_paciente));
        mysqli_stmt_close($stmt_paciente);

    } elseif ($rolUsuario === 'medico') {
        // Asegúrate que la tabla Medico exista y tenga estas columnas
        $sql_update_medico = "UPDATE Medico SET especialidad = ?, horario = ? WHERE idUsuario = ?";
        $stmt_medico = mysqli_prepare($conexion, $sql_update_medico);
         if ($stmt_medico === false) throw new Exception("Error al preparar update Medico: " . mysqli_error($conexion));
        // Asumiendo que pueden ser NULL si están vacíos
        $especialidad_db = !empty($especialidad) ? $especialidad : null;
        $horario_db = !empty($horario) ? $horario : null;
        mysqli_stmt_bind_param($stmt_medico, "ssi", $especialidad_db, $horario_db, $idUsuario);
        if (!mysqli_stmt_execute($stmt_medico)) throw new Exception("Error al ejecutar update Medico: " . mysqli_stmt_error($stmt_medico));
        mysqli_stmt_close($stmt_medico);
    }
    // Añadir 'elseif' para otros roles si es necesario

    // 6. Confirmar Transacción
    mysqli_commit($conexion);

    // 7. Actualizar nombre en sesión (opcional, para que se refleje en el header inmediatamente)
    $_SESSION['nombreUsuario'] = $nombre;

    echo json_encode(["success" => true, "message" => "Perfil actualizado correctamente."]);

} catch (Exception $e) {
    // 8. Revertir Transacción en caso de error
    mysqli_rollback($conexion);
    http_response_code(500);
    error_log("Error en actualizar_perfil.php para idUsuario $idUsuario: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error del servidor al actualizar el perfil: " . $e->getMessage()]); // Puedes usar un mensaje más genérico
} finally {
    // 9. Limpiar
    mysqli_close($conexion);
}

exit;
?>