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
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Panel de Doctores</title>

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
         #appointments-list-doctor li.placeholder,
         #patients-list-doctor li.placeholder {
            color: #9ca3af; /* gray-400 */
            font-style: italic;
         }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-gray-100">

    <!-- Header -->
    <header class="bg-white bg-opacity-90 shadow-md sticky top-0 z-50 dark:bg-gray-800 dark:bg-opacity-90 backdrop-blur-sm">
        <div class="container mx-auto flex justify-between items-center py-4 px-6">
            <div class="flex items-center gap-2">
                 <!-- CORREGIDO: Enlace a index.php -->
                <a href="index.php">
                    <img src="logo.png" alt="MediAgenda Logo" class="w-10 h-10">
                </a>
                <span class="text-xl font-bold text-blue-600 dark:text-blue-300">MediAgenda - Panel de Doctores</span>
            </div>
            <nav class="flex items-center gap-4">
                <!-- Navegación Panel Doctor -->
                 <ul id="nav-links-desktop" class="hidden lg:flex items-center gap-4 md:gap-6">
                    <li><a href="#profile" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Perfil</a></li>
                    <li><a href="#appointments" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Citas</a></li>
                    <li><a href="#patients" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Pacientes Hoy</a></li>
                    <li><a href="#consultations" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Consulta Online</a></li>
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
                <!-- Menú Hamburguesa (si aplica) -->
                <!-- <div id="hamburger-menu" class="lg:hidden ..."> ... </div> -->
            </nav>
            <!-- Menú Móvil Desplegable -->
            <!-- <div id="mobile-menu" class="lg:hidden hidden ..."> ... </div> -->
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto py-10 px-6">

        <!-- Sección Perfil del Doctor -->
        <section id="profile" class="mb-12 scroll-mt-20">
            <h2 class="text-2xl font-bold mb-4">Tu Perfil Profesional</h2>
            <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                <h3 class="text-lg font-semibold mb-4">Actualiza tu información</h3>
                 <!-- CORREGIDO: Añadido ID al form si JS lo necesita, y atributos name -->
                <form id="doctor-profile-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                         <div>
                            <label for="doc-profile-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre completo</label>
                            <input type="text" id="doc-profile-nombre" name="nombre_completo" placeholder="Tu nombre" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="doc-profile-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico</label>
                            <input type="email" id="doc-profile-email" name="email" placeholder="tu@correo.com" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                         <div>
                            <label for="doc-profile-especialidad" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Especialidad</label>
                            <input type="text" id="doc-profile-especialidad" name="especialidad" placeholder="Ej: Cardiología" class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="doc-profile-horario" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Horario (Texto descriptivo)</label>
                            <!-- CORREGIDO: Cambiado a textarea para horario flexible, añadido name -->
                            <textarea id="doc-profile-horario" name="horario" rows="3" placeholder="Ej: Lunes y Miércoles 9am-1pm, Viernes 2pm-5pm" class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                     <!-- Añadir campos para contraseña si se permite cambiar -->
                    <div class="text-right mt-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-md transition duration-200">
                            Actualizar Perfil
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Sección Gestión de Citas -->
        <section id="appointments" class="mb-12 scroll-mt-20">
            <h2 class="text-2xl font-bold mb-4">Tus Citas Agendadas</h2>
            <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                <h3 class="text-lg font-semibold mb-2">Próximas citas</h3>
                 <!-- CORREGIDO: Lista para ser llenada por JS (cargarCitasMedico) -->
                <ul id="appointments-list-doctor" class="space-y-4">
                     <li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando citas...</li>
                     <!-- Las citas se cargarán aquí -->
                     <!-- Ejemplo dinámico que JS podría generar:
                     <li data-cita-id="45" class="border-b pb-3 dark:border-gray-700">
                        <div><strong>Paciente:</strong> Juan Pérez (Tel: ...)</div>
                        <div><strong>Fecha/Hora:</strong> 2024-05-15 10:00</div>
                        <div><strong>Motivo:</strong> Revisión anual</div>
                        <div><strong>Estado:</strong> Programada</div>
                        <div class="mt-2 flex gap-2">
                           <button onclick="cambiarEstadoCita(45, 'Confirmada')" class="bg-green-500 text-white px-2 py-1 text-xs rounded">Confirmar</button>
                           <button onclick="cambiarEstadoCita(45, 'Cancelada Doctor')" class="bg-red-500 text-white px-2 py-1 text-xs rounded">Cancelar</button>
                           <button onclick="abrirModalReprogramar(45)" class="bg-yellow-500 text-white px-2 py-1 text-xs rounded">Reprogramar</button>
                        </div>
                     </li>
                     -->
                </ul>
            </div>
        </section>

        <!-- Sección Atención a Pacientes (Simplificada) -->
        <section id="patients" class="mb-12 scroll-mt-20">
            <h2 class="text-2xl font-bold mb-4">Pacientes del Día</h2>
             <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                 <h3 class="text-lg font-semibold mb-2">Lista de pacientes agendados para hoy</h3>
                 <!-- CORREGIDO: Lista para ser llenada por JS (necesitaría 'obtener_pacientes_hoy.php') -->
                 <ul id="patients-list-doctor" class="space-y-3">
                    <li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando pacientes de hoy...</li>
                     <!-- Ejemplo:
                     <li><strong>10:00 AM:</strong> Juan Pérez - <a href="#historial-42" class="text-blue-500 hover:underline">Ver Historial</a></li>
                     -->
                 </ul>
                 <div class="mt-6 border-t pt-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-2">Registrar Notas de Consulta</h3>
                     <!-- CORREGIDO: Añadido name -->
                     <form id="consulta-notes-form">
                         <!-- Podrías añadir un select para elegir el paciente de la cita actual -->
                         <!-- <select name="idCitaActual" required>...</select> -->
                        <textarea name="diagnostico_tratamiento" rows="4" class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500" placeholder="Escribir diagnóstico, tratamiento, notas para la cita seleccionada..."></textarea>
                        <div class="text-right mt-2">
                             <!-- Necesita script PHP 'guardar_notas_consulta.php' -->
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">Guardar Notas</button>
                        </div>
                     </form>
                </div>
             </div>
        </section>

        <!-- Sección Consultas en Línea (Placeholder) -->
        <section id="consultations" class="scroll-mt-20">
            <h2 class="text-2xl font-bold mb-4">Consultas en Línea</h2>
            <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                <p class="text-gray-600 dark:text-gray-300 mb-4">Funcionalidad de consulta virtual y chat próximamente.</p>
                <button class="bg-blue-500 text-white py-2 px-4 rounded-md opacity-50 cursor-not-allowed">Iniciar Consulta Virtual</button>
                 <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-2">Notas rápidas durante consulta virtual</h3>
                     <!-- CORREGIDO: Añadido name -->
                    <textarea name="notas_consulta_online" rows="3" class="border rounded-md p-2 w-full dark:bg-gray-700 opacity-50 cursor-not-allowed" placeholder="Escribir notas médicas..." disabled></textarea>
                    <button class="mt-2 bg-blue-500 text-white py-2 px-4 rounded-md opacity-50 cursor-not-allowed" disabled>Guardar</button>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
     <footer class="bg-gray-100 dark:bg-gray-800 py-6 mt-10 border-t dark:border-gray-700">
        <div class="container mx-auto text-center text-sm text-gray-600 dark:text-gray-400">
             © <?php echo date("Y"); ?> MediAgenda. Todos los derechos reservados.
        </div>
    </footer>

 <!-- Área para mostrar notificaciones -->
 <div id="notification-area" class="fixed top-5 right-5 z-[100] space-y-2 w-full max-w-xs sm:max-w-sm"></div>

 <!-- Enlace al archivo JavaScript -->
 <script src="scripts.js"></script>

</body>
</html>