<?php
session_start(); // Iniciar/reanudar la sesión

// Verificar si el usuario está logueado. Si no, redirigir a registro.php
if (!isset($_SESSION['idUsuario']) || empty($_SESSION['idUsuario'])) {
    // Puedes añadir un parámetro para mostrar un mensaje en registro.php si quieres
    header('Location: registro.php#login'); // Redirige a la pestaña de login
    exit; // Detiene la ejecución del script para evitar que se cargue el resto del HTML
}

// Si llegamos aquí, el usuario está logueado. Podemos obtener su nombre si es necesario.
$nombreUsuario = $_SESSION['nombreUsuario'] ?? 'Usuario';

?>
<!DOCTYPE html>
<html lang="es" class="dark:bg-gray-800">

<head>
    {/* ... resto del head ... */}
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
         .fade-in { opacity: 0; transition: opacity 0.6s ease-in-out; }
         .fade-in.visible { opacity: 1; }
         /* Placeholder styles */
         #appointments-list li.placeholder,
         #history-list li.placeholder { /* Cambiado #past-appointments-list a #history-list */
            color: #9ca3af; /* gray-400 */
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
    {/* El header ahora SÍ debe mostrar siempre "Cerrar Sesión", porque solo usuarios logueados verán esta página */}
    <header class="bg-white bg-opacity-90 shadow-md sticky top-0 z-50 dark:bg-gray-800 dark:bg-opacity-90 backdrop-blur-sm">
       {/* ... resto del header ... */}
        <div class="container mx-auto flex justify-between items-center py-4 px-6">
            <div class="flex items-center gap-2">
                 <!-- CORREGIDO: Enlace a index.php -->
                <a href="index.php">
                    <img src="logo.png" alt="MediAgenda Logo" class="w-10 h-10">
                </a>
                <span class="text-xl font-bold text-blue-600 dark:text-blue-300">MediAgenda - Panel de Pacientes</span>
            </div>
            <nav class="flex items-center gap-4">
                 <!-- Navegación Panel Paciente -->
                 <ul id="nav-links-desktop" class="hidden lg:flex items-center gap-4 md:gap-6">
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
                 <!-- Menú Hamburguesa (si aplica diseño móvil) -->
                 <!-- <div id="hamburger-menu" class="lg:hidden ..."> ... </div> -->
            </nav>
             <!-- Menú Móvil Desplegable -->
             <!-- <div id="mobile-menu" class="lg:hidden hidden ..."> ... </div> -->
        </div>
    </header>

    <!-- Main Content -->
    {/* ... resto del body ... */}
    <main class="dark:bg-gray-800 flex-grow w-full">
        <div class="container mx-auto py-10 px-6">

            <!-- Sección Perfil del Paciente -->
            <section id="profile" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Perfil del Paciente</h2>
                <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Tus datos personales</h3>
                    <form id="update-profile-form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label for="profile-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre completo</label>
                                <input type="text" id="profile-nombre" name="nombre_completo" placeholder="Tu nombre y apellido" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="profile-telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
                                <input type="tel" id="profile-telefono" name="telefono" placeholder="+57 3001234567" class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="profile-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico</label>
                                <input type="email" id="profile-email" name="email" placeholder="tu@correo.com" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="profile-direccion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección</label>
                                <input type="text" id="profile-direccion" name="direccion" placeholder="Tu dirección de residencia" class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-md transition duration-200">
                                Actualizar Datos
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Sección Gestión de Citas -->
            <section id="appointments" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Tus Citas Programadas</h2>
                <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                    <ul id="appointments-list" class="space-y-4">
                        <li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando citas...</li>
                    </ul>
                    <div class="mt-6 border-t pt-4 dark:border-gray-700 flex flex-wrap gap-4">
                        <a href="#schedule" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                           <i class="bi bi-calendar-plus mr-1"></i> Agendar Nueva Cita
                        </a>
                    </div>
                </div>
            </section>

            <!-- Sección Agendar Cita -->
            <section id="schedule" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Agendar Nueva Cita</h2>
                <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                    <form id="schedule-appointment-form">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                            <div>
                                <label for="schedule-medico" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Médico</label>
                                <select id="schedule-medico" name="idMedico" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="" disabled selected>Seleccione un médico...</option>
                                </select>
                            </div>
                            <div>
                                <label for="schedule-fecha" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                                <input type="date" id="schedule-fecha" name="fecha" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500" min="<?php echo date('Y-m-d'); // Previene fechas pasadas ?>">
                            </div>
                            <div>
                                <label for="schedule-hora" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hora</label>
                                <input type="time" id="schedule-hora" name="hora" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="schedule-motivo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo de la consulta (Opcional)</label>
                            <textarea id="schedule-motivo" name="motivo" rows="3" placeholder="Describa brevemente el motivo..." class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded-md transition duration-200">
                            Confirmar Cita
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Sección Historial Médico -->
            <section id="medical-history" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Tu Historial Médico</h2>
                <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-100">Diagnósticos y Tratamientos Anteriores</h3>
                    <ul id="history-list" class="space-y-3">
                        <li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando historial...</li>
                    </ul>
                    <div class="mt-6 border-t pt-4 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">La descarga de documentos estará disponible próximamente.</p>
                    </div>
                </div>
            </section>

            <!-- Sección Consultas en Línea -->
            <section id="consultations" class="scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Consultas en Línea</h2>
                <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Próximamente podrás realizar consultas virtuales y subir documentos.</p>
                    <button class="bg-blue-500 text-white py-2 px-4 rounded-md opacity-50 cursor-not-allowed">Iniciar Consulta</button>
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-100">Subida de documentos médicos</h3>
                        <input type="file" class="border rounded-md p-2 w-full dark:bg-gray-700 opacity-50 cursor-not-allowed" disabled>
                    </div>
                </div>
            </section>

        </div> <!-- Cierre del div container -->
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 py-6 mt-auto border-t dark:border-gray-700 border-gray-200">
        <div class="container mx-auto text-center text-sm text-gray-600 dark:text-gray-400">
            © <?php /* Puedes quitar el tag php si no lo necesitas aqui */ date("Y"); ?> MediAgenda. Todos los derechos reservados.
        </div>
    </footer>

    {/* Scripts */}
    <script src="scripts.js"></script>

</body>
</html> 