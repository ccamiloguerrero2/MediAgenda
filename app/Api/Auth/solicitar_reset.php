<?php
/**
 * API Endpoint para Solicitar el Restablecimiento de Contraseña
 *
 * Verifica si el correo electrónico proporcionado existe en la base de datos.
 * Si existe, genera un token único y seguro para el restablecimiento,
 * lo almacena en la tabla 'password_resets' junto con una fecha de expiración,
 * y (conceptualmente) envía un correo electrónico al usuario con un enlace
 * que contiene dicho token.
 * Por seguridad, siempre devuelve una respuesta genérica de éxito al frontend,
 * independientemente de si el correo existía o no, para no revelar información.
 *
 * @package MediAgenda\App\Api\Auth
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0'); // MUY IMPORTANTE: No mostrar errores en producción
ini_set('log_errors', '1');    // Siempre loguear errores

// --- Encabezado de Respuesta ---
header('Content-Type: application/json');

// --- Definir Ruta Raíz ---
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
// No requiere sesión.
require_once PROJECT_ROOT . '/app/Core/database.php'; // Conexión a la BD ($conexion)

// --- Verificar Conexión ---
if (!isset($conexion) || !$conexion) {
    // conexion.php ya debería haber logueado y enviado un error JSON 500.
    // Salimos aquí por si acaso.
    exit;
}

// --- Verificar Método HTTP ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
    exit;
}

// --- Obtener y Validar Email ---
$email = filter_input(INPUT_POST, 'forgot_email', FILTER_VALIDATE_EMAIL);

// Si el email falta o no es válido, devolvemos la respuesta genérica igualmente
// para no dar pistas sobre la validez de los emails.
if (!$email) {
    error_log("Intento de solicitud de reseteo con email inválido o faltante.");
    echo json_encode(['success' => true]); // Respuesta genérica
    exit;
}

// --- Lógica Principal (dentro de try...catch) ---
$userExists = false;
$idUsuario = null; // Guardar ID por si se necesita para logs

try {
    // 1. Verificar si el Usuario Existe
    $sql_check_user = "SELECT idUsuario FROM Usuario WHERE email = ? LIMIT 1";
    $stmt_check = mysqli_prepare($conexion, $sql_check_user);
    if (!$stmt_check) throw new Exception("Error DB: Preparando verificación de usuario - " . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $userData = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if ($userData) {
        $userExists = true;
        $idUsuario = $userData['idUsuario'];
        error_log("Usuario encontrado para solicitud de reseteo. Email: {$email}, ID: {$idUsuario}");
    } else {
        error_log("Solicitud de reseteo para email no registrado: " . $email);
        // ¡IMPORTANTE! No salimos aquí, continuamos para dar respuesta genérica.
    }

    // 2. Generar y Guardar Token (SOLO SI el usuario existe)
    if ($userExists) {
        // Generar token seguro
        $token = bin2hex(random_bytes(32)); // 64 caracteres hexadecimales

        // Calcular fecha de expiración (ej. 1 hora desde ahora)
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Usar INSERT ... ON DUPLICATE KEY UPDATE para manejar casos donde
        // el usuario solicita un nuevo reseteo antes de que expire el anterior.
        // Esto reemplazará el token y la fecha de expiración antiguos.
        $sql_token = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)
                      ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        $stmt_token = mysqli_prepare($conexion, $sql_token);
        if (!$stmt_token) throw new Exception("Error DB: Preparando guardado de token - " . mysqli_error($conexion));

        mysqli_stmt_bind_param($stmt_token, "sss", $email, $token, $expires_at);
        if (!mysqli_stmt_execute($stmt_token)) {
            throw new Exception("Error DB: Ejecutando guardado de token - " . mysqli_stmt_error($stmt_token));
        }
        mysqli_stmt_close($stmt_token);
        error_log("Token de reseteo generado/actualizado para email: {$email}");

        // 3. Enviar Correo Electrónico (Lógica Conceptual)
        // Construir el enlace de reseteo (asegúrate que la URL base sea correcta)
        // La URL debe apuntar al script PHP que muestra el formulario (reset_password.php),
        // NO al script que procesa (procesar_reset.php).
        $resetLinkBase = "http://mediagenda.local"; // Cambiar por tu dominio real o config
        $resetLink = $resetLinkBase . "/api/Auth/reset_password.php?token=" . urlencode($token);

        $subject = "Restablecer Contraseña - MediAgenda";
        // Mensaje de correo (considerar usar plantilla HTML para mejor formato)
        $message = "Hola,\n\n";
        $message .= "Recibimos una solicitud para restablecer la contraseña de tu cuenta en MediAgenda.\n\n";
        $message .= "Si tú realizaste esta solicitud, haz clic en el siguiente enlace para continuar. Este enlace es válido por 1 hora:\n";
        $message .= $resetLink . "\n\n";
        $message .= "Si no solicitaste restablecer tu contraseña, puedes ignorar este mensaje de forma segura.\n\n";
        $message .= "Saludos,\nEl equipo de MediAgenda";

        // Cabeceras del correo
        $headers = 'From: MediAgenda <no-reply@mediagenda.local>' . "\r\n" . // ¡AJUSTA EL REMITENTE!
                   'Reply-To: no-reply@mediagenda.local' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion() . "\r\n" .
                   'Content-Type: text/plain; charset=UTF-8'; // Asegurar UTF-8

        // --- ¡ENVÍO REAL DEL CORREO! ---
        // DESCOMENTA y CONFIGURA tu servidor para enviar correos.
        // La función mail() puede no funcionar en localhost sin configuración adicional (sendmail, SMTP).
        // Considera usar librerías como PHPMailer para mayor fiabilidad y opciones (SMTP, HTML).
        /*
        if (mail($email, $subject, $message, $headers)) {
            error_log("Email de reseteo enviado exitosamente a: " . $email);
        } else {
            error_log("ERROR CRÍTICO al enviar email de reseteo a: " . $email . " - Verifica configuración mail()");
            // Podrías lanzar una excepción aquí si el envío de email es CRÍTICO
            // throw new Exception("No se pudo enviar el correo de restablecimiento.");
        }
        */

        // Log para depuración (muestra el enlace que se enviaría)
        error_log("DEBUG (Envío Email Simulado): Destinatario={$email}, Asunto={$subject}, Enlace={$resetLink}");

    } // Fin if ($userExists)

    // 4. Enviar Respuesta Genérica al Frontend
    // Se envía éxito incluso si el email no existía.
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // --- Manejo de Errores Internos ---
    http_response_code(500); // Internal Server Error
    error_log("Error crítico en " . basename(__FILE__) . " para email {$email}: " . $e->getMessage());
    // ¡IMPORTANTE! Enviar respuesta genérica también en caso de error interno
    // para no revelar detalles del fallo al posible atacante.
    echo json_encode(['success' => true]);

} finally {
    // --- Cerrar Conexión ---
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
}

exit;
?>