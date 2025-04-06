<?php
// --- mediagenda-backend/login.php (CORREGIDO) ---
session_start();
header('Content-Type: application/json');
include 'conexion.php';

if (!isset($_POST["login_email"]) || !isset($_POST["login_password"])) {
    echo json_encode(["success" => false, "message" => "Faltan datos de email o contraseña."]);
    exit;
}

$email = $_POST["login_email"];
$password_ingresada = $_POST["login_password"]; // Renombrar para claridad

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Formato de correo electrónico inválido."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // USA 'password' (nombre de la columna en la BD) en la consulta SQL
    $sql_usuario = "SELECT idUsuario, password, nombre, rol FROM Usuario WHERE email = ?"; // <-- CORREGIDO AQUÍ
    $stmt_usuario = mysqli_prepare($conexion, $sql_usuario);

    if ($stmt_usuario) {
        mysqli_stmt_bind_param($stmt_usuario, "s", $email);
        mysqli_stmt_execute($stmt_usuario);
        $resultado = mysqli_stmt_get_result($stmt_usuario);
        $usuario = mysqli_fetch_assoc($resultado);

        if ($usuario) {
            // USA 'password' (nombre de la columna) al verificar
            if (password_verify($password_ingresada, $usuario['password'])) { // <-- CORREGIDO AQUÍ
                // Contraseña correcta
                $_SESSION['idUsuario'] = $usuario['idUsuario'];
                $_SESSION['nombreUsuario'] = $usuario['nombre'];
                $_SESSION['rolUsuario'] = $usuario['rol'];

                echo json_encode([
                    "success" => true,
                    "message" => "Inicio de sesión exitoso",
                    "rol" => $usuario['rol']
                ]);
            } else {
                // Contraseña incorrecta
                echo json_encode(["success" => false, "message" => "Contraseña incorrecta"]);
            }
        } else {
            // Usuario no encontrado
            echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
        }
        mysqli_stmt_close($stmt_usuario);
    } else {
        // Error servidor
        error_log("Error al preparar la consulta de login: " . mysqli_error($conexion));
        echo json_encode(["success" => false, "message" => "Error del servidor al intentar iniciar sesión."]);
    }
    mysqli_close($conexion);
    exit;

} else {
    echo json_encode(["success" => false, "message" => "Método de solicitud incorrecto."]);
}
?>