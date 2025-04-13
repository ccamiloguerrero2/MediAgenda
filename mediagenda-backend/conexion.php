<?php
// --- mediagenda-backend/conexion.php ---

$servername = "localhost";
$username = "root";
$password = "";
$database = "mediagenda_db";

// Crear conexión
$conexion = mysqli_connect($servername, $username, $password, $database);

// Verificar la conexión
if (!$conexion) {
    // Para desarrollo, es útil saber si falla aquí, pero evitemos 'die' si contamina la salida JSON.
    header('Content-Type: application/json');
    http_response_code(500); // Error interno del servidor
    // --- DEBUG TEMPORAL ---
    error_log("[DEBUG CONEXION.PHP] Entrando en el bloque if (!\$conexion).");
    $connect_error_msg = mysqli_connect_error(); // Obtener el mensaje de error primero
    error_log("[DEBUG CONEXION.PHP] mysqli_connect_error() devolvió: " . $connect_error_msg);
    // --- FIN DEBUG TEMPORAL ---
    // Registrar el error real en el servidor
    error_log("Fallo CRÍTICO de conexión a BD: " . $connect_error_msg); // Usar la variable guardada
    // Enviar respuesta JSON de error genérico
    echo json_encode(["success" => false, "message" => "Error interno del servidor [DB Connect]."]);
    exit; // Detener ejecución
}

// Establecer charset
if (!mysqli_set_charset($conexion, "utf8mb4")) {
    // Loguear opcionalmente el error de charset si ocurre
    error_log("Error al establecer charset utf8mb4: " . mysqli_error($conexion));
}
// NADA MÁS AQUÍ
// NO USAR ?>   