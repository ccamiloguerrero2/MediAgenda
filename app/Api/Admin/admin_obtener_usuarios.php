<?php
/**
 * API Endpoint para Obtener la Lista de Todos los Usuarios (Admin)
 *
 * Este script recupera una lista de todos los usuarios registrados
 * en el sistema (ID, nombre, email, rol) para mostrarla en el panel
 * de administración.
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

// --- Lógica Principal (dentro de try...catch) ---
$usuarios = []; // Inicializar array para almacenar usuarios

try {
    // 1. Preparar la Consulta SQL
    // Seleccionar los campos necesarios para la tabla del panel de admin.
    // Ordenar por nombre o ID es útil para la visualización.
    $sql = "SELECT idUsuario, nombre, email, rol FROM Usuario ORDER BY nombre ASC";

    // 2. Ejecutar la Consulta (Usando mysqli_query es seguro para SELECT sin parámetros)
    // Alternativa: usar sentencias preparadas si se añadieran filtros en el futuro.
    $resultado = mysqli_query($conexion, $sql);

    // Verificar si la consulta falló
    if ($resultado === false) {
        throw new Exception("Error DB: Ejecutando consulta de usuarios - " . mysqli_error($conexion));
    }

    // 3. Procesar los Resultados
    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Opcional: Podrías querer formatear o sanear datos aquí si fuera necesario,
        // pero para mostrar en tabla, los datos directos suelen estar bien.
        $usuarios[] = $fila;
    }

    // Liberar memoria del resultado
    mysqli_free_result($resultado);

    // 4. Enviar Respuesta Exitosa
    echo json_encode(['success' => true, 'usuarios' => $usuarios]);

} catch (Exception $e) {
    // --- Manejo de Errores ---
    http_response_code(500); // Internal Server Error
    error_log("Error en " . basename(__FILE__) . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al obtener la lista de usuarios.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>