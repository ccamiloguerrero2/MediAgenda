<?php
/**
 * Página de Registro y Acceso de Usuarios (Paciente/Médico/Admin)
 *
 * Permite a los usuarios registrarse como pacientes o médicos,
 * o iniciar sesión si ya tienen una cuenta.
 * Si un usuario ya ha iniciado sesión, muestra un mensaje de bienvenida
 * y enlaces a su panel correspondiente y para cerrar sesión.
 *
 * @package MediAgenda\Public
 */

// --- Dependencias Core ---
// Inicia/reanuda la sesión y carga utilidades de sesión (roles, estado de login, etc.)
require_once __DIR__ . '/../app/Core/session_utils.php';

// Determina si mostrar el mensaje de bienvenida o los formularios de registro/login
$mostrarFormularios = !$loggedIn; // $loggedIn viene de session_utils.php

?>
<!DOCTYPE html>
<html lang="es" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : ''; // Aplica clase dark si la cookie existe ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Registro y Acceso</title>

    <!-- --- CSS --- -->
    <!-- Tailwind CSS compilado -->
    <link rel="stylesheet" href="/dist/output.css"> <!-- Ruta absoluta desde la raíz pública -->
    <!-- Iconos (Bootstrap Icons y Font Awesome) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS (para notificaciones) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts (Cargadas globalmente, probablemente en header.php o directamente aquí si es necesario) -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- --- Estilos Específicos de la Página --- -->
    <style>
        /* Estilo para la fuente del logo */
        .logo-pacifico { font-family: 'Pacifico', cursive; }

        /* Ocultar tabs por defecto (JavaScript las mostrará) */
        .tab-content { display: none; }

        /* Animación del icono Hamburguesa (si se usa el mismo header que index) */
        #hamburger-menu span { transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out; }
        #hamburger-menu.open span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        #hamburger-menu.open span:nth-child(2) { opacity: 0; }
        #hamburger-menu.open span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

        /* Estilo para errores de validación JS */
        .error-message { display: none; /* Oculto por defecto */ }
        input.border-red-500, select.border-red-500 { border-color: #ef4444; }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-white flex flex-col min-h-screen">

    <!-- Incluir Cabecera (Logo, Menú Principal, Botón Dark Mode) -->
    <?php require_once __DIR__ . '/../app/Views/Layout/header.php'; ?>

    <!-- Contenedor Principal -->
    <main class="dark:bg-gray-800 flex-grow w-full">
        <div class="container mx-auto py-10 px-4 md:px-6 h-full">
            <div class="max-w-3xl mx-auto">

                <?php if (!$mostrarFormularios): ?>
                    <!-- --- Bloque: Usuario Ya Autenticado --- -->
                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8 text-center border border-blue-200 dark:border-gray-700">
                        <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Ya has iniciado sesión</h2>
                        <p class="text-lg text-gray-700 dark:text-gray-300 mb-6">
                            Hola <span class="font-medium text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars($nombreUsuario); ?></span>, tu sesión está activa.
                        </p>
                        <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                            <a href="<?php echo htmlspecialchars($panelLink); // $panelLink viene de session_utils.php ?>"
                               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-md transition duration-200 flex-grow sm:flex-grow-0 w-full sm:w-auto">
                                <i class="bi bi-speedometer2 mr-1"></i> Ir a mi Panel
                            </a>
                            <a href="/api/Auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-5 rounded-md transition duration-200 flex-grow sm:flex-grow-0 w-full sm:w-auto">
                                <i class="bi bi-box-arrow-right mr-1"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                    <!-- Fin Bloque Usuario Ya Autenticado -->

                <?php else: ?>
                    <!-- --- Bloque: Formularios de Registro/Login --- -->

                    <!-- Navegación por Pestañas -->
                    <div class="mb-6 border-b border-gray-300 dark:border-gray-700 flex flex-wrap justify-center space-x-2 md:space-x-6">
                        <button class="tab-button py-2 px-3 md:px-4 text-sm md:text-base text-gray-600 dark:text-gray-400 border-b-2 border-transparent hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" data-target="login">Iniciar Sesión</button>
                        <button class="tab-button py-2 px-3 md:px-4 text-sm md:text-base text-gray-600 dark:text-gray-400 border-b-2 border-transparent hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" data-target="register-patient">Registro Paciente</button>
                        <button class="tab-button py-2 px-3 md:px-4 text-sm md:text-base text-gray-600 dark:text-gray-400 border-b-2 border-transparent hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" data-target="register-doctor">Registro Médico</button>
                    </div>

                    <!-- Contenido de las Pestañas -->

                    <!-- Pestaña: Iniciar Sesión -->
                    <section id="login" class="tab-content bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                        <h2 class="text-2xl md:text-3xl font-bold mb-6 text-center text-gray-800 dark:text-white">Acceso a Usuarios</h2>
                        <form id="login-form" novalidate>
                            <div class="mb-4">
                                <label for="login-email" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                                <input id="login-email" type="email" name="login_email" required autocomplete="email" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="tu@correo.com">
                                <p class="text-xs text-red-600 mt-1 error-message" id="login-email-error"></p> <!-- Placeholder Error -->
                            </div>
                            <div class="mb-6">
                                <label for="login-password" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Contraseña <span class="text-red-500">*</span></label>
                                <input id="login-password" type="password" name="login_password" required autocomplete="current-password" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="********">
                                <p class="text-xs text-red-600 mt-1 error-message" id="login-password-error"></p> <!-- Placeholder Error -->
                            </div>
                            <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
                                <button type="button" id="btn-forgot-password" class="text-sm text-blue-600 hover:underline dark:text-blue-400 focus:outline-none">¿Olvidó su contraseña?</button>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-200 flex items-center justify-center">
                                    <span class="button-text">Iniciar Sesión</span>
                                    <!-- Spinner (opcional, si se implementa en JS setLoadingState) -->
                                    <!-- <svg class="animate-spin h-5 w-5 text-white ml-2 hidden button-spinner" ...></svg> -->
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Pestaña: Registro Paciente -->
                    <section id="register-patient" class="tab-content bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                        <h2 class="text-2xl md:text-3xl font-bold mb-6 text-center text-gray-800 dark:text-white">Registro de Paciente</h2>
                        <form id="patient-register-form" novalidate>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="patient-name" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                                    <input id="patient-name" type="text" name="patient_name" required autocomplete="name" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nombre Apellido">
                                    <p class="text-xs text-red-600 mt-1 error-message" id="patient-name-error"></p>
                                </div>
                                <div>
                                    <label for="patient-email" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                                    <input id="patient-email" type="email" name="patient_email" required autocomplete="email" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="paciente@example.com">
                                    <p class="text-xs text-red-600 mt-1 error-message" id="patient-email-error"></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label for="patient-phone" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Teléfono <span class="text-red-500">*</span></label>
                                    <input id="patient-phone" type="tel" name="patient_phone" required autocomplete="tel" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="+57 3001234567">
                                    <p class="text-xs text-red-600 mt-1 error-message" id="patient-phone-error"></p>
                                </div>
                                <div>
                                    <label for="patient-password" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Contraseña <span class="text-red-500">*</span></label>
                                    <input id="patient-password" type="password" name="patient_password" required minlength="6" autocomplete="new-password" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Mínimo 6 caracteres">
                                    <p class="text-xs text-red-600 mt-1 error-message" id="patient-password-error"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">Registrarme como Paciente</button>
                            </div>
                        </form>
                    </section>

                    <!-- Pestaña: Registro Médico -->
                    <section id="register-doctor" class="tab-content bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                        <h2 class="text-2xl md:text-3xl font-bold mb-6 text-center text-gray-800 dark:text-white">Registro de Médico</h2>
                        <form id="doctor-register-form" novalidate>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="doctor-name" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                                    <input id="doctor-name" type="text" name="doctor_name" required autocomplete="name" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Dr. Nombre Apellido">
                                    <p class="text-xs text-red-600 mt-1 error-message" id="doctor-name-error"></p>
                                </div>
                                <div>
                                    <label for="doctor-specialty" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Especialidad Principal <span class="text-red-500">*</span></label>
                                    <input id="doctor-specialty" type="text" name="doctor_specialty" required class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Cardiología">
                                    <p class="text-xs text-red-600 mt-1 error-message" id="doctor-specialty-error"></p>
                                    <!-- Podría ser un <select> si la lista es fija -->
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label for="doctor-email" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Correo Electrónico Profesional <span class="text-red-500">*</span></label>
                                    <input id="doctor-email" type="email" name="doctor_email" required autocomplete="email" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="doctor@example.com">
                                    <p class="text-xs text-red-600 mt-1 error-message" id="doctor-email-error"></p>
                                </div>
                                <div>
                                    <label for="doctor-password" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Contraseña <span class="text-red-500">*</span></label>
                                    <input id="doctor-password" type="password" name="doctor_password" required minlength="6" autocomplete="new-password" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Mínimo 6 caracteres">
                                    <p class="text-xs text-red-600 mt-1 error-message" id="doctor-password-error"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">Registrarme como Médico</button>
                            </div>
                        </form>
                    </section>
                    <!-- Fin Bloque Formularios -->
                <?php endif; ?>

            </div><!-- Fin max-w-3xl -->
        </div> <!-- Fin container -->
    </main>

    <!-- --- Modal: Olvidó Contraseña --- -->
    <div id="forgot-password-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-gray-800 bg-opacity-75 backdrop-blur-sm p-4 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md mx-auto transform transition-all scale-95 opacity-0"
             id="forgot-password-modal-content"> <!-- ID para animar contenido -->
            <div class="flex justify-between items-center mb-4 border-b pb-2 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Restablecer Contraseña</h3>
                <button id="close-forgot-modal" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 text-2xl leading-none focus:outline-none" aria-label="Cerrar modal">×</button>
            </div>
            <form id="forgot-password-form" novalidate>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña (si la cuenta existe).</p>
                <div class="mb-4">
                    <label for="forgot-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" id="forgot-email" name="forgot_email" required autocomplete="email" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="tuCorreo@example.com">
                     <p class="text-xs text-red-600 mt-1 error-message" id="forgot-email-error"></p>
                </div>
                <div class="text-right mt-6">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-200 flex items-center justify-center w-full sm:w-auto">
                        <span class="button-text">Enviar Enlace</span>
                         <!-- Spinner (opcional) -->
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Fin Modal Olvidó Contraseña -->

    <!-- Incluir Pie de Página -->
    <?php require_once __DIR__ . '/../app/Views/Layout/footer.php'; ?>

    <!-- --- JavaScript --- -->
    <!-- SweetAlert2 JS (antes de scripts.js) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- Scripts principales de la aplicación (apuntando a la nueva ubicación) -->
    <!-- Script específico para animar la modal (opcional pero mejora UX) -->
    <script>
        const forgotModal = document.getElementById('forgot-password-modal');
        const forgotModalContent = document.getElementById('forgot-password-modal-content');
        const openForgotBtn = document.getElementById('btn-forgot-password');
        const closeForgotBtn = document.getElementById('close-forgot-modal');

        const openForgotModalAnimate = () => {
            if (!forgotModal || !forgotModalContent) return;
            forgotModal.classList.remove('hidden');
            forgotModal.classList.add('flex');
            // Pequeño delay para permitir que el navegador renderice el display:flex antes de la transición
            requestAnimationFrame(() => {
                forgotModal.style.opacity = '1';
                forgotModalContent.style.opacity = '1';
                forgotModalContent.style.transform = 'scale(1)';
                document.getElementById('forgot-email')?.focus();
            });
        };

        const closeForgotModalAnimate = () => {
            if (!forgotModal || !forgotModalContent) return;
            forgotModal.style.opacity = '0';
            forgotModalContent.style.opacity = '0';
            forgotModalContent.style.transform = 'scale(0.95)';
            // Espera a que termine la transición antes de ocultar con display:none
            setTimeout(() => {
                forgotModal.classList.add('hidden');
                forgotModal.classList.remove('flex');
            }, 300); // Debe coincidir con la duración de la transición CSS
        };

        if (openForgotBtn) {
            openForgotBtn.addEventListener('click', openForgotModalAnimate);
        }
        if (closeForgotBtn) {
            closeForgotBtn.addEventListener('click', closeForgotModalAnimate);
        }
        if (forgotModal) {
            // Cerrar al hacer clic fuera del contenido
            forgotModal.addEventListener('click', (e) => {
                if (e.target === forgotModal) {
                    closeForgotModalAnimate();
                }
            });
        }
    </script>

</body>
</html>