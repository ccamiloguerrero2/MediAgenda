<?php
// --- registro.php ---
session_start(); // Iniciar sesión para verificar si el usuario ya está logueado

// Verificar si el usuario ya inició sesión
$loggedIn = isset($_SESSION['idUsuario']) && !empty($_SESSION['idUsuario']);
$nombreUsuario = $loggedIn ? ($_SESSION['nombreUsuario'] ?? 'Usuario') : ''; // Obtener nombre si está logueado
$idUsuario = $loggedIn ? $_SESSION['idUsuario'] : null; // Obtener ID si es necesario

?>
<!DOCTYPE html>
<html lang="es">

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

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-gray-100">

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
                    <!-- Enlazar a index.php si lo renombraste -->
                    <li><a href="index.php" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Inicio</a></li>

                    <?php if ($loggedIn): ?>
                        <!-- Mostrar nombre y botón de cerrar sesión si está logueado -->
                        <li class="text-gray-700 dark:text-gray-300">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></li>
                        <li>
                            <a href="backend/logout.php"
                                class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md transition duration-200 text-sm">
                                Cerrar Sesión
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Mostrar botones/enlaces de Login/Registro si no está logueado -->
                        <!-- Usamos botones que activan las tabs vía JS -->
                        <li><button data-target="login" class="tab-button text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition font-medium">Iniciar Sesión</button></li>
                        <li><button data-target="register-patient" class="tab-button bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-200 text-sm">Registrarse</button></li>
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
            <!-- Enlazar a index.php si lo renombraste -->
            <a href="index.php" class="block px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">Inicio</a>
            <?php if (!$loggedIn): ?>
                <!-- Usamos botones que activan tabs y cierran el menú (JS se encarga) -->
                <button data-target="login" class="tab-button block w-full text-left px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">Iniciar Sesión</button>
                <button data-target="register-patient" class="tab-button block w-full text-left px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">Registro Paciente</button>
                <button data-target="register-doctor" class="tab-button block w-full text-left px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">Registro Médico</button>
            <?php else: ?>
                <span class="block px-6 py-2 text-gray-500 dark:text-gray-400">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></span>
                <a href="backend/logout.php" class="block px-6 py-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-gray-700">Cerrar Sesión</a>
                <!-- Opcional: Enlace al panel correspondiente -->
                <?php
                $panelLink = 'index.php'; // Default fallback
                if (isset($_SESSION['rolUsuario'])) {
                    switch (strtolower($_SESSION['rolUsuario'])) {
                        case 'paciente':
                            $panelLink = 'perfil-usuario.html';
                            break;
                        case 'medico':
                            $panelLink = 'perfil-doctores.html';
                            break;
                        case 'admin':
                        case 'administrador':
                            $panelLink = 'panel-admin-sistema.html';
                            break;
                        case 'recepcionista':
                            $panelLink = 'panel-admin-recepcionista.html';
                            break;
                    }
                }
                ?>
                <a href="<?php echo $panelLink; ?>" class="block px-6 py-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-700">Mi Panel</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Contenedor Principal con Pestañas -->
    <main class="container mx-auto py-10 px-4 md:px-6">
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
                        <a href="backend/logout.php" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-5 rounded-md transition duration-200">
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
                                <!-- Funcionalidad olvidó contraseña no implementada aún -->
                                <a href="#" class="text-sm text-blue-600 hover:underline dark:text-blue-400">¿Olvidó su contraseña?</a>
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
    </main>

    <!-- Footer -->
    <footer class="bg-gray-200 dark:bg-gray-800 py-6 mt-16">
        <div class="container mx-auto text-center text-sm text-gray-600 dark:text-gray-400">
            © <?php echo date("Y"); ?> MediAgenda. Todos los derechos reservados.
            <!-- Puedes añadir más enlaces aquí si quieres -->
        </div>
    </footer>

    <!-- JavaScript (Importante que scripts.js esté al final y DESPUÉS del contenido HTML) -->


    <div id="notification-area" class="fixed top-5 right-5 z-[100] space-y-2 w-full max-w-xs sm:max-w-sm"></div>
    <script src="scripts.js"></script>
</body>