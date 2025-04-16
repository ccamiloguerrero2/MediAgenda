<?php
/**
 * API Endpoint para Crear un Nuevo Usuario (Admin)
 *
 * Este script maneja las solicitudes POST enviadas desde el panel de administración
 * para registrar un nuevo usuario (paciente, médico o admin) en el sistema.
 * Requiere autenticación de administrador.
 *
 * @package MediAgenda\App\Api\Admin
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// --- Inicio de Sesión ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Encabezado de Respuesta ---
header('Content-Type: application/json');

// --- Definir Ruta Raíz ---
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
require_once PROJECT_ROOT . '/app/Core/database.php';       // Conexión a la BD ($conexion)
require_once PROJECT_ROOT . '/app/Core/auth_middleware.php'; // Verifica si el usuario es admin

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? ''); // Contraseña para el nuevo usuario
$rol = trim($_POST['rol'] ?? '');           // Rol seleccionado ('paciente', 'medico', 'admin')

$errors = [];
if (empty($nombre)) {
    $errors[] = "El nombre es obligatorio.";
}
if (empty($email)) {
    $errors[] = "El correo electrónico es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Formato de correo electrónico inválido.";
}
if (empty($password)) {
    $errors[] = "La contraseña es obligatoria.";
} elseif (strlen($password) < 6) {
    $errors[] = "La contraseña debe tener al menos 6 caracteres.";
}
$rolesPermitidos = ['paciente', 'medico', 'admin'];
if (empty($rol)) {
    $errors[] = "El rol es obligatorio.";
} elseif (!in_array($rol, $rolesPermitidos)) {
    $errors[] = "Rol inválido seleccionado ('{$rol}'). Roles permitidos: " . implode(', ', $rolesPermitidos);
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
    // Asegurarse de cerrar la conexión si se estableció antes de la validación
    if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit;
}

// --- Lógica Principal (dentro de try...catch) ---
mysqli_begin_transaction($conexion); // Iniciar transacción

try {
    // 1. Verificar si el Email ya existe
    $sql_check_email = "SELECT idUsuario FROM Usuario WHERE email = ? FOR UPDATE"; // Bloqueo para concurrencia
    $stmt_check = mysqli_prepare($conexion, $sql_check_email);
    if (!$stmt_check) throw new Exception("Error DB: Preparando verificación de email - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        // No es necesario rollback aquí porque aún no hemos insertado nada.
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado.']);
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_close($stmt_check);

    // 2. Hashear la Contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    if ($password_hash === false) {
        error_log("Error crítico al hashear contraseña para nuevo usuario con email: " . $email);
        throw new Exception("Error interno al procesar la contraseña.");
    }

    // 3. Insertar en la tabla Usuario
    $sql_insert_usuario = "INSERT INTO Usuario (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
    $stmt_insert_usuario = mysqli_prepare($conexion, $sql_insert_usuario);
    if (!$stmt_insert_usuario) {
        throw new Exception("Error DB: Preparando inserción de Usuario - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_insert_usuario, "ssss", $nombre, $email, $password_hash, $rol);
    if (!mysqli_stmt_execute($stmt_insert_usuario)) {
        throw new Exception("Error DB: Ejecutando inserción de Usuario - " . mysqli_stmt_error($stmt_insert_usuario));
    }

    // Verificar si se insertó correctamente
    if (mysqli_stmt_affected_rows($stmt_insert_usuario) <= 0) {
        throw new Exception("Error DB: No se insertó el registro de Usuario (affected_rows <= 0).");
    }

    // Obtener el ID del usuario recién insertado
    $id_usuario_nuevo = mysqli_insert_id($conexion);
    if (!$id_usuario_nuevo) {
         throw new Exception("Error DB: No se pudo obtener el ID del usuario recién creado.");
    }
    mysqli_stmt_close($stmt_insert_usuario);


    // 4. Insertar en la tabla Paciente o Medico si aplica (según el rol)
    //    (Asumiendo que no se reciben datos adicionales como teléfono o especialidad desde este formulario admin)
    if ($rol === 'paciente') {
        $sql_insert_perfil = "INSERT INTO Paciente (idUsuario, telefono, direccion) VALUES (?, NULL, NULL)"; // Asume que tel/dir son opcionales aquí
        $stmt_insert_perfil = mysqli_prepare($conexion, $sql_insert_perfil);
        if (!$stmt_insert_perfil) throw new Exception("Error DB: Preparando inserción de Paciente - " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt_insert_perfil, "i", $id_usuario_nuevo);
        if (!mysqli_stmt_execute($stmt_insert_perfil)) throw new Exception("Error DB: Ejecutando inserción de Paciente - " . mysqli_stmt_error($stmt_insert_perfil));
        if (mysqli_stmt_affected_rows($stmt_insert_perfil) <= 0) throw new Exception("Error DB: No se insertó el registro de Paciente.");
        mysqli_stmt_close($stmt_insert_perfil);
        error_log("Registro Paciente creado para Usuario ID: " . $id_usuario_nuevo);

    } elseif ($rol === 'medico') {
        // Se podría requerir la especialidad aquí, pero el form actual no la pide
        $especialidad_default = "General"; // O NULL si la columna lo permite
        $sql_insert_perfil = "INSERT INTO Medico (idUsuario, especialidad, horario) VALUES (?, ?, NULL)"; // Asume especialidad NOT NULL (poner default), horario NULL
        $stmt_insert_perfil = mysqli_prepare($conexion, $sql_insert_perfil);
         if (!$stmt_insert_perfil) throw new Exception("Error DB: Preparando inserción de Medico - " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt_insert_perfil, "is", $id_usuario_nuevo, $especialidad_default);
        if (!mysqli_stmt_execute($stmt_insert_perfil)) throw new Exception("Error DB: Ejecutando inserción de Medico - " . mysqli_stmt_error($stmt_insert_perfil));
        if (mysqli_stmt_affected_rows($stmt_insert_perfil) <= 0) throw new Exception("Error DB: No se insertó el registro de Medico.");
        mysqli_stmt_close($stmt_insert_perfil);
        error_log("Registro Medico creado para Usuario ID: " . $id_usuario_nuevo);
    }
    // Si el rol es 'admin', no se necesita insertar en Paciente ni Medico.

    // 5. Confirmar Transacción
    mysqli_commit($conexion);

    // --- Respuesta Exitosa ---
    echo json_encode(['success' => true, 'message' => "Usuario '{$nombre}' ({$rol}) creado correctamente."]);

} catch (Exception $e) {
    // --- Revertir Transacción en caso de error ---
    mysqli_rollback($conexion);
    http_response_code(500); // Internal Server Error
    error_log("Error en " . basename(__FILE__) . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al crear el usuario.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>