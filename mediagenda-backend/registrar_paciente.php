<?php
// --- mediagenda-backend/registrar_paciente.php (CON TRANSACCIÓN) ---
header('Content-Type: application/json');
include 'conexion.php'; // Incluye el archivo de conexión

// --- Configuración de errores ---
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al usuario
ini_set('log_errors', 1);     // Registrar errores en el log de PHP

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obtener datos de POST de forma segura
    $nombre = isset($_POST["patient_name"]) ? trim($_POST["patient_name"]) : "";
    $email = isset($_POST["patient_email"]) ? trim($_POST["patient_email"]) : "";
    $telefono = isset($_POST["patient_phone"]) ? trim($_POST["patient_phone"]) : "";
    $password = isset($_POST["patient_password"]) ? $_POST["patient_password"] : "";

    // --- Validación de Entradas ---
    if (empty($nombre) || empty($email) || empty($telefono) || empty($password)) {
        http_response_code(400); // Bad request
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

    // --- Verificar si el Email ya existe ---
    // (Este check se hace ANTES de la transacción para evitar iniciarla innecesariamente)
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
        http_response_code(409); // Conflict
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
        // Asegúrate que tu BD acepta 'paciente' (o usa el valor exacto si es ENUM)
        $rol_paciente = 'paciente';

        // Insertar datos en la tabla Usuario
        // Asume columnas: nombre, email, password, rol
        $sql_usuario = "INSERT INTO Usuario (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
        $stmt_usuario = mysqli_prepare($conexion, $sql_usuario);
        if ($stmt_usuario === false) {
            throw new Exception("Error al preparar inserción de Usuario: " . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_usuario, "ssss", $nombre, $email, $password_hash, $rol_paciente);
        if (!mysqli_stmt_execute($stmt_usuario)) {
             throw new Exception("Error al ejecutar inserción de Usuario: " . mysqli_stmt_error($stmt_usuario));
        }
        // Verificar si realmente se insertó (afectó > 0 filas)
        if (mysqli_stmt_affected_rows($stmt_usuario) <= 0) {
             throw new Exception("No se pudo insertar el registro de Usuario.");
        }
        $id_usuario = mysqli_insert_id($conexion); // Obtener ID solo si la inserción fue exitosa
        mysqli_stmt_close($stmt_usuario); // Cerrar statement tan pronto como sea posible


        // Insertar datos en la tabla Paciente
        // Asume columnas: telefono, idUsuario (y opcional direccion si la añades)
        $sql_paciente = "INSERT INTO Paciente (telefono, idUsuario) VALUES (?, ?)";
        $stmt_paciente = mysqli_prepare($conexion, $sql_paciente);
        if($stmt_paciente === false) {
             throw new Exception("Error al preparar inserción de Paciente: " . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_paciente, "si", $telefono, $id_usuario);
        if (!mysqli_stmt_execute($stmt_paciente)) {
             throw new Exception("Error al ejecutar inserción de Paciente: " . mysqli_stmt_error($stmt_paciente));
        }
         // Verificar si realmente se insertó
        if (mysqli_stmt_affected_rows($stmt_paciente) <= 0) {
             throw new Exception("No se pudo insertar el registro de Paciente.");
        }
        mysqli_stmt_close($stmt_paciente);


        // --- Si ambas inserciones tuvieron éxito, CONFIRMAR TRANSACCIÓN ---
        mysqli_commit($conexion);
        echo json_encode(["success" => true, "message" => "Registro de paciente exitoso"]);

    } catch (Exception $e) {
        // --- Si algo falló, REVERTIR TRANSACCIÓN ---
        mysqli_rollback($conexion);
        http_response_code(500); // Error interno del servidor
        // Registrar el error real
        error_log("Error en transacción registrar_paciente: " . $e->getMessage());
        // Enviar un mensaje genérico al usuario
        echo json_encode(["success" => false, "message" => "Ocurrió un error durante el registro. Por favor, intente de nuevo."]);

    } finally {
        // --- Cerrar la conexión ---
        mysqli_close($conexion);
    }

} else {
    http_response_code(405); // Método no permitido
    echo json_encode(["success" => false, "message" => "Método de solicitud incorrecto"]);
}
exit; // Asegurar que el script termina aquí
?>