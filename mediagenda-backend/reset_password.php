<?php
// --- mediagenda-backend/reset_password.php ---

// ***** 1. OBTENER Y VALIDAR TOKEN DE LA URL *****
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    // Considera redirigir a una página de error más amigable
    die("Error: Token no proporcionado o inválido.");
}
$token = trim($_GET['token']);
// Podrías añadir validación extra del formato del token si lo deseas (ej: longitud, caracteres)

// ***** 2. INTENTAR CONEXIÓN DIRECTAMENTE AQUÍ (DEBUG) *****
error_log("[DEBUG RESET_PASS] Iniciando intento de conexión...");
$servername = "localhost";
$username = "root";
$password = "";
$database = "mediagenda_db";

$conexion = @mysqli_connect($servername, $username, $password, $database); // Usar @ para suprimir warning estándar

if (!$conexion) {
    error_log("[DEBUG RESET_PASS] mysqli_connect FALLÓ.");
    $error_msg = mysqli_connect_error();
    error_log("[DEBUG RESET_PASS] mysqli_connect_error() dice: " . ($error_msg ? $error_msg : '¿Error vacío?'));
    // Mostrar un mensaje más simple para no depender de JSON
    die("Error crítico de base de datos. Revise los logs.");
} else {
    error_log("[DEBUG RESET_PASS] mysqli_connect ¡ÉXITO!");
    // La conexión funcionó, podemos continuar
}
// ***** FIN DEBUG CONEXIÓN DIRECTA *****

// ***** 3. VERIFICAR VALIDEZ DEL TOKEN (MySQLi) *****
$isValidToken = false;
$email = null;
try {
    $currentTime = date('Y-m-d H:i:s');
    $sqlCheck = "SELECT email FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1";
    $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "ss", $token, $currentTime);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    $result = mysqli_fetch_assoc($resultCheck);

    if ($result) {
        $isValidToken = true;
        $email = $result['email'];
    }
    mysqli_stmt_close($stmtCheck);
} catch (Exception $e) {
    error_log("Error DB check token (reset_password.php - MySQLi): " . $e->getMessage());
    die("Error al verificar la solicitud. Inténtalo más tarde.");
}

mysqli_close($conexion); // Cerrar conexión ya que no se necesita más en el renderizado HTML

?>
<!DOCTYPE html>
<html lang="es" class="dark:bg-gray-800">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - MediAgenda</title>
    <!-- Incluye tus CSS (Tailwind compilado, etc.) -->
    <!-- Asegúrate que la ruta sea correcta desde esta ubicación -->
    <link rel="stylesheet" href="../dist/output.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Incluir SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-gray-100 flex flex-col min-h-screen items-center justify-center p-4">

    <div class="w-full max-w-md p-6 md:p-8 space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl">

        <?php if ($isValidToken): ?>
            <!-- Si el token es válido, muestra el formulario -->
            <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white">Establecer Nueva Contraseña</h2>
            <form id="reset-password-form" novalidate>
                <!-- Campo oculto para enviar el token -->
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="mb-4">
                    <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nueva Contraseña</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Mínimo 6 caracteres">
                    <p class="text-xs text-red-600 mt-1 hidden" id="new_password_error">La contraseña debe tener al menos 6 caracteres.</p>
                </div>
                <div class="mb-6">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmar Nueva Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Repite la contraseña">
                     <p class="text-xs text-red-600 mt-1 hidden" id="confirm_password_error">Las contraseñas no coinciden.</p>
                </div>
                <div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-200 flex items-center justify-center">
                        <span class="button-text">Actualizar Contraseña</span>
                        <svg class="animate-spin h-5 w-5 text-white ml-2 hidden button-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                           <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                           <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <!-- Si el token NO es válido, muestra un mensaje de error -->
            <h2 class="text-2xl font-bold text-center text-red-600 dark:text-red-400">Enlace Inválido o Expirado</h2>
            <p class="text-center text-gray-600 dark:text-gray-300 mt-4">
                El enlace para restablecer la contraseña no es válido o ha expirado.
                Por favor, <a href="../registro.php" class="text-blue-600 hover:underline dark:text-blue-400">solicita uno nuevo</a>. <!-- Asegúrate que la ruta a registro.php sea correcta -->
            </p>
        <?php endif; ?>

    </div>

    <!-- Incluir SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- Script para manejar el formulario -->
    <script>
        const resetForm = document.getElementById('reset-password-form');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const newPasswordError = document.getElementById('new_password_error');
        const confirmPasswordError = document.getElementById('confirm_password_error');

        // --- Funciones auxiliares (simplificadas, sin usar helpers globales) ---
        function setLoadingState(form, isLoading) {
             const btn = form.querySelector('button[type="submit"]');
             const text = btn.querySelector('.button-text');
             const spinner = btn.querySelector('.button-spinner');
             if (!btn || !text || !spinner) return;

             if (isLoading) {
                 btn.disabled = true;
                 text.classList.add('hidden');
                 spinner.classList.remove('hidden');
             } else {
                 btn.disabled = false;
                 text.classList.remove('hidden');
                 spinner.classList.add('hidden');
             }
        }

        function showFieldError(field, errorElement, message) {
             if (errorElement) {
                 errorElement.textContent = message;
                 errorElement.classList.remove('hidden');
             }
             if (field) field.classList.add('border-red-500'); // Marca el campo
        }
        function clearFieldError(field, errorElement) {
             if (errorElement) errorElement.classList.add('hidden');
             if (field) field.classList.remove('border-red-500');
        }
        // --- Fin Funciones auxiliares ---

        if (resetForm) {
            resetForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearFieldError(newPasswordInput, newPasswordError);
                clearFieldError(confirmPasswordInput, confirmPasswordError);

                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                // Validaciones básicas en frontend
                let isValid = true;
                if (newPassword.length < 6) {
                    showFieldError(newPasswordInput, newPasswordError, 'La contraseña debe tener al menos 6 caracteres.');
                    isValid = false;
                }
                if (newPassword !== confirmPassword) {
                     showFieldError(confirmPasswordInput, confirmPasswordError, 'Las contraseñas no coinciden.');
                     isValid = false;
                }

                if (!isValid) return; // Detener si hay errores de validación

                setLoadingState(resetForm, true);
                const formData = new FormData(resetForm);

                try {
                     // URL del script PHP que procesa el reset
                    const response = await fetch('procesar_reset.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data?.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: data.message || 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
                            confirmButtonText: 'Ir a Iniciar Sesión'
                        }).then(() => {
                            // Redirige a la página de registro/login
                            window.location.href = '../registro.php#login'; // Ajusta la ruta si es necesario
                        });
                    } else {
                         // Mostrar error general o específico del backend
                         Swal.fire('Error', data?.message || 'No se pudo actualizar la contraseña. Verifica los datos o inténtalo más tarde.', 'error');
                    }
                } catch (error) {
                     console.error('Error en fetch procesar_reset:', error);
                     Swal.fire('Error', 'Ocurrió un problema de comunicación con el servidor.', 'error');
                } finally {
                    setLoadingState(resetForm, false);
                }
            });
        }
    </script>

</body>
</html>