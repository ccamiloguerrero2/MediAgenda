<?php
/**
 * API Endpoint para Obtener la Lista de Médicos
 *
 * Recupera una lista de todos los médicos registrados y activos
 * en el sistema, incluyendo su ID, nombre y especialidad.
 * Esta información se usa típicamente para poblar selectores
 * en formularios de agendamiento de citas.
 * No requiere autenticación específica, ya que la lista de médicos
 * puede ser considerada información pública dentro de la aplicación.
 *
 * @package MediAgenda\App\Api\General
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0'); // No mostrar errores en producción
ini_set('log_errors', '1');    // Loguear errores

// --- Encabezado de Respuesta ---
header('Content-Type: application/json');

// --- Definir Ruta Raíz ---
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
require_once PROJECT_ROOT . '/app/Core/database.php'; // Conexión a la BD ($conexion)
// No se requiere sesión aquí, pero podría añadirse si se quisiera filtrar
// médicos basados en alguna preferencia del usuario logueado en el futuro.
// require_once PROJECT_ROOT . '/app/Core/session_utils.php';

// --- Verificar Conexión ---
if (!isset($conexion) || !$conexion) {
    // conexion.php ya debería haber manejado esto, pero por si acaso.
    exit;
}

// --- Lógica Principal (try...catch) ---
$medicos = []; // Array para almacenar los resultados

try {
    // 1. Preparar la Consulta SQL
    // Seleccionar ID del médico (de la tabla Medico), nombre (de Usuario)
    // y especialidad (de Medico).
    // Unir las tablas Usuario y Medico.
    // Filtrar por rol 'medico' en la tabla Usuario para asegurar consistencia.
    // Opcional: Podrías añadir un filtro WHERE para médicos 'activos' si tuvieras ese campo.
    // Ordenar alfabéticamente por nombre para facilitar la búsqueda en el selector.
    $sql = "SELECT
                m.idMedico,
                u.nombre,
                m.especialidad
            FROM Medico m
            INNER JOIN Usuario u ON m.idUsuario = u.idUsuario
            WHERE u.rol = 'medico'
            /* AND u.activo = 1 */ -- Ejemplo de filtro adicional si existiera
            ORDER BY u.nombre ASC";

    // 2. Ejecutar la Consulta
    // Como no hay parámetros de entrada, mysqli_query es seguro aquí.
    $resultado = mysqli_query($conexion, $sql);

    if ($resultado === false) {
        throw new Exception("Error DB: Ejecutando consulta de médicos - " . mysqli_error($conexion));
    }

    // 3. Procesar los Resultados
    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Asegurarse de que especialidad tenga un valor por defecto si es NULL en BD
        // y el frontend espera siempre un string.
        $fila['especialidad'] = $fila['especialidad'] ?? 'General'; // Asigna 'General' si es NULL
        $medicos[] = $fila;
    }

    mysqli_free_result($resultado); // Liberar memoria

    // 4. Enviar Respuesta Exitosa
    echo json_encode(['success' => true, 'medicos' => $medicos]);

} catch (Exception $e) {
    // --- Manejo de Errores ---
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al obtener la lista de médicos.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>