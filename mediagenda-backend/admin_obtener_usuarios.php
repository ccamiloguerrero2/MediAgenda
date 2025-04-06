<?php
// --- mediagenda-backend/admin_obtener_usuarios.php ---
session_start();
header('Content-Type: application/json');
include 'conexion.php';
require 'verificar_admin.php'; // Script para verificar si el usuario es admin (lo crearemos después)

$usuarios = [];
$sql = "SELECT idUsuario, nombre, email, rol FROM Usuario ORDER BY nombre ASC"; // Obtener todos los usuarios

try {
    $resultado = mysqli_query($conexion, $sql);
    if ($resultado === false) {
        throw new Exception("Error al ejecutar consulta: " . mysqli_error($conexion));
    }

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $usuarios[] = $fila;
    }

    echo json_encode(["success" => true, "usuarios" => $usuarios]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en admin_obtener_usuarios.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error al obtener la lista de usuarios."]);
} finally {
    mysqli_close($conexion);
}
exit;
?>