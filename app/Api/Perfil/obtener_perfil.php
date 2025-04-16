<?php
/**
 * API Endpoint para Obtener los Datos del Perfil del Usuario Autenticado
 *
 * Recupera los datos básicos del usuario desde la tabla 'Usuario' y los
 * datos específicos del perfil (Paciente o Medico) según el rol del
 * usuario actualmente en sesión.
 * Se utiliza para poblar los formularios de edición de perfil en los paneles.
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

// --- Verificar Autenticación ---
if (!is_authenticated()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Debes iniciar sesión.']);
    exit;
}

$idUsuario = get_user_id();
$rolUsuario = get_user_role(); // 'paciente', 'medico', 'admin'

// Si no se pudo obtener el rol (raro si está autenticado), salir con error
if (empty($rolUsuario)) {
     http_response_code(500);
     error_log("Error crítico: Rol de usuario no encontrado en la sesión para idUsuario: " . $idUsuario);
     echo json_encode(['success' => false, 'message' => 'Error interno: Rol de usuario no definido.']);
     if (isset($conexion) && $conexion) mysqli_close($conexion);
     exit;
}

// --- Lógica Principal (try...catch) ---
$perfilData = null; // Array para almacenar los datos del perfil
$sql = "";
$stmt = null;

try {
    // 1. Construir la Consulta SQL basada en el Rol del Usuario
    if ($rolUsuario === 'paciente') {
        // Obtener datos de Usuario y JOIN con Paciente
        $sql = "SELECT
                    u.nombre, u.email, /* Datos de Usuario */
                    p.telefono, p.direccion /* Datos de Paciente */
                FROM Usuario u
                LEFT JOIN Paciente p ON u.idUsuario = p.idUsuario
                WHERE u.idUsuario = ?";

    } elseif ($rolUsuario === 'medico') {
        // Obtener datos de Usuario y JOIN con Medico
        $sql = "SELECT
                    u.nombre, u.email, /* Datos de Usuario */
                    m.especialidad, m.horario /* Datos de Medico */
                FROM Usuario u
                LEFT JOIN Medico m ON u.idUsuario = m.idUsuario
                WHERE u.idUsuario = ?";

    } elseif ($rolUsuario === 'admin') {
        // Para el admin, solo devolver datos básicos de Usuario
        // (El panel admin tiene su propio endpoint para obtener lista completa)
        $sql = "SELECT nombre, email, rol FROM Usuario WHERE idUsuario = ?";

    } else {
        // Rol desconocido o no manejado
        throw new Exception("Rol de usuario no soportado: '{$rolUsuario}'");
    }

    // 2. Preparar y Ejecutar la Consulta
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception("Error DB: Preparando consulta de perfil ({$rolUsuario}) - " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, "i", $idUsuario);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error DB: Ejecutando consulta de perfil ({$rolUsuario}) - " . mysqli_stmt_error($stmt));
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $perfilData = mysqli_fetch_assoc($resultado); // Obtener la fila de datos
    mysqli_stmt_close($stmt); // Cerrar statement

    // 3. Verificar si se encontraron datos
    if ($perfilData) {
        // --- Respuesta Exitosa ---
        echo json_encode(['success' => true, 'perfil' => $perfilData]);
    } else {
        // Esto es inusual si el usuario está autenticado y el rol es válido.
        // Podría indicar que falta la fila correspondiente en Paciente/Medico
        // si se usó INNER JOIN, pero con LEFT JOIN debería devolver al menos los datos de Usuario.
        // Si $perfilData es false/null, significa que ni siquiera el usuario base fue encontrado.
        http_response_code(404); // Not Found
        error_log("Error: Perfil no encontrado en BD para idUsuario {$idUsuario} con rol {$rolUsuario}. Posible inconsistencia de datos.");
        echo json_encode(['success' => false, 'message' => 'No se encontraron los datos del perfil.']);
    }

} catch (Exception $e) {
    // --- Manejo de Errores Inesperados ---
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " (Usuario ID: {$idUsuario}, Rol: {$rolUsuario}): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al obtener los datos del perfil.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>