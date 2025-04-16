<?php
/**
 * API Endpoint para Obtener Lista de Citas
 *
 * Recupera las citas asociadas a un usuario (paciente o médico),
 * según el rol especificado en el parámetro GET 'rol'.
 * Incluye información adicional relevante (nombre del médico/paciente).
 * Requiere autenticación.
 *
 * @package MediAgenda\App\Api\Citas
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
$rolUsuarioActual = get_user_role(); // Rol del usuario en sesión

// --- Obtener y Validar Rol Solicitado (Parámetro GET) ---
$rolSolicitado = strtolower(trim($_GET['rol'] ?? '')); // Rol para el que se piden las citas

if ($rolSolicitado !== 'paciente' && $rolSolicitado !== 'medico') {
     http_response_code(400); // Bad Request
     echo json_encode(['success' => false, 'message' => "Parámetro 'rol' inválido o faltante. Debe ser 'paciente' o 'medico'."]);
     exit;
}

// Opcional: Verificar si el rol solicitado coincide con el rol del usuario en sesión
// (Esto evitaría que un médico intente ver citas "como paciente" o viceversa, aunque
// la lógica de obtención de ID de perfil ya lo previene indirectamente).
// if ($rolSolicitado !== $rolUsuarioActual) {
//     http_response_code(403); // Forbidden
//     echo json_encode(['success' => false, 'message' => "No puedes solicitar citas para un rol diferente al tuyo."]);
//     exit;
// }

// --- Lógica Principal (try...catch) ---
$citas = []; // Array para almacenar los resultados
$sql = "";
$stmt = null;
$idPerfil = null; // idPaciente o idMedico

try {
    // 1. Obtener idPerfil (idPaciente o idMedico) basado en el rol del usuario en sesión
    $sql_get_id = "";
    $campoPerfil = ""; // Nombre de la columna ID en Paciente/Medico
    if ($rolUsuarioActual === 'paciente') {
        $sql_get_id = "SELECT idPaciente FROM Paciente WHERE idUsuario = ?";
        $campoPerfil = 'idPaciente';
    } elseif ($rolUsuarioActual === 'medico') {
        $sql_get_id = "SELECT idMedico FROM Medico WHERE idUsuario = ?";
        $campoPerfil = 'idMedico';
    } else {
        // Rol admin u otro no debería llegar aquí basado en la lógica anterior,
        // pero por si acaso.
        throw new Exception("Rol de usuario ('{$rolUsuarioActual}') no válido para obtener citas.");
    }

    $stmt_get_id = mysqli_prepare($conexion, $sql_get_id);
    if (!$stmt_get_id) throw new Exception("Error DB: Preparando obtención de ID de perfil - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_get_id, "i", $idUsuario);
    mysqli_stmt_execute($stmt_get_id);
    $res_id = mysqli_stmt_get_result($stmt_get_id);
    $perfilRow = mysqli_fetch_assoc($res_id);
    mysqli_stmt_close($stmt_get_id);

    if (!$perfilRow || !isset($perfilRow[$campoPerfil])) {
        // Si no tiene perfil (raro si el rol es paciente/medico), no tendrá citas
        error_log("Advertencia: No se encontró perfil {$campoPerfil} para Usuario ID {$idUsuario} con rol {$rolUsuarioActual}.");
        echo json_encode(['success' => true, 'citas' => []]); // Devolver lista vacía
        if (isset($conexion) && $conexion) mysqli_close($conexion);
        exit;
    }
    $idPerfil = $perfilRow[$campoPerfil];


    // 2. Construir la Consulta SQL Principal basada en el $rolSolicitado (que debe coincidir con $rolUsuarioActual)
    if ($rolSolicitado === 'paciente') {
        // Consulta para obtener citas del paciente, incluyendo datos del médico
        $sql = "SELECT
                    c.idCita, c.fecha, c.hora, c.motivo, c.estado,
                    m.idMedico, /* Incluir ID médico si se necesita */
                    u_medico.nombre AS nombreMedico,
                    m.especialidad AS especialidadMedico
                FROM Cita c
                INNER JOIN Medico m ON c.idMedico = m.idMedico
                INNER JOIN Usuario u_medico ON m.idUsuario = u_medico.idUsuario
                WHERE c.idPaciente = ?
                ORDER BY c.fecha DESC, c.hora DESC"; // Más recientes primero
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) throw new Exception("Error DB: Preparando consulta citas paciente - " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt, "i", $idPerfil); // idPerfil aquí es idPaciente

    } elseif ($rolSolicitado === 'medico') {
        // Consulta para obtener citas del médico, incluyendo datos del paciente
        $sql = "SELECT
                    c.idCita, c.fecha, c.hora, c.motivo, c.estado,
                    p.idPaciente, /* Incluir ID paciente si se necesita */
                    u_paciente.nombre AS nombrePaciente,
                    p.telefono AS telefonoPaciente
                FROM Cita c
                INNER JOIN Paciente p ON c.idPaciente = p.idPaciente
                INNER JOIN Usuario u_paciente ON p.idUsuario = u_paciente.idUsuario
                WHERE c.idMedico = ?
                ORDER BY c.fecha ASC, c.hora ASC"; // Próximas primero
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) throw new Exception("Error DB: Preparando consulta citas médico - " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt, "i", $idPerfil); // idPerfil aquí es idMedico
    }
    // No debería haber un 'else' aquí si las validaciones anteriores son correctas

    // 3. Ejecutar Consulta y Recolectar Resultados
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error DB: Ejecutando consulta de citas - " . mysqli_stmt_error($stmt));
    }
    $resultado = mysqli_stmt_get_result($stmt);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Opcional: Formatear datos aquí si es preferible hacerlo en el backend
        // $fila['fecha_formateada'] = formatearFecha($fila['fecha']); // Necesitarías definir formatearFecha
        // $fila['hora_formateada'] = formatearHora($fila['hora']); // Necesitarías definir formatearHora
        $citas[] = $fila;
    }
    mysqli_stmt_close($stmt); // Cerrar statement principal

    // 4. Enviar Respuesta Exitosa
    echo json_encode(['success' => true, 'citas' => $citas]);

} catch (Exception $e) {
    // --- Manejo de Errores Inesperados ---
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " (Usuario ID: {$idUsuario}, Rol Solicitado: {$rolSolicitado}): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error interno al obtener las citas.']);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>