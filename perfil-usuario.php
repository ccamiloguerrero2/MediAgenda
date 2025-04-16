<?php
session_start(); // Iniciar/reanudar la sesión

// Verificar si el usuario está logueado. Si no, redirigir a registro.php
if (!isset($_SESSION['idUsuario']) || empty($_SESSION['idUsuario'])) {
    // Puedes añadir un parámetro para mostrar un mensaje en registro.php si quieres
    header('Location: registro.php#login'); // Redirige a la pestaña de login
    exit; // Detiene la ejecución del script para evitar que se cargue el resto del HTML
}

// Verificar si el usuario tiene el rol correcto (paciente)
if (!isset($_SESSION['rolUsuario']) || strtolower($_SESSION['rolUsuario']) !== 'paciente') {
    // El usuario está logueado pero no es paciente, redirigir a index con mensaje de error
    header('Location: index.php?error=unauthorized_role');
    exit;
}

// Si llegamos aquí, el usuario está logueado y es paciente. Podemos obtener su nombre si es necesario.
$nombreUsuario = $_SESSION['nombreUsuario'] ?? 'Usuario';

?>
<!DOCTYPE html>
<html lang="es" class="dark:bg-gray-800">

<head>
    <!-- ... resto del head ... -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Panel de Pacientes</title>

    <!-- Enlaces CSS -->
    <link rel="stylesheet" href="dist/output.css"> <!-- Tailwind Compilado -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos adicionales si son necesarios */
        /* Estilo para fade-in (si usas la clase .fade-in) */
        .fade-in {
            opacity: 0;
            transition: opacity 0.6s ease-in-out;
        }

        .fade-in.visible {
            opacity: 1;
        }

        /* Placeholder styles */
        #appointments-list li.placeholder,
        #history-list li.placeholder {
            /* Cambiado #past-appointments-list a #history-list */
            color: #9ca3af;
            /* gray-400 */
            font-style: italic;
        }

        /* Estilo para botones de cita deshabilitados (opcional) */
        .appointment-action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-800 dark:text-white bg-gray-100 flex flex-col min-h-screen">

    <!-- Header -->
    <!-- El header ahora SÍ debe mostrar siempre "Cerrar Sesión", porque solo usuarios logueados verán esta página -->
    <header class="bg-white bg-opacity-90 shadow-md sticky top-0 z-50 dark:bg-gray-800 dark:bg-opacity-90 backdrop-blur-sm">
        <!-- ... resto del header ... -->
        <div class="container mx-auto flex justify-between items-center py-4 px-6">
            <div class="flex items-center gap-2">
                <!-- CORREGIDO: Enlace a index.php -->
                <a href="index.php">
                    <img src="img/logo.png" alt="MediAgenda Logo" class="w-10 h-10">
                </a>
                <span class="text-xl font-bold text-blue-600 dark:text-blue-300">MediAgenda - Panel de Pacientes</span>
            </div>
            <nav class="flex items-center gap-4" style="font-family: 'Roboto', 'Montserrat', Arial, sans-serif;">
                <!-- Navegación Panel Paciente -->
                <ul id="nav-links-desktop" class="hidden lg:flex items-center gap-4 md:gap-6 font-medium tracking-wide text-gray-700 dark:text-gray-200 transition-colors duration-200" style="font-family: 'Roboto', 'Montserrat', Arial, sans-serif;">
                    <li><a href="#profile" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Perfil</a></li>
                    <li><a href="#appointments" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Citas</a></li>
                    <li><a href="#schedule" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Agendar</a></li>
                    <li><a href="#medical-history" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Historial</a></li>
                    <!-- Añadir enlace Cerrar Sesión -->
                    <li>
                        <a href="mediagenda-backend/logout.php" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-3 rounded-md transition duration-200 text-sm">
                            Cerrar Sesión
                        </a>
                    </li>
                </ul>
                <!-- Botón Modo Oscuro -->
                <button id="dark-mode-toggle" class="text-blue-600 dark:text-yellow-400 ml-2 text-xl">
                    <i class="fas fa-moon"></i>
                </button>
                <!-- Botón Hamburguesa (Visible en < lg) -->
                <div class="hamburger-menu lg:hidden flex flex-col gap-1 cursor-pointer" id="hamburger-menu">
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                </div>
            </nav>
            <!-- Menú Móvil Desplegable (Oculto por defecto y en >= lg) -->
            <div id="mobile-menu" class="lg:hidden hidden absolute top-full left-0 w-full bg-white dark:bg-gray-800 shadow-lg border-t border-gray-200 dark:border-gray-700">
                <ul class="flex flex-col items-center gap-4 font-medium tracking-wide text-gray-700 dark:text-gray-200 transition-colors duration-200" style="font-family: 'Roboto', 'Montserrat', Arial, sans-serif;">
                    <li><a href="#profile" class="block py-2 px-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Perfil</a></li>
                    <li><a href="#appointments" class="block py-2 px-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Citas</a></li>
                    <li><a href="#schedule" class="block py-2 px-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Agendar</a></li>
                    <li><a href="#medical-history" class="block py-2 px-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">Historial</a></li>
                    <li class="pt-2 border-t border-gray-200 dark:border-gray-700"><a href="mediagenda-backend/logout.php" class="block text-center bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-3 rounded-md transition duration-200">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dark:bg-gray-800 flex-grow w-full">
        <div class="container mx-auto py-10 px-6">

            <!-- Envoltura del Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Columna 1: Citas e Historial -->
                <div class="flex flex-col gap-8">
                    <section id="appointments" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Tus Citas Programadas</h2>
                        <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800 flex flex-col">
                            <ul id="appointments-list" class="space-y-4">
                                <li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando citas...</li>
                            </ul>
                            <div class="mt-6 border-t pt-4 dark:border-gray-700">
                                <button id="btn-show-schedule" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                                    <i class="bi bi-calendar-plus mr-1"></i> Agendar Nueva Cita
                                </button>
                            </div>
                        </div>
                    </section>

                    <section id="medical-history" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Tu Historial Médico</h2>
                        <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                            <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-100">Diagnósticos y Tratamientos</h3>
                            <ul id="history-list" class="space-y-3">
                                <li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando historial...</li>
                            </ul>
                            <div class="mt-6 border-t pt-4 dark:border-gray-700">
                                <p class="text-sm text-gray-500 dark:text-gray-400">La descarga estará disponible próximamente.</p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Columna 2: Perfil -->
                <div class="flex flex-col gap-8">
                    <section id="profile" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Perfil del Paciente</h2>
                        <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Tus datos personales</h3>
                            <form id="update-profile-form" novalidate>
                                <div class="grid grid-cols-1 gap-6 mb-4">
                                    <div><label for="profile-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre <span class="text-red-500">*</span></label><input type="text" id="profile-nombre" name="nombre" placeholder="Tu nombre" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                        <p class="text-xs text-red-600 mt-1 hidden error-message" id="profile-nombre-error"></p>
                                    </div>
                                    <div><label for="profile-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo <span class="text-red-500">*</span></label><input type="email" id="profile-email" name="email" placeholder="tu@correo.com" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                        <p class="text-xs text-red-600 mt-1 hidden error-message" id="profile-email-error"></p>
                                    </div>
                                    <div><label for="profile-telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label><input type="tel" id="profile-telefono" name="telefono" placeholder="+57..." class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                        <p class="text-xs text-red-600 mt-1 hidden error-message" id="profile-telefono-error"></p>
                                    </div>
                                    <div><label for="profile-direccion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección</label><input type="text" id="profile-direccion" name="direccion" placeholder="Tu dirección" class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                        <p class="text-xs text-red-600 mt-1 hidden error-message" id="profile-direccion-error"></p>
                                    </div>
                                </div>
                                <div class="text-right mt-4"><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-md transition duration-200">Actualizar Datos</button></div>
                            </form>
                        </div>
                    </section>
                </div>

                <!-- Sección Consultas (Eliminada o reubicada si es necesaria) -->
                <!-- <section id="consultations" class="scroll-mt-20 lg:col-span-3"> -->
                <!-- ... Contenido consultas ... -->
                <!-- </section> -->
            </div> <!-- Fin Envoltura del Grid -->

        </div> <!-- Cierre del div container -->
    </main>

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

    <!-- Modal para Agendar Cita -->
    <div id="schedule-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-[60] p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 md:p-8 w-full max-w-2xl relative">
            <!-- Botón de Cierre -->
            <button id="close-schedule-modal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-2xl">&times;</button>

            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Agendar Nueva Cita</h2>

            <form id="schedule-appointment-form" novalidate>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label for="modal-schedule-medico" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Médico <span class="text-red-500">*</span></label>
                        <select id="modal-schedule-medico" name="idMedico" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            <option value="" disabled selected>Seleccione un médico...</option>
                        </select>
                        <p class="text-xs text-red-600 mt-1 hidden error-message" id="modal-schedule-medico-error"></p>
                    </div>
                    <div>
                        <label for="modal-schedule-fecha" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" id="modal-schedule-fecha" name="fecha" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500" min="<?php echo date('Y-m-d'); ?>">
                        <p class="text-xs text-red-600 mt-1 hidden error-message" id="modal-schedule-fecha-error"></p>
                    </div>
                    <div>
                        <label for="modal-schedule-hora" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hora <span class="text-red-500">*</span></label>
                        <input type="time" id="modal-schedule-hora" name="hora" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-red-600 mt-1 hidden error-message" id="modal-schedule-hora-error"></p>
                    </div>
                </div>
                <div class="mb-6"><label for="modal-schedule-motivo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo (Opcional)</label><textarea id="modal-schedule-motivo" name="motivo" rows="3" placeholder="Describa brevemente el motivo..." class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500"></textarea></div>
                <div class="text-right"><button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded-md transition duration-200">Confirmar Cita</button></div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <!-- Incluir SweetAlert2 JS (DESDE CDN) ANTES de tu script -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="scripts.js"></script>

</body>

</html>