<?php
// --- mediagenda-backend/admin_crear_usuario.php ---
session_start();
header('Content-Type: application/json');
include 'conexion.php';
require 'verificar_admin.php';

// Verificar Método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

// Obtener y validar datos (simplificado)
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? ''; // Contraseña para el nuevo usuario
$rol = $_POST['rol'] ?? ''; // Rol seleccionado ('paciente', 'medico', 'admin')

// Validación básica
if (empty($nombre) || empty($email) || empty($password) || empty($rol)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { /* ... error ... */ exit; }
if (strlen($password) < 6) { /* ... error ... */ exit; }
$rolesPermitidos = ['paciente', 'medico', 'admin']; // Roles que el admin puede crear
if (!in_array($rol, $rolesPermitidos)) {
     http_response_code(400);
     echo json_encode(["success" => false, "message" => "Rol inválido seleccionado."]);
     exit;
}

// Verificar si email ya existe
$sql_check = "SELECT idUsuario FROM Usuario WHERE email = ?";
$stmt_check = mysqli_prepare($conexion, $sql_check);
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);
if (mysqli_stmt_num_rows($stmt_check) > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "El correo electrónico ya está registrado."]);
    mysqli_stmt_close($stmt_check); mysqli_close($conexion); exit;
}
mysqli_stmt_close($stmt_check);

// Hashear contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insertar en BD
$sql_insert = "INSERT INTO Usuario (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
$stmt_insert = mysqli_prepare($conexion, $sql_insert);

if ($stmt_insert) {
    mysqli_stmt_bind_param($stmt_insert, "ssss", $nombre, $email, $password_hash, $rol);
    if (mysqli_stmt_execute($stmt_insert)) {
        if (mysqli_stmt_affected_rows($stmt_insert) > 0) {
             // TODO OPCIONAL: Si el rol es 'medico' o 'paciente', insertar también en la tabla Medico/Paciente
             // requeriría obtener el idUsuario recién insertado (mysqli_insert_id)
             // y recibir datos adicionales del form (especialidad, telefono). Por simplicidad, no lo hacemos aquí.
            echo json_encode(["success" => true, "message" => "Usuario creado correctamente."]);
        } else {
            throw new Exception("No se insertó el usuario (affected_rows = 0).");
        }
    } else {
        throw new Exception("Error al ejecutar insert: " . mysqli_stmt_error($stmt_insert));
    }
    mysqli_stmt_close($stmt_insert);
} else {
    throw new Exception("Error al preparar insert: " . mysqli_error($conexion));
}

mysqli_close($conexion);
exit;

// Captura básica de excepciones (podría ir en un bloque try/catch más formal)
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) { // Captura errores fatales
        http_response_code(500);
        error_log("Error fatal en admin_crear_usuario.php: " . $error['message'] . " en " . $error['file'] . ":" . $error['line']);
        // Evitar mostrar JSON si ya hubo salida o headers enviados
        if (!headers_sent()) {
             header('Content-Type: application/json'); // Reasegurar header
             echo json_encode(["success" => false, "message" => "Error interno del servidor al crear usuario."]);
        }
    }
});

?>