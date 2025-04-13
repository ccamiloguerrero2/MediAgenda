<?php
// --- mediagenda-backend/procesar_reset.php ---

header('Content-Type: application/json');

// ***** 1. INCLUIR TU ARCHIVO DE CONEXIÓN A LA BASE DE DATOS *****
// Usando tu archivo conexion.php
require_once 'conexion.php'; // ¡AHORA USA CONEXION.PHP!

// Verificar conexión
if (!isset($conexion) || !$conexion) {
    exit; // Error ya manejado en conexion.php
}

// ***** 2. OBTENER DATOS DEL POST *****
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['token'], $_POST['new_password'], $_POST['confirm_password'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida o datos incompletos.']);
    exit;
}

$token = trim($_POST['token']);
$newPassword = $_POST['new_password'];
$confirmPassword = $_POST['confirm_password'];

// ***** 3. VALIDACIONES BÁSICAS *****
if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token inválido o faltante.']);
    exit;
}
if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
    exit;
}
if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Las contraseñas introducidas no coinciden.']);
    exit;
}

// ***** 4. VERIFICAR TOKEN Y OBTENER EMAIL (MySQLi) *****
$email = null;
try {
    $currentTime = date('Y-m-d H:i:s');
    $sqlCheck = "SELECT email FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1";
    $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "ss", $token, $currentTime);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    $result = mysqli_fetch_assoc($resultCheck);
    mysqli_stmt_close($stmtCheck);

    if ($result) {
        $email = $result['email'];
    } else {
        echo json_encode(['success' => false, 'message' => 'El enlace de restablecimiento ya no es válido o ha expirado. Solicita uno nuevo.']);
        exit;
    }
} catch (Exception $e) {
    error_log("Error DB check token (procesar_reset.php - MySQLi): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al verificar la solicitud. Inténtalo más tarde.']);
    exit;
}

// ***** 5. ACTUALIZAR CONTRASEÑA Y ELIMINAR TOKEN (MySQLi) *****
if ($email) {
    // Iniciar transacción MySQLi
    mysqli_begin_transaction($conexion);

    try {
        // Hashear la nueva contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Actualizar contraseña en la tabla usuario
        $sqlUpdate = "UPDATE usuario SET password = ? WHERE email = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        mysqli_stmt_bind_param($stmtUpdate, "ss", $hashedPassword, $email);
        $updateSuccess = mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);

        if (!$updateSuccess) {
             throw new Exception("Error al actualizar la contraseña del usuario.");
        }

        // Eliminar el token usado de la tabla password_resets
        $sqlDelete = "DELETE FROM password_resets WHERE email = ?";
        $stmtDelete = mysqli_prepare($conexion, $sqlDelete);
        mysqli_stmt_bind_param($stmtDelete, "s", $email);
        $deleteSuccess = mysqli_stmt_execute($stmtDelete);
        mysqli_stmt_close($stmtDelete);

         if (!$deleteSuccess) {
             error_log("Advertencia: No se pudo eliminar el token de reseteo para el email: " . $email);
             // Continuamos si la contraseña se actualizó
         }

        // Confirmar transacción
        mysqli_commit($conexion);

        echo json_encode(['success' => true, 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión con tu nueva contraseña.']);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conexion);
        error_log("Error DB update password/delete token (procesar_reset.php - MySQLi): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno al actualizar la contraseña. Por favor, inténtalo de nuevo más tarde.']);
    }
} else {
     echo json_encode(['success' => false, 'message' => 'Error inesperado: No se pudo asociar el token a un usuario.']);
}

mysqli_close($conexion); // Cerrar conexión
exit;
?>
