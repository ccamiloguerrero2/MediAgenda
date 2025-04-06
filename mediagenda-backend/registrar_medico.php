<?php
// --- mediagenda-backend/registrar_medico.php (CON TRANSACCIÓN) ---
header('Content-Type: application/json');
include 'conexion.php';

// --- Configuración de errores ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al usuario
ini_set('log_errors', 1);     // Registrar errores en el log de PHP

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obtener datos de POST de forma segura
    $nombre = isset($_POST["doctor_name"]) ? trim($_POST["doctor_name"]) : "";
    $email = isset($_POST["doctor_email"]) ? trim($_POST["doctor_email"]) : "";
    $especialidad = isset($_POST["doctor_specialty"]) ? trim($_POST["doctor_specialty"]) : "";
    $password = isset($_POST["doctor_password"]) ? $_POST["doctor_password"] : "";

    // --- Validación de Entradas ---
    if (empty($nombre) || empty($email) || empty($especialidad) || empty($password)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios."]);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Formato de correo electrónico inválido."]);
        exit;
    }
    if (strlen($password) < 6) {
       http_response_code(400);
       echo json_encode(["success" => false, "message" => "La contraseña debe tener al menos 6 caracteres."]);
       exit;
    }

    // --- Verificar si el Email ya existe (ANTES de la transacción) ---
    $sql_check_email = "SELECT idUsuario FROM Usuario WHERE email = ?";
    $stmt_check = mysqli_prepare($conexion, $sql_check_email);
     if ($stmt_check === false) {
        error_log("Error al preparar check de email: " . mysqli_error($conexion));
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error interno del servidor [Check Email]."]);
        mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "El correo electrónico ya está registrado."]);
        mysqli_stmt_close($stmt_check);
        mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_close($stmt_check);

    // --- INICIAR TRANSACCIÓN ---
    mysqli_begin_transaction($conexion);

    try {
        // --- Proceder con el Registro ---
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // Asegúrate que tu BD acepta 'medico' (o usa el valor exacto si es ENUM)
        $rol_medico = 'medico';

        // Insertar datos en la tabla Usuario
        // Asume columnas: nombre, email, password, rol
        $sql_usuario = "INSERT INTO Usuario (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
        $stmt_usuario = mysqli_prepare($conexion, $sql_usuario);
        if ($stmt_usuario === false) {
             throw new Exception("Error al preparar inserción de Usuario: " . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_usuario, "ssss", $nombre, $email, $password_hash, $rol_medico);
        if (!mysqli_stmt_execute($stmt_usuario)) {
            throw new Exception("Error al ejecutar inserción de Usuario: " . mysqli_stmt_error($stmt_usuario));
        }
        if (mysqli_stmt_affected_rows($stmt_usuario) <= 0) {
             throw new Exception("No se pudo insertar el registro de Usuario.");
        }
        $id_usuario = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt_usuario);


        // Insertar datos en la tabla Medico
        // Asume columnas: especialidad, idUsuario (y opcional horario)
        // Si 'horario' NO permite NULL, debes pasar un valor o quitarlo del INSERT
        $sql_medico = "INSERT INTO Medico (especialidad, idUsuario, horario) VALUES (?, ?, NULL)"; // Asume horario puede ser NULL inicialmente
        $stmt_medico = mysqli_prepare($conexion, $sql_medico);
        if($stmt_medico === false) {
             throw new Exception("Error al preparar inserción de Medico: " . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_medico, "si", $especialidad, $id_usuario);
        if (!mysqli_stmt_execute($stmt_medico)) {
             throw new Exception("Error al ejecutar inserción de Medico: " . mysqli_stmt_error($stmt_medico));
        }
        if (mysqli_stmt_affected_rows($stmt_medico) <= 0) {
             throw new Exception("No se pudo insertar el registro de Medico.");
        }
        mysqli_stmt_close($stmt_medico);


        // --- Si ambas inserciones tuvieron éxito, CONFIRMAR TRANSACCIÓN ---
        mysqli_commit($conexion);
        echo json_encode(["success" => true, "message" => "Registro de médico exitoso"]);

    } catch (Exception $e) {
        // --- Si algo falló, REVERTIR TRANSACCIÓN ---
        mysqli_rollback($conexion);
        http_response_code(500);
        error_log("Error en transacción registrar_medico: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Ocurrió un error durante el registro. Por favor, intente de nuevo."]);

    } finally {
        // --- Cerrar la conexión ---
        mysqli_close($conexion);
    }

} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método de solicitud incorrecto"]);
}
exit;
?>