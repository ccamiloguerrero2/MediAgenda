<?php
/**
 * Página para Mostrar el Formulario de Restablecimiento de Contraseña
 *
 * Verifica la validez de un token de reseteo proporcionado en la URL.
 * Si el token es válido y no ha expirado, muestra un formulario HTML
 * donde el usuario puede ingresar y confirmar su nueva contraseña.
 * Si el token es inválido o ha expirado, muestra un mensaje de error.
 *
 * @package MediAgenda\App\Api\Auth
 */

// --- Modo Estricto y Reporte de Errores ---
declare(strict_types=1);
error_reporting(E_ALL);
// Mostrar errores aquí SÍ es útil durante el desarrollo,
// pero debería desactivarse en producción o configurarse para logging.
ini_set('display_errors', '1'); // TEMPORALMENTE ACTIVADO PARA DESARROLLO
ini_set('log_errors', '1');

// --- NO Iniciar Sesión ---
// Esta página no debe requerir una sesión activa.

// --- Definir Ruta Raíz ---
define('PROJECT_ROOT', dirname(__DIR__, 3));

// --- Dependencias Core ---
// Solo necesitamos la conexión a la BD.
require_once PROJECT_ROOT . '/app/Core/database.php'; // Conexión a la BD ($conexion)

// --- Verificar Conexión (Importante antes de usar $conexion) ---
if (!isset($conexion) || !$conexion) {
    // Si conexion.php falló y envió JSON, esta parte no se ejecutará.
    // Si $conexion no está seteada, mostrar error HTML aquí.
    http_response_code(503); // Service Unavailable
    die("<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Error del Servidor</h1><p>No se pudo establecer la conexión con la base de datos. Por favor, intente más tarde.</p></body></html>");
}


// --- Obtener y Validar Token de la URL ---
$token = trim($_GET['token'] ?? '');
$isValidToken = false;
$errorMessage = ''; // Mensaje de error para mostrar en HTML si es inválido

if (empty($token)) {
    $errorMessage = "El enlace utilizado no contiene un token de restablecimiento.";
} else {
    // Podría añadirse validación de formato de token (ej. longitud hexadecimal)
    // if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    //    $errorMessage = "El formato del token es inválido.";
    // } else {
        // --- Verificar Validez del Token en la BD ---
        try {
            $currentTime = date('Y-m-d H:i:s');
            $sqlCheck = "SELECT email FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1";
            $stmtCheck = mysqli_prepare($conexion, $sqlCheck);

            if (!$stmtCheck) throw new Exception("Error DB: Preparando verificación de token - " . mysqli_error($conexion));

            mysqli_stmt_bind_param($stmtCheck, "ss", $token, $currentTime);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);
            $result = mysqli_fetch_assoc($resultCheck);
            mysqli_stmt_close($stmtCheck);

            if ($result) {
                $isValidToken = true; // Token encontrado y no expirado
                error_log("Token de reseteo válido presentado: " . $token);
            } else {
                $errorMessage = "El enlace de restablecimiento es inválido o ha expirado. Por favor, solicita uno nuevo.";
                error_log("Token de reseteo inválido o expirado presentado: " . $token);
            }

        } catch (Exception $e) {
            error_log("Error crítico DB al verificar token en reset_password.php: " . $e->getMessage());
            $errorMessage = "Ocurrió un error al verificar tu solicitud. Inténtalo más tarde.";
            // Considerar mostrar un error 500 genérico en lugar del formulario
            // http_response_code(500);
            // $isValidToken = false; // Asegurarse que no se muestre el form
        }
    // } // Fin else validación formato token
}

// --- Cerrar Conexión (Ya no se necesita para el resto del HTML) ---
if (isset($conexion) && $conexion) {
    mysqli_close($conexion);
}

?>
<!DOCTYPE html>
<html lang="es" class="<?php /* Podríamos intentar detectar preferencia OS aquí, pero sin JS es limitado */ echo 'light'; // Default a light ya que no hay cookie fácil ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - MediAgenda</title>
    <!-- --- CSS --- -->
    <!-- Usar ruta absoluta desde el DocumentRoot (public/) -->
    <link rel="stylesheet" href="/dist/output.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS (para mostrar éxito/error del formulario JS) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* Estilos mínimos si Tailwind no carga */
        body { font-family: 'Roboto', sans-serif; }
        .error-message { display: none; color: #ef4444; /* Rojo */ font-size: 0.75rem; margin-top: 0.25rem; }
        input.border-red-500 { border-color: #ef4444 !important; } /* Forzar borde rojo */
        .button-spinner { display: none; } /* Ocultar spinner por defecto */
    </style>
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 dark:text-white flex flex-col min-h-screen items-center justify-center p-4">

    <div class="w-full max-w-md p-6 md:p-8 space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl border dark:border-gray-700">

        <div class="text-center">
            <!-- Logo o Nombre -->
            <a href="/index.php" class="inline-block mb-4">
                 <!-- <img src="/img/logo.png" alt="MediAgenda Logo" class="h-12 w-auto mx-auto"> -->
                 <span class="text-3xl font-bold text-blue-600 dark:text-blue-400" style="font-family: 'Montserrat', sans-serif;">MediAgenda</span>
            </a>
        </div>

        <?php if ($isValidToken): ?>
            <!-- === FORMULARIO DE NUEVA CONTRASEÑA (Token Válido) === -->
            <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white">Establecer Nueva Contraseña</h2>
            <form id="reset-password-form" method="POST" action="/api/Auth/procesar_reset.php" novalidate>
                <!-- Campo oculto para enviar el token -->
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-4">
                    <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nueva Contraseña <span class="text-red-500">*</span></label>
                    <input type="password" id="new_password" name="new_password" required minlength="6" autocomplete="new-password"
                           class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:border-blue-500"
                           placeholder="Mínimo 6 caracteres">
                    <p class="error-message" id="new_password_error"></p>
                </div>
                <div class="mb-6">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmar Nueva Contraseña <span class="text-red-500">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password"
                           class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:border-blue-500"
                           placeholder="Repite la contraseña">
                     <p class="error-message" id="confirm_password_error"></p>
                </div>
                <div>
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-md transition duration-200 flex items-center justify-center disabled:opacity-60 disabled:cursor-not-allowed">
                        <span class="button-text">Actualizar Contraseña</span>
                        <svg class="animate-spin h-5 w-5 text-white ml-3 button-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                           <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                           <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
            <!-- Fin Formulario -->

        <?php else: ?>
            <!-- === MENSAJE DE ERROR (Token Inválido/Expirado) === -->
            <h2 class="text-2xl font-bold text-center text-red-600 dark:text-red-400">Enlace Inválido o Expirado</h2>
            <p class="text-center text-gray-600 dark:text-gray-300 mt-4">
                <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); // Mostrar el mensaje de error específico ?>
            </p>
            <div class="mt-6 text-center">
                 <a href="/registro.php" class="text-blue-600 hover:underline dark:text-blue-400 font-medium">
                     <i class="bi bi-arrow-left mr-1"></i> Volver a Inicio de Sesión
                 </a>
            </div>
            <!-- Fin Mensaje de Error -->
        <?php endif; ?>

    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- Script para manejar el formulario con Fetch API -->
    <script>
        const resetForm = document.getElementById('reset-password-form');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const newPasswordError = document.getElementById('new_password_error');
        const confirmPasswordError = document.getElementById('confirm_password_error');
        const submitButton = resetForm?.querySelector('button[type="submit"]');
        const buttonText = submitButton?.querySelector('.button-text');
        const buttonSpinner = submitButton?.querySelector('.button-spinner');

        // --- Funciones auxiliares (locales, no dependen de scripts.js) ---
        function setFormLoadingState(isLoading) {
             if (!submitButton || !buttonText || !buttonSpinner) return;
             if (isLoading) {
                 submitButton.disabled = true;
                 buttonText.classList.add('hidden'); // Ocultar texto
                 buttonSpinner.style.display = 'inline-block'; // Mostrar spinner
             } else {
                 submitButton.disabled = false;
                 buttonText.classList.remove('hidden'); // Mostrar texto
                 buttonSpinner.style.display = 'none'; // Ocultar spinner
             }
        }

        function showFieldError(field, errorElement, message) {
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block'; // Mostrar mensaje
            }
            field?.classList.add('border-red-500');
        }

        function clearFieldError(field, errorElement) {
             if (errorElement) {
                 errorElement.textContent = '';
                 errorElement.style.display = 'none'; // Ocultar mensaje
             }
             field?.classList.remove('border-red-500');
        }
        // --- Fin Funciones auxiliares ---

        if (resetForm) {
            resetForm.addEventListener('submit', async (e) => {
                e.preventDefault(); // Prevenir envío HTML tradicional
                console.log("Formulario de reseteo enviado...");

                // Limpiar errores previos
                clearFieldError(newPasswordInput, newPasswordError);
                clearFieldError(confirmPasswordInput, confirmPasswordError);

                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                // Validaciones básicas frontend
                let isValid = true;
                if (newPassword.length < 6) {
                    showFieldError(newPasswordInput, newPasswordError, 'La contraseña debe tener al menos 6 caracteres.');
                    isValid = false;
                }
                if (newPassword !== confirmPassword) {
                     showFieldError(confirmPasswordInput, confirmPasswordError, 'Las contraseñas no coinciden.');
                     isValid = false;
                }

                if (!isValid) {
                    console.log("Errores de validación encontrados.");
                    return;
                }

                setFormLoadingState(true); // Activar estado de carga
                const formData = new FormData(resetForm);
                const apiUrl = resetForm.getAttribute('action'); // Obtener URL del action

                try {
                    console.log(`Enviando a: ${apiUrl}`);
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData,
                        headers: { // Indicar que esperamos JSON
                            'Accept': 'application/json'
                        }
                    });

                    console.log(`Respuesta recibida, status: ${response.status}`);
                    const data = await response.json(); // Intentar parsear JSON siempre
                    console.log("Datos de respuesta:", data);

                    if (response.ok && data?.success) { // Verificar status OK y success: true
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: data.message || 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
                            confirmButtonText: 'Ir a Iniciar Sesión'
                        }).then(() => {
                            // Redirige a la página de registro/login (ruta absoluta desde la raíz pública)
                            window.location.href = '/registro.php#login';
                        });
                    } else {
                         // Mostrar error específico del backend o genérico
                         Swal.fire('Error', data?.message || `Error ${response.status}: No se pudo actualizar la contraseña. Verifica los datos o inténtalo más tarde.`, 'error');
                    }
                } catch (error) {
                     console.error('Error en fetch procesar_reset:', error);
                     Swal.fire('Error de Red', 'Ocurrió un problema de comunicación con el servidor. Revisa tu conexión e inténtalo de nuevo.', 'error');
                } finally {
                    setFormLoadingState(false); // Desactivar estado de carga
                }
            });
        } else {
            console.log("Formulario de reseteo no encontrado en esta página (probablemente token inválido).");
        }
    </script>

</body>
</html>