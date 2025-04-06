<?php
// --- mediagenda-backend/admin_actualizar_usuario.php ---
session_start();
header('Content-Type: application/json');
include 'conexion.php';
require 'verificar_admin.php';

// Verificar Método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... error 405 ... */ exit; }

// Obtener y validar datos
$idUsuario = filter_input(INPUT_POST, 'idUsuario', FILTER_VALIDATE_INT); // ID del usuario a editar
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$rol = $_POST['rol'] ?? '';
$password = $_POST['password'] ?? ''; // Contraseña NUEVA (opcional)

if (!$idUsuario || empty($nombre) || empty($email) || empty($rol)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (ID, Nombre, Email, Rol)."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { /* ... error 400 ... */ exit; }
$rolesPermitidos = ['paciente', 'medico', 'admin'];
if (!in_array($rol, $rolesPermitidos)) { /* ... error 400 Rol inválido ... */ exit; }
// Validar nueva contraseña si se proporcionó
if (!empty($password) && strlen($password) < 6) { /* ... error 400 Contraseña corta ... */ exit; }


// Verificar si el nuevo email ya existe para OTRO usuario
$sql_check = "SELECT idUsuario FROM Usuario WHERE email = ? AND idUsuario != ?";
$stmt_check = mysqli_prepare($conexion, $sql_check);
mysqli_stmt_bind_param($stmt_check, "si", $email, $idUsuario);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);
if (mysqli_stmt_num_rows($stmt_check) > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["success" => false, "message" => "El nuevo correo electrónico ya está en uso por otro usuario."]);
    mysqli_stmt_close($stmt_check); mysqli_close($conexion); exit;
}
mysqli_stmt_close($stmt_check);


// Construir la consulta UPDATE
$sql_update = "UPDATE Usuario SET nombre = ?, email = ?, rol = ?";
$params = [$nombre, $email, $rol];
$types = "sss";

// Si se proporcionó nueva contraseña, añadirla al update
if (!empty($password)) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql_update .= ", password = ?";
    $params[] = $password_hash;
    $types .= "s";
}

$sql_update .= " WHERE idUsuario = ?";
$params[] = $idUsuario;
$types .= "i";

$stmt_update = mysqli_prepare($conexion, $sql_update);

if ($stmt_update) {
    mysqli_stmt_bind_param($stmt_update, $types, ...$params); // Usar splat operator (...) para pasar parámetros
    if (mysqli_stmt_execute($stmt_update)) {
        // Se ejecutó, verificar si realmente cambió algo (affected_rows >= 0 es éxito aquí)
         if (mysqli_stmt_affected_rows($stmt_update) >= 0) {
             // TODO Opcional: Actualizar tabla Medico/Paciente si el rol cambió? Complejo.
            echo json_encode(["success" => true, "message" => "Usuario actualizado correctamente." . (!empty($password) ? " (Contraseña actualizada)" : "")]);
         } else {
              throw new Exception("No se actualizó el usuario (affected_rows < 0).");
         }
    } else {
        throw new Exception("Error al ejecutar update: " . mysqli_stmt_error($stmt_update));
    }
    mysqli_stmt_close($stmt_update);
} else {
    throw new Exception("Error al preparar update: " . mysqli_error($conexion));
}

mysqli_close($conexion);
exit;

// Captura básica de excepciones (similar a crear)
register_shutdown_function(function() { /* ... (código similar a crear) ... */});
?>