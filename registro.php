<?php
// --- registro.php ---
session_start(); // Iniciar sesión para verificar si el usuario ya está logueado

// Verificar si el usuario ya inició sesión
$loggedIn = isset($_SESSION['idUsuario']) && !empty($_SESSION['idUsuario']);
$nombreUsuario = $loggedIn ? ($_SESSION['nombreUsuario'] ?? 'Usuario') : ''; // Obtener nombre si está logueado
$idUsuario = $loggedIn ? $_SESSION['idUsuario'] : null; // Obtener ID si es necesario

// MODIFICADO: Calcular $panelLink apuntando a .php
$panelLink = 'index.php'; // Default fallback
if ($loggedIn && isset($_SESSION['rolUsuario'])) {
    switch (strtolower($_SESSION['rolUsuario'])) {
        case 'paciente':
            $panelLink = 'perfil-usuario.php'; // Cambiado a .php
            break;
        case 'medico':
            $panelLink = 'perfil-doctores.php'; // Cambiado a .php
            break;
        case 'admin':
        case 'administrador':
            $panelLink = 'panel-admin-sistema.php';
            break;
        case 'recepcionista':
            $panelLink = 'panel-admin-recepcionista.html'; // Mantener .html si no se ha renombrado
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="es" class="dark:bg-gray-800">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Registro y Acceso</title>

    <!-- Enlaces a Tailwind CSS, Bootstrap Icons, Font Awesome -->
    <!-- Considera usar tu output.css compilado en lugar del CDN de Tailwind si ya lo tienes configurado -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="dist/output.css"> <!-- Tu CSS compilado con Tailwind -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* Estilos adicionales si son necesarios, por ejemplo, para la animación de la X del menú */
        #hamburger-menu span {
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }

        #hamburger-menu.open span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        #hamburger-menu.open span:nth-child(2) {
            opacity: 0;
        }

        #hamburger-menu.open span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Ocultar tabs por defecto (JS las mostrará) */
        .tab-content {
            display: none;
        }

        /* Estilo para fade-in (si usas la clase .fade-in) */
        .fade-in {
            opacity: 0;
            transition: opacity 0.6s ease-in-out;
        }

        .fade-in.visible {
            opacity: 1;
        }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-800 dark:text-white bg-gray-100 flex flex-col min-h-screen">

    <!-- Header Mejorado -->
    <header class="bg-white bg-opacity-90 shadow-md sticky top-0 z-50 dark:bg-gray-800 dark:bg-opacity-90 backdrop-blur-sm">
        <div class="container mx-auto flex justify-between items-center py-3 px-6">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <!-- Enlazar a index.php si lo renombraste -->
                <a href="index.php">
                    <img src="logo.png" alt="MediAgenda Logo" class="w-10 h-10 md:w-12 md:h-12">
                </a>
                <span class="text-xl md:text-2xl font-bold text-blue-600 dark:text-blue-300">MediAgenda</span>
            </div>

            <!-- Navegación Principal / Acciones -->
            <nav class="flex items-center gap-4">
                <!-- Links para Desktop -->
                <ul id="nav-links-desktop" class="hidden lg:flex items-center gap-4 md:gap-6">
                    <!-- Eliminado enlace a Inicio -->
                    <?php /* <li><a href="index.php" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Inicio</a></li> */ ?>

                    <?php if ($loggedIn): ?>
                        <!-- Mostrar nombre y botón de cerrar sesión si está logueado -->
                        <li class="text-gray-700 dark:text-gray-300">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></li>
                        <li>
                            <a href="mediagenda-backend/logout.php"
                                class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md transition duration-200 text-sm">
                                Cerrar Sesión
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Mostrar botones/enlaces de Login/Registro si no está logueado -->
                        <!-- Restaurando clase tab-button para funcionalidad JS -->
                        <li><button data-target="login" class="tab-button bg-blue-600 hover:bg-blue-700 text-white dark:bg-blue-500 dark:hover:bg-blue-600 dark:text-white font-semibold py-2 px-4 rounded-md transition duration-200 text-sm">Iniciar Sesión</button></li>
                        <li><button data-target="register-patient" class="tab-button bg-green-600 hover:bg-green-700 text-white dark:text-white dark:bg-green-500 dark:hover:bg-green-600 font-semibold py-2 px-4 rounded-md transition duration-200 text-sm">Registro Paciente</button></li>
                        <li><button data-target="register-doctor" class="tab-button bg-purple-600 hover:bg-purple-700 text-white dark:text-white dark:bg-purple-500 dark:hover:bg-purple-600 font-semibold py-2 px-4 rounded-md transition duration-200 text-sm">Registro Médico</button></li>
                    <?php endif; ?>
                </ul>

                <!-- Botón Modo Oscuro -->
                <button id="dark-mode-toggle" class="text-blue-600 dark:text-yellow-400 ml-2 text-xl">
                    <i class="fas fa-moon"></i> <!-- JS cambiará a fa-sun -->
                </button>

                <!-- Menu Hamburguesa (para móvil) -->
                <div class="hamburger-menu lg:hidden flex flex-col gap-1 cursor-pointer ml-3" id="hamburger-menu">
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                </div>
            </nav>

        </div>
        <!-- Menú Móvil Desplegable -->
        <div id="mobile-menu" class="lg:hidden hidden flex-col bg-white dark:bg-gray-800 absolute w-full shadow-lg pb-4">
            <a href="index.php" class="block px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">Inicio</a>
            <?php if (!$loggedIn): ?>
                <!-- Restaurados botones específicos de registro paciente/médico -->
                <button data-target="login" class="tab-button block w-full text-left px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">Iniciar Sesión</button>
                <button data-target="register-patient" class="tab-button block w-full text-left px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">Registro Paciente</button>
                <button data-target="register-doctor" class="tab-button block w-full text-left px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">Registro Médico</button>
            <?php else: ?>
                <span class="block px-6 py-2 text-gray-500 dark:text-gray-400">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></span>
                <a href="mediagenda-backend/logout.php" class="block px-6 py-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-gray-700">Cerrar Sesión</a>
                <a href="<?php echo $panelLink; ?>" class="block px-6 py-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-700">Mi Panel</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Contenedor Principal -->
    <main class="dark:bg-gray-800 flex-grow w-full">
        <div class="container mx-auto py-10 px-4 md:px-6 h-full">
            <div class="max-w-3xl mx-auto">

                <!-- Mensaje si ya está logueado -->
                <?php if ($loggedIn): ?>
                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 text-center border border-blue-200 dark:border-gray-700">
                        <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Ya has iniciado sesión</h2>
                        <p class="text-lg text-gray-700 dark:text-gray-300 mb-4">Hola <span class="font-medium"><?php echo htmlspecialchars($nombreUsuario); ?></span>, tu sesión está activa.</p>
                        <div class="flex justify-center items-center gap-4">
                            <a href="<?php echo $panelLink; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-md transition duration-200">
                                Ir a mi Panel
                            </a>
                            <a href="mediagenda-backend/logout.php" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-5 rounded-md transition duration-200">
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Pestañas de Navegación (si no está logueado) -->
                    <div class="mb-6 border-b border-gray-300 dark:border-gray-700 flex flex-wrap justify-center space-x-2 md:space-x-6">
                        <button class="tab-button py-2 px-3 md:px-4 text-sm md:text-base text-gray-600 dark:text-gray-400 border-b-2 border-transparent hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" data-target="login">Iniciar Sesión</button>
                        <button class="tab-button py-2 px-3 md:px-4 text-sm md:text-base text-gray-600 dark:text-gray-400 border-b-2 border-transparent hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" data-target="register-patient">Registro Paciente</button>
                        <button class="tab-button py-2 px-3 md:px-4 text-sm md:text-base text-gray-600 dark:text-gray-400 border-b-2 border-transparent hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none" data-target="register-doctor">Registro Médico</button>
                    </div>

                    <!-- Contenido de las Pestañas -->

                    <!-- Pestaña Iniciar Sesión -->
                    <section id="login" class="tab-content">
                        <h2 class="text-2xl md:text-3xl font-bold mb-6 text-center text-gray-800 dark:text-white">Acceso a Usuarios</h2>
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                            <!-- Asegúrate que el form tenga el ID correcto -->
                            <form id="login-form">
                                <div class="mb-4">
                                    <label for="login-email" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Correo Electrónico</label>
                                    <input id="login-email" type="email" name="login_email" required autocomplete="email" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="sucorreo@example.com">
                                </div>
                                <div class="mb-6">
                                    <label for="login-password" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Contraseña</label>
                                    <input id="login-password" type="password" name="login_password" required autocomplete="current-password" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="********">
                                </div>
                                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                                    <!-- Funcionalidad olvidó contraseña - Ahora es un botón que abre modal -->
                                    <button type="button" id="btn-forgot-password" class="text-sm text-blue-600 hover:underline dark:text-blue-400">¿Olvidó su contraseña?</button>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">Iniciar Sesión</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <!-- Pestaña Registro Paciente -->
                    <section id="register-patient" class="tab-content"> <!-- No necesita display:none aquí, JS lo maneja -->
                        <h2 class="text-2xl md:text-3xl font-bold mb-6 text-center text-gray-800 dark:text-white">Registro de Paciente</h2>
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                            <!-- Asegúrate que el form tenga el ID correcto -->
                            <form id="patient-register-form">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="patient-name" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Nombre Completo</label>
                                        <input id="patient-name" type="text" name="patient_name" required class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nombre Apellido">
                                    </div>
                                    <div>
                                        <label for="patient-email" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Correo Electrónico</label>
                                        <input id="patient-email" type="email" name="patient_email" required autocomplete="email" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="paciente@example.com">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label for="patient-phone" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Teléfono</label>
                                        <input id="patient-phone" type="tel" name="patient_phone" required autocomplete="tel" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="+57 3001234567">
                                    </div>
                                    <div>
                                        <label for="patient-password" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Contraseña</label>
                                        <input id="patient-password" type="password" name="patient_password" required minlength="6" autocomplete="new-password" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Mínimo 6 caracteres">
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">Registrarme como Paciente</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <!-- Pestaña Registro Médico -->
                    <section id="register-doctor" class="tab-content"> <!-- No necesita display:none aquí, JS lo maneja -->
                        <h2 class="text-2xl md:text-3xl font-bold mb-6 text-center text-gray-800 dark:text-white">Registro de Médico</h2>
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 md:p-8">
                            <!-- Asegúrate que el form tenga el ID correcto -->
                            <form id="doctor-register-form">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="doctor-name" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Nombre Completo</label>
                                        <input id="doctor-name" type="text" name="doctor_name" required class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Dr. Nombre Apellido">
                                    </div>
                                    <div>
                                        <label for="doctor-specialty" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Especialidad Principal</label>
                                        <input id="doctor-specialty" type="text" name="doctor_specialty" required class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Cardiología">
                                        <!-- Podrías cambiarlo a un <select> si tienes una lista fija -->
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label for="doctor-email" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Correo Electrónico Profesional</label>
                                        <input id="doctor-email" type="email" name="doctor_email" required autocomplete="email" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="doctor@example.com">
                                    </div>
                                    <div>
                                        <label for="doctor-password" class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Contraseña</label>
                                        <input id="doctor-password" type="password" name="doctor_password" required minlength="6" autocomplete="new-password" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Mínimo 6 caracteres">
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">Registrarme como Médico</button>
                                </div>
                            </form>
                        </div>
                    </section>
                <?php endif; ?> <!-- Fin del if !$loggedIn -->

            </div><!-- Fin max-w-3xl -->
        </div> <!-- Fin container -->
    </main>

    <!-- Modal Olvidó Contraseña (Oculto por defecto) -->
    <div id="forgot-password-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-800 bg-opacity-75 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Restablecer Contraseña</h3>
                <button id="close-forgot-modal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&times;</button>
            </div>
            <form id="forgot-password-form">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
                <div class="mb-4">
                    <label for="forgot-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico</label>
                    <input type="email" id="forgot-email" name="forgot_email" required autocomplete="email" class="w-full px-4 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="tuCorreo@example.com">
                </div>
                <div class="text-right">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">Enviar Enlace</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-10 dark:bg-gray-800 mt-auto">
        <div class="container mx-auto flex flex-wrap justify-between gap-8 px-6"> <!-- Añadido px-6 para consistencia -->
            <div>
                <h3 class="text-lg font-semibold mb-4">MediAgenda</h3>
                <p class="text-gray-400">Facilitar la programación de la asistencia médica.</p>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Enlaces Rápidos</h3>
                <ul>
                    <li><a href="index.php#about" class="text-gray-400 hover:text-white">Acerca de</a></li>
                    <li><a href="index.php#services" class="text-gray-400 hover:text-white">Servicios</a></li>
                    <li><a href="contacto.html" class="text-gray-400 hover:text-white">Contacto</a></li>
                    <li><a href="politicas.html" class="text-gray-400 hover:text-white">Políticas</a></li>
                    <!-- Podrías añadir enlace a Términos si existe -->
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Contáctenos</h3>
                <p class="text-gray-400">Email: info@mediagenda.com</p>
                <p class="text-gray-400">Teléfono: 315 2885138</p>
            </div>
        </div>
        <div class="text-center text-gray-500 text-sm mt-8 border-t border-gray-700 pt-6">
            © <?php echo date("Y"); ?> MediAgenda. Todos los derechos reservados.
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="scripts.js"></script>
</body>

</html>