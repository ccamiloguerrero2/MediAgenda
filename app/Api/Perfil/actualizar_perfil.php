<?php
/**
 * API Endpoint para Actualizar el Perfil del Usuario (Paciente o Médico)
 *
 * Maneja las solicitudes POST enviadas desde los paneles de perfil
 * (`perfil-usuario.php`, `perfil-doctores.php`) para modificar los datos
 * del usuario en la tabla 'Usuario' y los datos específicos del rol
 * en las tablas 'Paciente' o 'Medico'.
 * Requiere autenticación.
 *
 * @package MediAgenda\App\Api\Perfil
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
require_once PROJECT_ROOT . '/app/Core/session_utils.php'; // Para obtener rol e ID de usuario

// --- Verificar Autenticación y Rol Válido ---
if (!is_authenticated()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Debes iniciar sesión.']);
    exit;
}

$idUsuario = get_user_id();
$rolUsuario = get_user_role(); // 'paciente', 'medico', 'admin'

// Solo permitir a pacientes y médicos actualizar su propio perfil aquí.
// El admin usa su propio endpoint (admin_actualizar_usuario.php).
if ($rolUsuario !== 'paciente' && $rolUsuario !== 'medico') {
    http_response_code(403); // Forbidden
    error_log("Intento de actualización de perfil por rol no permitido: '{$rolUsuario}' (Usuario ID: {$idUsuario})");
    echo json_encode(['success' => false, 'message' => 'Tu rol no permite actualizar el perfil desde esta interfaz.']);
    exit;
}

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
// Datos comunes
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
// Datos específicos (pueden venir vacíos dependiendo del form que envió)
$telefono = trim($_POST['telefono'] ?? '');       // Del form paciente
$direccion = trim($_POST['direccion'] ?? '');     // Del form paciente
$especialidad = trim($_POST['especialidad'] ?? ''); // Del form medico
$horario = trim($_POST['horario'] ?? '');         // Del form medico

// Validación
$errors = [];
if (empty($nombre)) {
    $errors[] = "El nombre completo es obligatorio.";
}
if (empty($email)) {
    $errors[] = "El correo electrónico es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Formato de correo electrónico inválido.";
}
// Validación específica de rol (solo si el dato corresponde al rol actual)
if ($rolUsuario === 'paciente' && !empty($telefono) && !preg_match('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/', $telefono)) {
    // Validación de teléfono muy básica, mejorar si es necesario
    // $errors[] = "Formato de teléfono inválido.";
}
if ($rolUsuario === 'medico' && empty($especialidad)) {
    // Considerar si la especialidad debe ser siempre obligatoria para un médico
    // $errors[] = "La especialidad es obligatoria para médicos.";
}


if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
    if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit;
}

// --- Lógica Principal (try...catch y transacción) ---
mysqli_begin_transaction($conexion);

try {
    // 1. Verificar si el nuevo Email ya existe para OTRO usuario
    $sql_check_email = "SELECT idUsuario FROM Usuario WHERE email = ? AND idUsuario != ?";
    $stmt_check = mysqli_prepare($conexion, $sql_check_email);
    if (!$stmt_check) throw new Exception("Error DB: Preparando verificación de email - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_check, "si", $email, $idUsuario);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        mysqli_rollback($conexion); // Revertir transacción iniciada
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ingresado ya está en uso por otro usuario.']);
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_close($stmt_check);


    // 2. Actualizar Tabla Usuario (Nombre y Email)
    $sql_update_usuario = "UPDATE Usuario SET nombre = ?, email = ? WHERE idUsuario = ?";
    $stmt_usuario = mysqli_prepare($conexion, $sql_update_usuario);
    if (!$stmt_usuario) throw new Exception("Error DB: Preparando actualización de Usuario - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_usuario, "ssi", $nombre, $email, $idUsuario);
    if (!mysqli_stmt_execute($stmt_usuario)) {
        throw new Exception("Error DB: Ejecutando actualización de Usuario - " . mysqli_stmt_error($stmt_usuario));
    }
    $affected_usuario = mysqli_stmt_affected_rows($stmt_usuario);
    mysqli_stmt_close($stmt_usuario);
    error_log("Tabla Usuario actualizada para ID {$idUsuario}. Affected rows: {$affected_usuario}");


    // 3. Actualizar Tabla Específica del Rol (Paciente o Medico)
    if ($rolUsuario === 'paciente') {
        // Preparar valores (usar NULL si están vacíos y la BD lo permite)
        $telefono_db = !empty($telefono) ? $telefono : null;
        $direccion_db = !empty($direccion) ? $direccion : null;

        $sql_update_perfil = "UPDATE Paciente SET telefono = ?, direccion = ? WHERE idUsuario = ?";
        $stmt_perfil = mysqli_prepare($conexion, $sql_update_perfil);
        if (!$stmt_perfil) throw new Exception("Error DB: Preparando actualización de Paciente - " . mysqli_error($conexion));

        mysqli_stmt_bind_param($stmt_perfil, "ssi", $telefono_db, $direccion_db, $idUsuario);
        if (!mysqli_stmt_execute($stmt_perfil)) {
             throw new Exception("Error DB: Ejecutando actualización de Paciente - " . mysqli_stmt_error($stmt_perfil));
        }
        $affected_perfil = mysqli_stmt_affected_rows($stmt_perfil);
        mysqli_stmt_close($stmt_perfil);
        error_log("Tabla Paciente actualizada para Usuario ID {$idUsuario}. Affected rows: {$affected_perfil}");

    } elseif ($rolUsuario === 'medico') {
        // Preparar valores
        $especialidad_db = !empty($especialidad) ? $especialidad : null; // Asume que puede ser NULL si no es obligatorio
        $horario_db = !empty($horario) ? $horario : null;

        $sql_update_perfil = "UPDATE Medico SET especialidad = ?, horario = ? WHERE idUsuario = ?";
        $stmt_perfil = mysqli_prepare($conexion, $sql_update_perfil);
        if (!$stmt_perfil) throw new Exception("Error DB: Preparando actualización de Medico - " . mysqli_error($conexion));

        mysqli_stmt_bind_param($stmt_perfil, "ssi", $especialidad_db, $horario_db, $idUsuario);
         if (!mysqli_stmt_execute($stmt_perfil)) {
             throw new Exception("Error DB: Ejecutando actualización de Medico - " . mysqli_stmt_error($stmt_perfil));
        }
        $affected_perfil = mysqli_stmt_affected_rows($stmt_perfil);
        mysqli_stmt_close($stmt_perfil);
        error_log("Tabla Medico actualizada para Usuario ID {$idUsuario}. Affected rows: {$affected_perfil}");
    }

    // 4. Confirmar Transacción
    mysqli_commit($conexion);

    // 5. Actualizar nombre en sesión para reflejo inmediato en UI
    $_SESSION['nombreUsuario'] = $nombre;

    // --- Respuesta Exitosa ---
    echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);

} catch (Exception $e) {
    // --- Revertir Transacción en caso de error ---
    mysqli_rollback($conexion);
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " para Usuario ID {$idUsuario}: " . $e->getMessage());
    // Enviar mensaje genérico, el error real ya está logueado
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al actualizar el perfil.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>