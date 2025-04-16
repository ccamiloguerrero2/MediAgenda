<?php
/**
 * API Endpoint para Actualizar un Usuario Existente (Admin)
 *
 * Este script maneja las solicitudes POST enviadas desde el panel de administración
 * para modificar los datos de un usuario específico en la base de datos.
 * Requiere autenticación de administrador.
 *
 * @package MediAgenda\App\Api\Admin
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1); // Opcional: Fomenta un tipado más estricto
error_reporting(E_ALL);
ini_set('display_errors', '0'); // No mostrar errores detallados en producción
ini_set('log_errors', '1');    // Registrar errores

// --- Inicio de Sesión ---
// Es crucial iniciar la sesión ANTES de cualquier salida o include que la necesite.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Encabezado de Respuesta ---
header('Content-Type: application/json');

// --- Definir Ruta Raíz (para facilitar includes) ---
// Sube tres niveles desde app/Api/Admin/ para llegar a la raíz del proyecto
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
require_once PROJECT_ROOT . '/app/Core/database.php';       // Conexión a la BD ($conexion)
require_once PROJECT_ROOT . '/app/Core/auth_middleware.php'; // Verifica si el usuario es admin (y detiene si no)

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
$idUsuario = filter_input(INPUT_POST, 'idUsuario', FILTER_VALIDATE_INT);
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$rol = trim($_POST['rol'] ?? '');
$password = trim($_POST['password'] ?? ''); // Contraseña NUEVA (opcional)

$errors = [];
if (!$idUsuario) {
    $errors[] = "ID de usuario inválido o faltante.";
}
if (empty($nombre)) {
    $errors[] = "El nombre es obligatorio.";
}
if (empty($email)) {
    $errors[] = "El correo electrónico es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Formato de correo electrónico inválido.";
}
$rolesPermitidos = ['paciente', 'medico', 'admin'];
if (empty($rol)) {
    $errors[] = "El rol es obligatorio.";
} elseif (!in_array($rol, $rolesPermitidos)) {
    $errors[] = "Rol inválido seleccionado ('{$rol}'). Roles permitidos: " . implode(', ', $rolesPermitidos);
}
// Validar nueva contraseña solo si se proporcionó
if (!empty($password) && strlen($password) < 6) {
    $errors[] = "La nueva contraseña debe tener al menos 6 caracteres.";
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
    exit;
}

// --- Lógica Principal (dentro de try...catch) ---
try {
    // Verificar si el nuevo email ya existe para OTRO usuario
    $sql_check_email = "SELECT idUsuario FROM Usuario WHERE email = ? AND idUsuario != ?";
    $stmt_check = mysqli_prepare($conexion, $sql_check_email);
    if (!$stmt_check) throw new Exception("Error DB: Preparando verificación de email - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_check, "si", $email, $idUsuario);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check); // Necesario para mysqli_stmt_num_rows

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'El nuevo correo electrónico ya está en uso por otro usuario.']);
        // Cerrar conexión antes de salir si aún está abierta
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }
    mysqli_stmt_close($stmt_check); // Cerrar statement de verificación

    // Construir la consulta UPDATE dinámicamente
    $sql_update = "UPDATE Usuario SET nombre = ?, email = ?, rol = ?";
    $params = [$nombre, $email, $rol]; // Array para parámetros
    $types = "sss";                     // String para tipos de parámetros
    $passwordUpdated = false;          // Flag para mensaje de éxito

    // Añadir contraseña al update solo si se proporcionó una nueva
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        if ($password_hash === false) {
            // Error al hashear, loguear y lanzar excepción
            error_log("Error crítico al hashear contraseña para usuario ID: " . $idUsuario);
            throw new Exception("Error interno al procesar la contraseña.");
        }
        $sql_update .= ", password = ?"; // Añadir campo password al SQL
        $params[] = $password_hash;      // Añadir hash al array de parámetros
        $types .= "s";                   // Añadir tipo string
        $passwordUpdated = true;         // Marcar que se actualizó
    }

    // Añadir la condición WHERE para el ID de usuario
    $sql_update .= " WHERE idUsuario = ?";
    $params[] = $idUsuario;             // Añadir ID al array de parámetros
    $types .= "i";                      // Añadir tipo integer

    // Preparar y ejecutar la sentencia UPDATE
    $stmt_update = mysqli_prepare($conexion, $sql_update);
    if (!$stmt_update) {
        throw new Exception("Error DB: Preparando actualización de usuario - " . mysqli_error($conexion));
    }

    // Vincular parámetros dinámicamente usando el operador splat (...)
    mysqli_stmt_bind_param($stmt_update, $types, ...$params);

    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Error DB: Ejecutando actualización de usuario - " . mysqli_stmt_error($stmt_update));
    }

    $affected_rows = mysqli_stmt_affected_rows($stmt_update);
    mysqli_stmt_close($stmt_update);

    // --- Manejo de Cambios de Rol (TODO - Simplificado por ahora) ---
    // TODO: Si el $rol cambió, se debería:
    //   1. Verificar si existe una entrada en la tabla Paciente/Medico para este idUsuario.
    //   2. Si el nuevo rol es 'paciente'/'medico' y no existe entrada, crearla.
    //   3. Si el rol anterior era 'paciente'/'medico' y el nuevo NO lo es, eliminar la entrada de Paciente/Medico.
    // Esto requiere lógica adicional y posiblemente una transacción más compleja. Por ahora, solo se actualiza la tabla Usuario.

    // --- Respuesta Exitosa ---
    $successMessage = "Usuario actualizado correctamente.";
    if ($passwordUpdated) {
        $successMessage .= " (Contraseña actualizada)";
    }
    // Opcional: informar si no hubo cambios reales
    // if ($affected_rows === 0 && !$passwordUpdated) {
    //    $successMessage .= " (Sin cambios detectados en los datos)";
    // }

    echo json_encode(['success' => true, 'message' => $successMessage]);

} catch (Exception $e) {
    // --- Manejo de Errores ---
    http_response_code(500); // Internal Server Error
    // Loguear el error detallado para depuración interna
    error_log("Error en " . basename(__FILE__) . ": " . $e->getMessage());
    // Enviar un mensaje genérico al cliente
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al actualizar el usuario.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit; // Asegurar que el script termina aquí
?>