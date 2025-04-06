<?php
// --- mediagenda-backend/obtener_medicos.php ---

error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al usuario
ini_set('log_errors', 1);     // Registrar errores en el log de PHP

// No se necesita sesión necesariamente para ver la lista de médicos,
// pero la incluimos por consistencia y si quisiéramos filtrar en el futuro.
session_start();
header('Content-Type: application/json');
include 'conexion.php'; // Asegura que $conexion esté disponible

$medicos = [];
$sql = "";
$stmt = null;

try {
    // 1. Preparar Consulta SQL para obtener Médicos activos (o todos por ahora)
    // Seleccionamos el idMedico (de la tabla Medico) y nombre/especialidad
    $sql = "SELECT m.idMedico, u.nombre, m.especialidad
            FROM Medico m
            JOIN Usuario u ON m.idUsuario = u.idUsuario
            WHERE u.rol = 'medico'
            ORDER BY u.nombre ASC"; // Ordenar alfabéticamente por nombre

    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt === false) {
        throw new Exception("Error al preparar consulta obtener médicos: " . mysqli_error($conexion));
    }

    // 2. Ejecutar Consulta y Obtener Datos
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $medicos[] = $fila; // Añadir cada médico al array
    }

    // 3. Devolver Datos Exitosamente
    echo json_encode(["success" => true, "medicos" => $medicos]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en obtener_medicos.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error del servidor al obtener la lista de médicos."]);
} finally {
    // 4. Limpiar
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);
}

exit; // Terminar script
?>