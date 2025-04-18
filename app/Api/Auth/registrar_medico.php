<?php
/**
 * API Endpoint para Registrar un Nuevo Médico
 *
 * Maneja las solicitudes POST desde el formulario de registro de médicos.
 * Crea una nueva entrada en la tabla 'Usuario' con el rol 'medico' y
 * una entrada correspondiente en la tabla 'Medico', asegurando la
 * atomicidad mediante una transacción.
 *
 * @package MediAgenda\App\Api\Auth
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// --- Encabezado de Respuesta ---
header('Content-Type: application/json');

// --- Definir Ruta Raíz ---
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
require_once PROJECT_ROOT . '/app/Core/database.php'; // Conexión a la BD ($conexion)

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
// Usar los nombres de campo correctos del formulario HTML (doctor_*)
$nombre = trim($_POST['doctor_name'] ?? '');
$email = trim($_POST['doctor_email'] ?? '');
$especialidad = trim($_POST['doctor_specialty'] ?? ''); // Campo específico de médico
$password = trim($_POST['doctor_password'] ?? '');

$errors = [];
if (empty($nombre)) {
    $errors[] = "El nombre completo es obligatorio.";
}
if (empty($email)) {
    $errors[] = "El correo electrónico es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Formato de correo electrónico inválido.";
}
if (empty($especialidad)) {
    // Considerar si la especialidad es realmente obligatoria en el registro inicial.
    // Si puede ser opcional, quitar esta validación y ajustar el INSERT en Medico.
    $errors[] = "La especialidad principal es obligatoria.";
}
if (empty($password)) {
    $errors[] = "La contraseña es obligatoria.";
} elseif (strlen($password) < 6) {
    $errors[] = "La contraseña debe tener al menos 6 caracteres.";
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
    if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit;
}

// --- Lógica Principal (dentro de try...catch y transacción) ---
mysqli_begin_transaction($conexion);

try {
    // 1. Verificar si el Email ya existe
    $sql_check_email = "SELECT idUsuario FROM Usuario WHERE email = ? FOR UPDATE";
    $stmt_check = mysqli_prepare($conexion, $sql_check_email);
    if (!$stmt_check) throw new Exception("Error DB: Preparando verificación de email - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado.']);
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_close($stmt_check);

    // 2. Hashear la Contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    if ($password_hash === false) {
        error_log("Error crítico al hashear contraseña para nuevo médico con email: " . $email);
        throw new Exception("Error interno al procesar la contraseña.");
    }

    // 3. Insertar en la tabla Usuario con rol 'medico'
    $rol_medico = 'medico';
    $sql_insert_usuario = "INSERT INTO Usuario (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
    $stmt_insert_usuario = mysqli_prepare($conexion, $sql_insert_usuario);
    if (!$stmt_insert_usuario) {
        throw new Exception("Error DB: Preparando inserción de Usuario - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_insert_usuario, "ssss", $nombre, $email, $password_hash, $rol_medico);
    if (!mysqli_stmt_execute($stmt_insert_usuario)) {
        throw new Exception("Error DB: Ejecutando inserción de Usuario - " . mysqli_stmt_error($stmt_insert_usuario));
    }
    if (mysqli_stmt_affected_rows($stmt_insert_usuario) <= 0) {
        throw new Exception("Error DB: No se insertó el registro de Usuario (affected_rows <= 0).");
    }

    // Obtener el ID del usuario recién insertado
    $id_usuario_nuevo = mysqli_insert_id($conexion);
    if (!$id_usuario_nuevo) {
         throw new Exception("Error DB: No se pudo obtener el ID del usuario recién creado.");
    }
    mysqli_stmt_close($stmt_insert_usuario);

    // 4. Insertar en la tabla Medico
    // Asume que 'horario' puede ser NULL inicialmente.
    $sql_insert_medico = "INSERT INTO Medico (idUsuario, especialidad, horario) VALUES (?, ?, NULL)";
    $stmt_insert_medico = mysqli_prepare($conexion, $sql_insert_medico);
    if (!$stmt_insert_medico) {
        throw new Exception("Error DB: Preparando inserción de Medico - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_insert_medico, "is", $id_usuario_nuevo, $especialidad);
    if (!mysqli_stmt_execute($stmt_insert_medico)) {
        throw new Exception("Error DB: Ejecutando inserción de Medico - " . mysqli_stmt_error($stmt_insert_medico));
    }
    if (mysqli_stmt_affected_rows($stmt_insert_medico) <= 0) {
        throw new Exception("Error DB: No se insertó el registro de Medico (affected_rows <= 0).");
    }
    mysqli_stmt_close($stmt_insert_medico);

    // 5. Confirmar Transacción
    mysqli_commit($conexion);
    error_log("Nuevo médico registrado - Usuario ID: {$id_usuario_nuevo}, Email: {$email}, Especialidad: {$especialidad}");

    // --- Respuesta Exitosa ---
    echo json_encode(['success' => true, 'message' => "Registro de médico para '{$nombre}' exitoso."]);

} catch (Exception $e) {
    // --- Revertir Transacción en caso de error ---
    mysqli_rollback($conexion);
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " para email {$email}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno durante el registro.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>