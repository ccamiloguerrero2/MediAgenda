<?php
// --- mediagenda-backend/admin_eliminar_usuario.php ---
session_start();
header('Content-Type: application/json');
include 'conexion.php';
require 'verificar_admin.php';

// Usar POST para acciones destructivas
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

$idUsuario = filter_input(INPUT_POST, 'idUsuario', FILTER_VALIDATE_INT);

if (!$idUsuario) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID de usuario inválido o faltante."]);
    exit;
}

// Evitar que el admin se elimine a sí mismo
if (isset($_SESSION['idUsuario']) && $idUsuario == $_SESSION['idUsuario']) {
     http_response_code(403); // Prohibido
     echo json_encode(["success" => false, "message" => "No puedes eliminar tu propia cuenta de administrador."]);
     exit;
}

// Eliminar de la tabla Usuario (CASCADE debería encargarse del resto)
$sql_delete = "DELETE FROM Usuario WHERE idUsuario = ?";
$stmt_delete = mysqli_prepare($conexion, $sql_delete);

try {
    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $idUsuario);
        if (mysqli_stmt_execute($stmt_delete)) {
            if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                echo json_encode(["success" => true, "message" => "Usuario eliminado correctamente."]);
            } else {
                 http_response_code(404); // No encontrado
                 echo json_encode(["success" => false, "message" => "No se encontró el usuario a eliminar o ya fue eliminado."]);
            }
        } else {
            throw new Exception("Error al ejecutar delete: " . mysqli_stmt_error($stmt_delete));
        }
        mysqli_stmt_close($stmt_delete);
    } else {
        throw new Exception("Error al preparar delete: " . mysqli_error($conexion));
    }
} catch (Exception $e) {
     http_response_code(500);
     error_log("Error en admin_eliminar_usuario.php (ID: $idUsuario): " . $e->getMessage());
     echo json_encode(["success" => false, "message" => "Error interno del servidor al eliminar usuario."]);
} finally {
    mysqli_close($conexion);
}
exit;
?>