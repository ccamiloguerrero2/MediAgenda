<?php
/**
 * API Endpoint para Iniciar Sesión de Usuario
 *
 * Verifica las credenciales (email y contraseña) proporcionadas por el usuario.
 * Si son válidas, inicia una sesión PHP, almacena la información del usuario
 * (ID, nombre, rol) en la sesión y devuelve una respuesta JSON de éxito
 * incluyendo el rol del usuario para la redirección en el frontend.
 *
 * @package MediAgenda\App\Api\Auth
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// --- Inicio de Sesión ---
// Debe llamarse antes de cualquier salida o manipulación de $_SESSION.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Encabezado de Respuesta ---
header('Content-Type: application/json');

// --- Definir Ruta Raíz ---
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
// No necesitamos auth_middleware aquí, ya que es un punto de entrada público.
require_once PROJECT_ROOT . '/app/Core/database.php'; // Conexión a la BD ($conexion)

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Datos de Entrada ---
$email = trim($_POST['login_email'] ?? '');
$password_ingresada = trim($_POST['login_password'] ?? '');

$errors = [];
if (empty($email)) {
    $errors[] = "El correo electrónico es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Formato de correo electrónico inválido.";
}
if (empty($password_ingresada)) {
    $errors[] = "La contraseña es obligatoria.";
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    // Usar un mensaje genérico para no revelar si el email existe o no
    echo json_encode(['success' => false, 'message' => 'Credenciales inválidas.']);
    // Loguear los errores específicos internamente si es necesario
    // error_log("Errores de validación en login: " . implode(', ', $errors));
    if (isset($conexion) && $conexion) mysqli_close($conexion);
    exit;
}

// --- Lógica de Autenticación (dentro de try...catch) ---
try {
    // 1. Buscar usuario por email usando sentencia preparada
    $sql_usuario = "SELECT idUsuario, password, nombre, rol FROM Usuario WHERE email = ? LIMIT 1";
    $stmt_usuario = mysqli_prepare($conexion, $sql_usuario);

    if (!$stmt_usuario) {
        throw new Exception("Error DB: Preparando consulta de usuario - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_usuario, "s", $email);
    if (!mysqli_stmt_execute($stmt_usuario)) {
        throw new Exception("Error DB: Ejecutando consulta de usuario - " . mysqli_stmt_error($stmt_usuario));
    }

    $resultado = mysqli_stmt_get_result($stmt_usuario);
    $usuario = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt_usuario); // Cerrar statement tan pronto como sea posible

    // 2. Verificar si el usuario existe y la contraseña es correcta
    if ($usuario && password_verify($password_ingresada, $usuario['password'])) {
        // --- ¡Éxito! Usuario autenticado ---

        // Regenerar ID de sesión para prevenir fijación de sesión
        session_regenerate_id(true);

        // Almacenar información esencial en la sesión
        $_SESSION['idUsuario'] = $usuario['idUsuario'];
        $_SESSION['nombreUsuario'] = $usuario['nombre'];
        $_SESSION['rolUsuario'] = $usuario['rol']; // Asegúrate que el rol en la BD sea consistente (ej. 'paciente', 'medico', 'admin')

        // Log de inicio de sesión exitoso (opcional)
        error_log("Inicio de sesión exitoso para Usuario ID: {$usuario['idUsuario']}, Email: {$email}, Rol: {$usuario['rol']}");

        // Enviar respuesta JSON de éxito incluyendo el rol
        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'rol' => $usuario['rol'] // El frontend usará esto para redirigir
        ]);

    } else {
        // --- Fallo: Usuario no encontrado o contraseña incorrecta ---
        http_response_code(401); // Unauthorized
        // Mensaje genérico para no dar pistas a atacantes
        echo json_encode(['success' => false, 'message' => 'Credenciales inválidas.']);
        // Loguear el intento fallido internamente
        error_log("Intento de login fallido para Email: {$email}");
    }

} catch (Exception $e) {
    // --- Manejo de Errores Inesperados (ej. DB) ---
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " para email {$email}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno durante el inicio de sesión.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>