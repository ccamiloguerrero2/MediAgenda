<?php
/**
 * Página Principal (Landing Page) de MediAgenda
 *
 * Muestra la información introductoria, características principales,
 * servicios, doctores destacados y testimonios.
 * Incluye enlaces para registro/login o acceso al panel si el usuario
 * ya ha iniciado sesión.
 *
 * @package MediAgenda\Public
 */

// --- Dependencias Core ---
require_once __DIR__ . '/../app/Core/session_utils.php'; // Carga sesión y define $loggedIn, $rolUsuario, etc.

?>
<!DOCTYPE html>
<html lang="es" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : ''; // Aplica modo oscuro inicial ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MediAgenda - Gestión de Citas Médicas Online</title>

    <!-- --- CSS --- -->
    <link rel="stylesheet" href="/dist/output.css"> <!-- Tailwind Compilado -->
    <!-- Iconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 (para notificaciones, ej. logout exitoso) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- --- Estilos Específicos (o mover a input.css/@layer) --- -->
    <style>
        .logo-pacifico { font-family: 'Pacifico', cursive; }
        /* Aplicar fuentes base (si no se define en tailwind.config.js) */
        body { font-family: 'Roboto', sans-serif; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Montserrat', sans-serif; }

        /* --- Efecto Parallax --- */
        /* Define una variable CSS para la imagen de fondo base */
        :root {
          --parallax-bg-image: linear-gradient(to right, rgb(255, 255, 255) 40%, rgba(255, 255, 255, 0.7) 60%, rgba(255, 255, 255, 0.3) 80%, rgba(255, 255, 255, 0) 100%), url('/img/FondoMedico1.jpg');
        }
        /* Define la variable para el modo oscuro */
        html.dark:root {
          --parallax-bg-image: linear-gradient(to right, rgba(17, 24, 39, 0.9) 30%, rgba(31, 41, 55, 0.8) 50%, rgba(55, 65, 81, 0.6) 70%, rgba(55, 65, 81, 0) 100%), url('/img/Fondoini1.jpeg'); /* Fondo oscuro */
        }

        .parallax-section {
            /* Usa la variable CSS */
            background-image: var(--parallax-bg-image);
            /* Propiedades comunes del parallax */
            min-height: 60vh; /* Ajustar altura según necesidad */
            background-attachment: fixed; /* Efecto parallax */
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative; /* Necesario para el overlay */
            color: #fff; /* Color de texto por defecto para parallax */
            display: flex;
            align-items: center; /* Centrar contenido verticalmente */
        }
        /* Overlay opcional para mejorar legibilidad del texto */
        .parallax-section::before {
             content: '';
             position: absolute;
             top: 0; left: 0; right: 0; bottom: 0;
             /* Ajusta la opacidad y color del overlay si es necesario */
             /* background-color: rgba(0, 0, 0, 0.1); */
             z-index: 1;
        }
        /* Contenido del parallax debe estar sobre el overlay */
        .parallax-content {
            position: relative;
            z-index: 2;
        }
        /* Estilo específico para el texto dentro del parallax en modo claro */
        .parallax-section .parallax-content { color: #1f2937; /* gray-800 */ }
        /* Estilo específico para el texto dentro del parallax en modo oscuro */
        .dark .parallax-section .parallax-content { color: #f9fafb; /* gray-50 */ }


        /* --- Animación Icono Hamburguesa --- */
        #hamburger-menu span { transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out; }
        #hamburger-menu.open span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        #hamburger-menu.open span:nth-child(2) { opacity: 0; }
        #hamburger-menu.open span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-white"> <!-- Fondo base blanco para modo claro -->

    <!-- Incluir Cabecera -->
    <?php require_once __DIR__ . '/../app/Views/Layout/header.php'; ?>

    <!-- --- Sección Hero (Portada con Parallax) --- -->
    <section class="parallax-section">
        <!-- Overlay y contenido relativo para z-index -->
        <div class="container mx-auto px-6 sm:px-10 md:px-16 lg:px-24 py-16 parallax-content">
            <div class="max-w-2xl">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Programe su cita médica con facilidad</h1>
                <p class="text-xl md:text-2xl mt-4 opacity-90"> <!-- Ligera opacidad para integrarse mejor -->
                    Agendamiento rápido, cómodo y seguro de sus consultas médicas.
                </p>
                <!-- El botón de acción depende si el usuario está logueado o no -->
                <?php if ($loggedIn): ?>
                    <?php // Si está logueado, pero es admin, no mostramos "Agendar Cita"
                          if ($rolUsuario !== 'admin'): ?>
                        <a href="<?php echo htmlspecialchars($agendarCitaLink); // $agendarCitaLink va al panel o a agendar directamente ?>"
                           class="mt-8 inline-block bg-blue-600 text-white py-3 px-8 rounded-lg shadow-lg hover:bg-blue-700 transition duration-200 text-lg font-semibold">
                           <i class="bi bi-calendar-plus mr-2"></i>Agendar Cita
                        </a>
                    <?php else: // Si es admin, mostrar enlace a su panel ?>
                         <a href="<?php echo htmlspecialchars($panelLink); ?>"
                           class="mt-8 inline-block bg-purple-600 text-white py-3 px-8 rounded-lg shadow-lg hover:bg-purple-700 transition duration-200 text-lg font-semibold">
                           <i class="bi bi-shield-lock mr-2"></i>Ir al Panel Admin
                        </a>
                    <?php endif; ?>
                <?php else: // Si no está logueado, enlace a Registro/Login ?>
                     <a href="/registro.php"
                       class="mt-8 inline-block bg-green-600 text-white py-3 px-8 rounded-lg shadow-lg hover:bg-green-700 transition duration-200 text-lg font-semibold">
                       <i class="bi bi-person-plus mr-2"></i>Registrarse / Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- --- Mensaje de Error (si aplica, ej. acceso no autorizado a otro panel) --- -->
    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized_role'): ?>
        <div id="error-message" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-auto my-4 max-w-4xl rounded shadow-md" role="alert">
            <div class="flex items-center">
                <div class="py-1"><i class="fas fa-exclamation-circle text-red-500 mr-3 text-xl"></i></div>
                <div>
                    <p class="font-bold">Acceso no autorizado</p>
                    <p>No tienes permiso para acceder al panel solicitado. Por favor, usa el panel asignado a tu rol.</p>
                </div>
                <button onclick="document.getElementById('error-message').style.display='none'" class="ml-auto -mx-1.5 -my-1.5 bg-red-100 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8" aria-label="Cerrar">
                    <span class="sr-only">Cerrar</span>
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
              </button>
            </div>
        </div>
        <!-- Script para auto-ocultar opcional (ya incluido en scripts.js si se detecta ?logout=success) -->
        <!-- Podrías añadir lógica similar en scripts.js para ?error=unauthorized_role si lo deseas -->
    <?php endif; ?>

    <!-- --- Sección: Cómo Funciona --- -->
    <section id="how-it-works" class="bg-gray-50 py-16 md:py-20 text-center dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-semibold mb-4 dark:text-white">¿Cómo funciona MediAgenda?</h2>
            <p class="text-gray-600 mb-12 dark:text-gray-300 max-w-3xl mx-auto">Simplificamos cada paso para que la gestión de tu salud sea más fácil y accesible.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 max-w-6xl mx-auto">
                <!-- Paso 1: Registro -->
                <a href="/registro.php" class="block group">
                    <div class="bg-white dark:bg-gray-700 shadow-lg rounded-lg p-6 h-full transition-transform transform group-hover:scale-105 duration-200 group-hover:shadow-xl">
                        <div class="text-blue-500 dark:text-blue-400 text-4xl mb-4"><i class="bi bi-person-plus-fill"></i></div>
                        <h3 class="text-xl font-semibold mb-2 dark:text-white">1. Regístrate o Accede</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Crea tu cuenta o inicia sesión para empezar a gestionar tus citas.</p>
                    </div>
                </a>
                <!-- Paso 2: Agendar -->
                 <a href="<?php echo htmlspecialchars($loggedIn && $rolUsuario !== 'admin' ? $agendarCitaLink : '/registro.php'); ?>" class="block group">
                    <div class="bg-white dark:bg-gray-700 shadow-lg rounded-lg p-6 h-full transition-transform transform group-hover:scale-105 duration-200 group-hover:shadow-xl">
                        <div class="text-green-500 dark:text-green-400 text-4xl mb-4"><i class="bi bi-calendar2-check-fill"></i></div>
                        <h3 class="text-xl font-semibold mb-2 dark:text-white">2. Agenda tu Cita</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Busca especialistas, consulta horarios disponibles y reserva tu cita fácilmente.</p>
                    </div>
                </a>
                <!-- Paso 3: Gestionar -->
                 <a href="<?php echo htmlspecialchars($loggedIn ? $panelLink : '/registro.php'); ?>" class="block group">
                    <div class="bg-white dark:bg-gray-700 shadow-lg rounded-lg p-6 h-full transition-transform transform group-hover:scale-105 duration-200 group-hover:shadow-xl">
                       <div class="text-purple-500 dark:text-purple-400 text-4xl mb-4"><i class="bi bi-pencil-square"></i></div>
                        <h3 class="text-xl font-semibold mb-2 dark:text-white">3. Gestiona tus Citas</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Consulta, modifica o cancela tus citas programadas desde tu panel personal.</p>
                    </div>
                </a>
                 <!-- Paso 4: Notificaciones (Concepto) -->
                <div class="bg-white dark:bg-gray-700 shadow-lg rounded-lg p-6 h-full transition-transform transform hover:scale-105 duration-200 hover:shadow-xl">
                    <div class="text-yellow-500 dark:text-yellow-400 text-4xl mb-4"><i class="bi bi-bell-fill"></i></div>
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">4. Recibe Recordatorios</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">Te enviaremos notificaciones para recordarte tus próximas citas.</p>
                    <span class="text-xs text-gray-400 dark:text-gray-500 mt-2 block">(Funcionalidad futura)</span>
                </div>
            </div>
        </div>
    </section>

    <!-- --- Sección: Servicios --- -->
    <section id="services" class="bg-white py-16 md:py-20 dark:bg-gray-900">
        <h2 class="text-3xl font-semibold mb-4 text-center dark:text-white">Nuestros Servicios</h2>
        <p class="text-gray-600 mb-12 text-center dark:text-gray-300 max-w-3xl mx-auto">Ofrecemos una variedad de especialidades médicas para cubrir tus necesidades.</p>
        <div class="container mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8 px-4">
            <!-- Servicio 1 -->
            <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg shadow-md text-center border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                <div class="text-3xl text-blue-500 dark:text-blue-400 mb-3"><i class="fas fa-stethoscope"></i></div>
                <h3 class="text-xl font-semibold dark:text-white mb-2">Consulta General</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Atención primaria y seguimiento de salud general.</p>
                <p class="text-gray-500 dark:text-gray-300 font-semibold mt-3">Desde $50,000 COP</p>
            </div>
             <!-- Servicio 2 -->
            <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg shadow-md text-center border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                 <div class="text-3xl text-red-500 dark:text-red-400 mb-3"><i class="fas fa-heartbeat"></i></div>
                <h3 class="text-xl font-semibold dark:text-white mb-2">Cardiología</h3>
                 <p class="text-gray-600 dark:text-gray-400 text-sm">Diagnóstico y tratamiento de enfermedades del corazón.</p>
                <p class="text-gray-500 dark:text-gray-300 font-semibold mt-3">Desde $120,000 COP</p>
            </div>
             <!-- Servicio 3 -->
            <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg shadow-md text-center border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
                 <div class="text-3xl text-teal-500 dark:text-teal-400 mb-3"><i class="fas fa-allergies"></i></div>
                <h3 class="text-xl font-semibold dark:text-white mb-2">Dermatología</h3>
                 <p class="text-gray-600 dark:text-gray-400 text-sm">Cuidado de la piel, diagnóstico y tratamiento de afecciones cutáneas.</p>
                <p class="text-gray-500 dark:text-gray-300 font-semibold mt-3">Desde $90,000 COP</p>
            </div>
            <!-- Añadir más servicios si es necesario -->
        </div>
         <div class="text-center mt-10">
             <a href="/registro.php" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                 Ver todas las especialidades y agendar <i class="bi bi-arrow-right ml-1"></i>
             </a>
        </div>
    </section>

    <!-- --- Sección: Doctores Destacados (Contenido Estático) --- -->
    <!-- Para una versión dinámica, esto se cargaría con JS desde obtener_medicos.php -->
    <section id="doctors" class="bg-gradient-to-b from-blue-50 to-white dark:from-gray-800 dark:to-gray-900 py-16 md:py-20">
        <div class="container mx-auto px-4">
             <h2 class="text-3xl font-semibold mb-4 text-center dark:text-white">Nuestros Doctores</h2>
             <p class="text-gray-600 mb-12 text-center dark:text-gray-300 max-w-3xl mx-auto">Conoce a algunos de nuestros profesionales dedicados a tu bienestar.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Doctor 1 -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden text-center transition-transform transform hover:-translate-y-2 duration-200">
                    <img src="https://readdy.ai/api/search-image?query=professional%20female%20doctor%20with%20glasses%2C%20mid%2030s%2C%20wearing%20white%20coat%2C%20friendly%20smile%2C%20neutral%20medical%20office%20background%2C%20professional%20headshot%2C%20high%20quality%20portrait&width=300&height=300&seq=123457&orientation=squarish" alt="Dr. Juan Pérez" class="w-full h-56 object-cover object-center">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold dark:text-white mb-1">Dr. Juan Pérez</h3>
                        <p class="text-blue-600 dark:text-blue-400 font-medium mb-3">Cardiólogo</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Lunes a Viernes, 9:00 AM - 4:00 PM</p>
                    </div>
                </div>
                 <!-- Doctor 2 -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden text-center transition-transform transform hover:-translate-y-2 duration-200">
                    <img src="https://readdy.ai/api/search-image?query=professional%20female%20doctor%20with%20glasses%2C%20mid%2030s%2C%20wearing%20white%20coat%2C%20friendly%20smile%2C%20neutral%20medical%20office%20background%2C%20professional%20headshot%2C%20high%20quality%20portrait&width=300&height=300&seq=123457&orientation=squarish" alt="Dra. Ana Martínez" class="w-full h-56 object-cover object-center">
                     <div class="p-6">
                        <h3 class="text-xl font-semibold dark:text-white mb-1">Dra. Ana Martínez</h3>
                        <p class="text-blue-600 dark:text-blue-400 font-medium mb-3">Dermatóloga</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Lunes a Jueves, 10:00 AM - 3:00 PM</p>
                    </div>
                </div>
                 <!-- Doctor 3 -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden text-center transition-transform transform hover:-translate-y-2 duration-200">
                    <img src="https://readdy.ai/api/search-image?query=professional%20female%20doctor%20with%20glasses%2C%20mid%2030s%2C%20wearing%20white%20coat%2C%20friendly%20smile%2C%20neutral%20medical%20office%20background%2C%20professional%20headshot%2C%20high%20quality%20portrait&width=300&height=300&seq=123457&orientation=squarish" alt="Dr. Carlos López" class="w-full h-56 object-cover object-center">
                     <div class="p-6">
                        <h3 class="text-xl font-semibold dark:text-white mb-1">Dr. Carlos López</h3>
                        <p class="text-blue-600 dark:text-blue-400 font-medium mb-3">Pediatra</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Martes a Viernes, 11:00 AM - 5:00 PM</p>
                    </div>
                </div>
            </div>
             <div class="text-center mt-12">
                <a href="/registro.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md transition duration-200">
                    Buscar y Agendar con un Especialista
                </a>
            </div>
        </div>
    </section>

    <!-- --- Sección: Testimonios (Contenido Estático) --- -->
    <section id="testimonials" class="bg-gray-100 py-16 md:py-20 dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-semibold mb-4 text-center dark:text-white">Testimonios de Usuarios</h2>
            <p class="text-gray-600 mb-12 text-center dark:text-gray-300 max-w-3xl mx-auto">Escucha lo que nuestros pacientes y doctores opinan sobre MediAgenda.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Testimonio 1 -->
                <div class="bg-white dark:bg-gray-700 p-6 rounded-lg shadow-lg flex flex-col">
                     <div class="text-yellow-400 text-xl mb-3">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="text-gray-700 dark:text-gray-200 italic mb-4 flex-grow">"¡Increíblemente fácil de usar! Agendar mis citas ahora toma segundos en lugar de minutos al teléfono. ¡Muy recomendado!"</p>
                    <p class="mt-auto font-semibold text-gray-800 dark:text-gray-100">- Laura Gómez <span class="text-sm text-gray-500 dark:text-gray-400">(Paciente)</span></p>
                </div>
                <!-- Testimonio 2 -->
                 <div class="bg-white dark:bg-gray-700 p-6 rounded-lg shadow-lg flex flex-col">
                      <div class="text-yellow-400 text-xl mb-3">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-700 dark:text-gray-200 italic mb-4 flex-grow">"Como médico, MediAgenda ha optimizado mi consulta. Mis pacientes pueden ver mi disponibilidad real y la gestión de citas es mucho más eficiente."</p>
                    <p class="mt-auto font-semibold text-gray-800 dark:text-gray-100">- Dr. Pedro Martínez <span class="text-sm text-gray-500 dark:text-gray-400">(Cardiólogo)</span></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Incluir Pie de Página -->
    <?php require_once __DIR__ . '/../app/Views/Layout/footer.php'; ?>

    <!-- --- JavaScript --- -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- No se necesita panel-admin.js aquí -->

</body>
</html>