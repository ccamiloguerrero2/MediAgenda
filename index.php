<?php
session_start();
$loggedIn = isset($_SESSION['idUsuario']) && !empty($_SESSION['idUsuario']);
$nombreUsuario = $loggedIn ? ($_SESSION['nombreUsuario'] ?? 'Usuario') : '';
// Obtener el rol del usuario si está logueado, en minúsculas para comparación
$rolUsuario = $loggedIn ? strtolower($_SESSION['rolUsuario'] ?? '') : '';

// Determinar el enlace del panel correcto
$panelLink = 'index.php'; // Fallback
if ($loggedIn) {
    switch ($rolUsuario) {
        case 'paciente':
            $panelLink = 'perfil-usuario.php';
            break;
        case 'medico':
            $panelLink = 'perfil-doctores.php';
            break;
        case 'admin':
            $panelLink = 'panel-admin-sistema.php';
            break;
            // No hay caso para recepcionista
    }
}

// Determinar el enlace para "Agendar Cita"
$agendarCitaLink = 'registro.php'; // Por defecto, llevar al registro si no está logueado
if ($loggedIn) {
    if ($rolUsuario === 'paciente') {
        $agendarCitaLink = 'perfil-usuario.php'; // Paciente va a su panel para agendar
    } else {
        // Médico o Admin van a su propio panel (aunque el botón dice "Agendar Cita")
        $agendarCitaLink = $panelLink;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>MediAgenda</title>

    <!-- Enlaces a bibliotecas externas: Tailwind, Bootstrap Icons y Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Añadir CSS de SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Google Fonts (Roboto y Montserrat) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        /* Aplicar fuentes base */
        body {
            font-family: 'Roboto', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Montserrat', sans-serif;
        }

        /* Estilos para el efecto Parallax en distintas secciones */
        .parallax {
            /* Gradiente claro + imagen original */
            background-image: linear-gradient(to right, rgba(255, 255, 255, 0.9) 40%, rgba(255, 255, 255, 0.7) 60%, rgba(255, 255, 255, 0.3) 80%, rgba(255, 255, 255, 0) 100%), url('/img/FondoMedico1.jpg');
            height: 100vh;
            /* background-attachment: fixed; */
            /* Eliminado para quitar parallax */
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
        }

        /* Estilo para modo oscuro */
        .dark .parallax {
            background-image: linear-gradient(to right, rgba(17, 24, 39, 0.9) 30%, rgba(31, 41, 55, 0.8) 50%, rgba(55, 65, 81, 0.6) 70%, rgba(55, 65, 81, 0) 100%), url('/img/Fondoini1.jpeg');
            /* No necesita background-attachment aquí si se quita en .parallax base */
        }

        .parallax-doctors {
            height: 100vh;
            /* background-attachment: fixed; */
            /* Eliminado para quitar parallax */
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
        }

        .parallax-testimonials {
            height: 100vh;
            /* background-attachment: fixed; */
            /* Eliminado para quitar parallax */
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
        }
    </style>
</head>

<body class="antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white">

    <!-- Encabezado con logo, navegación y modo oscuro -->
    <header class="bg-white shadow-md sticky top-0 z-50 dark:bg-gray-800">
        <div class="container mx-auto flex justify-between items-center py-3 px-4">

            <!-- Logo de MediAgenda -->
            <div class="flex items-center gap-2">
                <a href="index.php">
                    <img src="img/logo.png" alt="MediAgenda Logo" class="w-10 h-10">
                </a>
                <span class="text-xl font-bold text-blue-600 dark:text-blue-300">MediAgenda</span>
            </div>

            <!-- Menú de navegación de ESCRITORIO con submenús -->
            <nav>
                <ul id="nav-links" class="hidden lg:flex space-x-8 items-center">
                    <?php /* <li><a href="index.php"
                            class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md">Inicio</a>
                    </li> */ ?>

                    <!-- Submenú Usuarios - Visible solo si está logueado -->
                    <?php if ($loggedIn): ?>
                        <li class="relative group">
                            <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md">
                                Panel
                            </a>
                            <ul class="absolute left-0 hidden group-hover:flex flex-col bg-white dark:bg-gray-800 shadow-lg rounded-lg mt-2 transition-all duration-700 ease-in-out min-w-max">
                                <!-- Mantenemos solo el enlace genérico 'Ir a mi Panel' -->
                                <li><a href="<?php echo $panelLink; ?>" class="block px-4 py-2 hover:bg-blue-600 hover:text-white dark:text-gray-300 font-semibold">Ir a mi Panel</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Submenú Noticias y Blog -->
                    <li>
                        <a href="blog.html"
                            class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md">
                            Blog
                        </a>
                    </li>

                    <!-- Submenú Ayuda -->
                    <li class="relative group">
                        <a href="#"
                            class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md">
                            Ayuda
                        </a>
                        <ul
                            class="absolute left-0 hidden group-hover:flex flex-col bg-white dark:bg-gray-800 shadow-lg rounded-lg mt-2 transition-all duration-700 ease-in-out">
                            <li><a href="politicas.html"
                                    class="block px-4 py-2 hover:bg-blue-600 hover:text-white dark:text-gray-300">Políticas</a>
                            </li>
                            <li><a href="contacto.html"
                                    class="block px-4 py-2 hover:bg-blue-600 hover:text-white dark:text-gray-300">Contacto</a>
                            </li>
                        </ul>
                    </li>

                    <!-- Condicionalmente mostrar "Registro / Iniciar Sesión" o "Cerrar Sesión" -->
                    <?php if (!$loggedIn): ?>
                        <li><a href="registro.php" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md transition">Registro / Iniciar Sesión</a></li>
                    <?php else: ?>
                        <li class="text-gray-700 dark:text-gray-300 px-4 py-2">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></li>
                        <!-- Estilo de Cerrar Sesión actualizado -->
                        <li><a href="mediagenda-backend/logout.php"
                                class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-500 font-medium px-3 py-2 rounded-md hover:bg-red-50 dark:hover:bg-gray-700 transition text-sm">
                                <i class="bi bi-box-arrow-right mr-1"></i>Cerrar Sesión
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Botón de agendar cita (oculto para admin) -->
                    <?php if (!$loggedIn || $rolUsuario !== 'admin'): ?>
                        <li><a href="<?php echo $agendarCitaLink; ?>"
                                class="text-white font-bold bg-blue-700 hover:bg-white hover:text-blue-700 hover:border-blue-700 border-2 px-6 py-2 rounded-md">Agendar
                                Cita</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- Contenedor para botones derechos (modo oscuro y hamburguesa) -->
            <div class="flex items-center gap-4">
                <button id="dark-mode-toggle" class="cta-button text-blue-600">
                    <i class="fas fa-moon text-2xl"></i>
                </button>
                <div class="hamburger-menu lg:hidden flex flex-col gap-1 cursor-pointer" id="hamburger-menu">
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                </div>
            </div>

        </div>
    </header>

    <!-- Contenedor del MENÚ MÓVIL -->
    <div id="mobile-menu" class="hidden lg:hidden bg-white dark:bg-gray-800 shadow-lg py-4">
        <ul class="flex flex-col items-center gap-4">
            <li><a href="index.php" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Inicio</a></li>
            <!-- Quitar enlaces directos a paneles específicos, se usa 'Mi Panel' condicional -->
            <!-- <li><a href="perfil-usuario.php" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Panel Pacientes</a></li> -->
            <!-- <li><a href="perfil-doctores.php" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Panel Doctores</a></li> -->
            <li><a href="blog.html" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Blog</a></li>
            <li><a href="contacto.html" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Contacto</a></li>
            <!-- Actualizar enlace Agendar Cita Móvil (oculto para admin) -->
            <?php if (!$loggedIn || $rolUsuario !== 'admin'): ?>
                <li><a href="<?php echo $agendarCitaLink; ?>" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Agendar Cita</a></li>
            <?php endif; ?>

            <hr class="w-1/2 border-gray-300 dark:border-gray-600 my-2">

            <?php if (!$loggedIn): ?>
                <li><a href="registro.php" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Registro / Iniciar Sesión</a></li>
            <?php else: ?>
                <li class="text-gray-700 dark:text-gray-300 px-4 py-2">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></li>
                <!-- Añadir enlace a 'Mi Panel' que apunta al lugar correcto -->
                <li><a href="<?php echo $panelLink; ?>" class="block text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 px-4 py-2 font-medium">Mi Panel</a></li>
                <!-- Estilo de Cerrar Sesión actualizado (Móvil) -->
                <li><a href="mediagenda-backend/logout.php" class="block text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 px-4 py-2">
                        <i class="bi bi-box-arrow-right mr-1"></i>Cerrar Sesión
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Sección Hero con efecto Parallax -->
    <section class="parallax">
        <div class="absolute inset-0 bg-gray-800 bg-opacity-30 dark:bg-opacity-50"></div>
        <div class="relative z-10 flex flex-col justify-center h-full px-6 sm:px-10 md:px-16 lg:px-24 py-10 text-white">
            <div class="max-w-2xl">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">Programe su cita médica con facilidad</h1>
                <p class="text-xl md:text-2xl mt-4 text-gray-700 dark:text-gray-300">Programación rápida, cómoda y segura de sus citas médicas.</p>
                <a href="./registro.php" class="mt-8 inline-block bg-blue-600 text-white py-3 px-6 rounded-lg shadow-lg hover:bg-blue-700 transition">Registrarse / Iniciar Sesión</a>
            </div>
        </div>
    </section>

    <!-- Mensaje de Error para acceso no autorizado -->
    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized_role'): ?>
        <div id="error-message" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-auto my-4 max-w-4xl">
            <div class="flex items-center">
                <div class="py-1"><i class="fas fa-exclamation-circle text-red-500 mr-3"></i></div>
                <div>
                    <p class="font-bold">Acceso no autorizado</p>
                    <p>No tienes permiso para acceder al panel solicitado. Por favor, usa el panel asignado a tu rol de usuario.</p>
                </div>
            </div>
        </div>
        <script>
            // Auto-ocultar el mensaje después de 5 segundos
            setTimeout(() => {
                const errorMsg = document.getElementById('error-message');
                if (errorMsg) {
                    errorMsg.style.opacity = '0';
                    errorMsg.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => errorMsg.remove(), 500);
                }
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Sección de Características del Sistema -->
    <section class="bg-gray-100 py-20 text-center dark:bg-gray-800">
        <h2 class="text-3xl font-semibold mb-4 dark:text-white">Cómo funciona MediAgenda</h2>
        <p class="text-gray-600 mb-12 dark:text-gray-300">MediAgenda simplifica el proceso de gestión de tus citas médicas.</p>

        <!-- Paneles con los pasos principales -->
        <div class="flex flex-wrap justify-around max-w-6xl mx-auto gap-8">
            <a href="./registro.php">
                <div
                    class="bg-white shadow-lg rounded-lg p-6 max-w-xs transition-transform hover:scale-105 dark:bg-gray-700">
                    <h3 class="text-xl font-semibold mb-2 dark:text-white"><i class="bi bi-person-plus"></i> Registrarse o
                        Iniciar Sesión</h3>
                    <p class="text-gray-600 dark:text-gray-300">Cree su cuenta o acceda a su perfil para gestionar sus
                        citas.</p>
                </div>
            </a>

            <div
                class="bg-white shadow-lg rounded-lg p-6 max-w-xs transition-transform hover:scale-105 dark:bg-gray-700">
                <h3 class="text-xl font-semibold mb-2 dark:text-white"><i class="bi bi-calendar"></i> Programar Cita</h3>
                <p class="text-gray-600 dark:text-gray-300">Consulte las horas disponibles y reserve su cita.</p>
            </div>
            <div
                class="bg-white shadow-lg rounded-lg p-6 max-w-xs transition-transform hover:scale-105 dark:bg-gray-700">
                <h3 class="text-xl font-semibold mb-2 dark:text-white"><i class="bi bi-gear"></i> Administrar Citas</h3>
                <p class="text-gray-600 dark:text-gray-300">Consulte, modifique o cancele sus citas a través de su
                    panel.</p>
            </div>
            <div
                class="bg-white shadow-lg rounded-lg p-6 max-w-xs transition-transform hover:scale-105 dark:bg-gray-700">
                <h3 class="text-xl font-semibold mb-2 dark:text-white"><i class="bi bi-bell"></i> Notificaciones</h3>
                <p class="text-gray-600 dark:text-gray-300">Reciba notificaciones automáticas antes de cada cita.</p>
            </div>
        </div>
    </section>

    <!-- Sección de Servicios -->
    <section id="services" class="bg-gray-100 py-20 dark:bg-gray-800">
        <h2 class="text-3xl font-semibold mb-4 text-center dark:text-white">Nuestros Servicios</h2>
        <p class="text-gray-600 mb-12 text-center dark:text-gray-300">Ofrecemos una amplia variedad de servicios médicos.</p>
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white dark:bg-gray-700 p-6 rounded-lg shadow-lg text-center">
                <h3 class="text-xl font-semibold dark:text-white">Consulta General</h3>
                <p class="text-gray-600 dark:text-gray-300">Desde $50,000</p>
            </div>
            <div class="bg-white dark:bg-gray-700 p-6 rounded-lg shadow-lg text-center">
                <h3 class="text-xl font-semibold dark:text-white">Cardiología</h3>
                <p class="text-gray-600 dark:text-gray-300">Desde $120,000</p>
            </div>
            <div class="bg-white dark:bg-gray-700 p-6 rounded-lg shadow-lg text-center">
                <h3 class="text-xl font-semibold dark:text-white">Dermatología</h3>
                <p class="text-gray-600 dark:text-gray-300">Desde $90,000</p>
            </div>
        </div>
    </section>

    <!-- Sección de Doctores -->
    <section id="doctors" class="parallax-doctors bg-white py-20 dark:bg-gray-800">
        <div class="relative container mx-auto">
            <h2 class="text-3xl font-semibold mb-4 text-center text-white dark:text-white">Nuestros Doctores</h2>
            <p class="mb-12 text-center text-white dark:text-gray-300">Conozca a nuestros doctores.</p>
            <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-lg shadow-lg text-center">
                    <h3 class="text-xl font-semibold dark:text-white">Dr. Juan Pérez</h3>
                    <p class="text-gray-600 dark:text-gray-300">Cardiólogo</p>
                    <p class="text-gray-600 dark:text-gray-300">Horario: Lunes a Viernes, 9:00 AM - 4:00 PM</p>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-lg shadow-lg text-center">
                    <h3 class="text-xl font-semibold dark:text-white">Dra. Ana Martínez</h3>
                    <p class="text-gray-600 dark:text-gray-300">Dermatóloga</p>
                    <p class="text-gray-600 dark:text-gray-300">Horario: Lunes a Jueves, 10:00 AM - 3:00 PM</p>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-lg shadow-lg text-center">
                    <h3 class="text-xl font-semibold dark:text-white">Dr. Carlos López</h3>
                    <p class="text-gray-600 dark:text-gray-300">Pediatra</p>
                    <p class="text-gray-600 dark:text-gray-300">Horario: Martes a Viernes, 11:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Testimonios -->
    <section id="testimonials" class="parallax-testimonials bg-white py-20 dark:bg-gray-800">
        <div class="relative container mx-auto">
            <h2 class="text-3xl font-semibold mb-4 text-center text-white dark:text-white">Testimonios de Usuarios</h2>
            <p class="mb-12 text-center text-white dark:text-gray-300">Escucha lo que nuestros usuarios dicen sobre
                MediAgenda.</p>
            <div class="container mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-lg shadow-lg">
                    <p class="text-lg italic dark:text-white">"MediAgenda me ha permitido agendar mis citas médicas de manera
                        rápida y sin complicaciones."</p>
                    <p class="mt-4 text-gray-600 dark:text-gray-300">- Laura Gómez</p>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-lg shadow-lg">
                    <p class="text-lg italic dark:text-white">"Los doctores son muy profesionales y el sistema de notificaciones
                        me ayudó a no perder ninguna cita."</p>
                    <p class="mt-4 text-gray-600 dark:text-gray-300">- Pedro Martínez</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pie de Página Mejorado -->
    <footer class="bg-gray-900 text-gray-300 dark:bg-gray-800">
        <div class="container mx-auto px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Columna 1: Logo y Descripción -->
                <div class="md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="img/logo.png" alt="MediAgenda Logo" class="w-8 h-8">
                        <span class="text-xl font-bold text-white">MediAgenda</span>
                    </div>
                    <p class="text-sm text-gray-400">Facilitando el acceso a la salud.</p>
                </div>

                <!-- Columna 2: Enlaces Rápidos -->
                <div>
                    <h3 class="text-base font-semibold text-white mb-4 uppercase tracking-wider">Navegación</h3>
                    <ul class="space-y-2">
                        <li><a href="#about" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Acerca de</a></li>
                        <li><a href="#services" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Servicios</a></li>
                        <li><a href="blog.html" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Blog</a></li>
                        <li><a href="registro.php" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Acceso / Registro</a></li>
                    </ul>
                </div>

                <!-- Columna 3: Legal y Soporte -->
                <div>
                    <h3 class="text-base font-semibold text-white mb-4 uppercase tracking-wider">Soporte</h3>
                    <ul class="space-y-2">
                        <li><a href="contacto.html" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Contacto</a></li>
                        <li><a href="politicas.html" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Políticas de Privacidad</a></li>
                        <li><a href="#terms" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Términos de Servicio</a></li>
                        <li><a href="#faq" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Preguntas Frecuentes</a></li>
                    </ul>
                </div>

                <!-- Columna 4: Redes Sociales y Contacto -->
                <div>
                    <h3 class="text-base font-semibold text-white mb-4 uppercase tracking-wider">Síguenos</h3>
                    <div class="flex space-x-4 mb-6">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300"><i class="fab fa-linkedin-in fa-lg"></i></a>
                    </div>
                    <h3 class="text-base font-semibold text-white mb-4 uppercase tracking-wider">Contacto Directo</h3>
                    <p class="text-sm text-gray-400">Email: info@mediagenda.com</p>
                    <p class="text-sm text-gray-400">Teléfono: 315 2885138</p>
                </div>
            </div>
        </div>
        <!-- Línea de Copyright -->
        <div class="border-t border-gray-700 mt-8 py-6">
            <div class="container mx-auto px-6 text-center text-sm text-gray-500">
                © <?php echo date("Y"); ?> MediAgenda. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    <!-- Enlace al archivo de JavaScript para manejar las interacciones -->
    <!-- Añadir SweetAlert2 ANTES de scripts.js -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="scripts.js"></script>

    <!-- Área para mostrar notificaciones -->
    <div id="notification-area" class="fixed top-5 right-5 z-[100] space-y-2 w-full max-w-xs sm:max-w-sm"></div>

</body>

</html>