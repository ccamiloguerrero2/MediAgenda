<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- mediagenda-backend/solicitar_reset.php ---

header('Content-Type: application/json');

// ***** 1. INCLUIR TU ARCHIVO DE CONEXIÓN A LA BASE DE DATOS *****
// Usando tu archivo conexion.php
require_once 'conexion.php'; // ¡AHORA USA CONEXION.PHP!

// Verificar si $conexion se creó correctamente (desde conexion.php)
if (!isset($conexion) || !$conexion) {
    // El error ya se logueó y se envió respuesta JSON en conexion.php
    // Si llegamos aquí sin $conexion, algo más falló, pero salimos para evitar errores.
    exit;
}

// ***** 2. OBTENER Y VALIDAR EL EMAIL *****
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['forgot_email'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
    exit;
}

$email = filter_var(trim($_POST['forgot_email']), FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => true]); // Respuesta genérica
    exit;
}

// ***** 3. VERIFICAR SI EL USUARIO EXISTE (MySQLi) *****
$userExists = false;
try {
    $sqlCheck = "SELECT idUsuario FROM usuario WHERE email = ? LIMIT 1";
    $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "s", $email);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    if (mysqli_fetch_assoc($resultCheck)) {
        $userExists = true;
    }
    mysqli_stmt_close($stmtCheck);
} catch (Exception $e) { // Captura errores generales y de MySQLi si ocurren
    error_log("Error DB check user (MySQLi): " . $e->getMessage());
    echo json_encode(['success' => true]); // Respuesta genérica
    exit;
}

// ***** 4. GENERAR Y GUARDAR TOKEN SI EL USUARIO EXISTE (MySQLi) *****
if ($userExists) {
    try {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Usar INSERT ... ON DUPLICATE KEY UPDATE para reemplazar si ya existe
        $sqlToken = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        $stmtToken = mysqli_prepare($conexion, $sqlToken);
        mysqli_stmt_bind_param($stmtToken, "sss", $email, $token, $expires_at);
        $executeSuccess = mysqli_stmt_execute($stmtToken);
        mysqli_stmt_close($stmtToken);

        if (!$executeSuccess) {
            throw new Exception("Error al guardar/actualizar el token de reseteo.");
        }

        // ***** 5. ENVIAR CORREO ELECTRÓNICO (LÓGICA CONCEPTUAL - SIN CAMBIOS) *****
        $resetLink = "http://localhost/mediagenda-backend/reset_password.php?token=" . $token; // URL BASE CORREGIDA!
        $subject = "Restablecer Contraseña - MediAgenda";
        $message = "Hola,\n\nHas solicitado restablecer tu contraseña.\n\n";
        $message .= "Haz clic en el siguiente enlace para continuar (válido por 1 hora):\n";
        $message .= $resetLink . "\n\n";
        $message .= "Si no solicitaste esto, puedes ignorar este mensaje.\n\nSaludos,\nEl equipo de MediAgenda";
        $headers = 'From: no-reply@mediagenda.com' . "\r\n" . // ¡AJUSTA EL REMITENTE!
                   'Reply-To: no-reply@mediagenda.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        // ¡DESCOMENTA Y CONFIGURA ESTO PARA ENVIAR EMAILS REALES!
        // if (mail($email, $subject, $message, $headers)) {
        //     error_log("Email de reseteo enviado a: " . $email);
        // } else {
        //     error_log("ERROR al enviar email de reseteo a: " . $email);
        // }

        // Log para depuración
         error_log("DEBUG: Email de reseteo para $email. Link: $resetLink");

    } catch (Exception $e) {
        error_log("Error generando/guardando token o enviando email (MySQLi): " . $e->getMessage());
        echo json_encode(['success' => true]); // Respuesta genérica
        exit;
    }
} else {
     error_log("Solicitud reseteo para email no registrado: " . $email);
}

// ***** 6. RESPUESTA GENÉRICA AL FRONTEND *****
echo json_encode(['success' => true]);

mysqli_close($conexion); // Cerrar conexión MySQLi
exit;

?>
