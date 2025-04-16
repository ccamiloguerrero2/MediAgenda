<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['idUsuario']) || strtolower($_SESSION['rolUsuario'] ?? '') !== 'admin') {
    // Si no está autenticado o no es admin, redirigir a la página de inicio
    header('Location: index.php?error=unauthorized');
    exit();
}

// Obtener datos del usuario desde la sesión
$user_id = $_SESSION['idUsuario'];
$nombre_usuario = $_SESSION['nombreUsuario'] ?? 'Admin';
$rol_usuario = $_SESSION['rolUsuario'];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Administración del Sistema</title>

    <!-- Enlaces a Tailwind CSS y bibliotecas de íconos -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dist/output.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Añadir CSS de SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .logo-pacifico { font-family: 'Pacifico', cursive; }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white">

    <!-- Header con logo y navegación -->
    <header class="bg-white bg-opacity-80 shadow-md sticky top-0 z-50 dark:bg-gray-800">
        <div class="container mx-auto flex justify-between items-center py-3 px-6">
            <div class="flex items-center gap-2">
                <a href="index.php">
                    <img src="img/logo.png" alt="MediAgenda Logo" class="h-10 w-auto object-contain">
                </a>
                <span class="logo-pacifico text-blue-600 text-2xl">MediAgenda</span>
                <span class="border-l border-blue-400 h-8 mx-4"></span>
                <h1 class="text-xl font-bold text-blue-600 dark:text-blue-300 tracking-wide uppercase drop-shadow-sm" style="font-family: 'Montserrat', Arial, sans-serif;">Panel de Administración</h1>
            </div>
            <nav style="font-family: 'Roboto', 'Montserrat', Arial, sans-serif;">
                <!-- Navegación entre secciones del panel administrativo -->
                <ul id="nav-links" class="hidden lg:flex gap-6 items-center font-medium tracking-wide text-gray-700 dark:text-gray-200 transition-colors duration-200" style="font-family: 'Roboto', 'Montserrat', Arial, sans-serif;">
                    <li><a href="#users" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md transition">Usuarios</a></li>
                    <li><a href="#doctors" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md transition">Doctores</a></li>
                    <li><a href="#reports" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md transition">Reportes</a></li>
                    <li><a href="#settings" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md transition">Configuraciones</a></li>
                    <!-- Separador visual (opcional) -->
                    <li class="border-l border-gray-300 dark:border-gray-600 h-6"></li>
                    <!-- Saludo y Cerrar Sesión para Escritorio -->
                    <li class="text-gray-700 dark:text-gray-300">Hola, <?php echo htmlspecialchars($nombre_usuario); ?></li>
                    <li><a href="mediagenda-backend/logout.php"
                            class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-500 font-medium px-3 py-2 rounded-md hover:bg-red-50 dark:hover:bg-gray-700 transition text-sm">
                            <i class="bi bi-box-arrow-right mr-1"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- Botón para cambiar a modo oscuro -->
            <!-- Contenedor para botones derechos (modo oscuro y hamburguesa) -->
            <div class="flex items-center gap-4">
                <button id="dark-mode-toggle" class="text-blue-600 dark:text-yellow-400 text-xl">
                    <i class="fas fa-moon"></i> <!-- JS cambiará a fa-sun -->
                </button>
                <!-- Icono Hamburguesa (visible en móvil) -->
                <div class="hamburger-menu lg:hidden flex flex-col gap-1 cursor-pointer" id="hamburger-menu">
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                    <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenedor del MENÚ MÓVIL (copiado y adaptado de index.php) -->
    <div id="mobile-menu" class="hidden lg:hidden bg-white dark:bg-gray-800 shadow-lg py-4">
        <ul class="flex flex-col items-center gap-4 font-medium tracking-wide text-gray-700 dark:text-gray-200 transition-colors duration-200" style="font-family: 'Roboto', 'Montserrat', Arial, sans-serif;">
            <!-- Enlaces a secciones del panel admin -->
            <li><a href="#users" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Usuarios</a></li>
            <li><a href="#doctors" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Doctores</a></li>
            <li><a href="#reports" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Reportes</a></li>
            <li><a href="#settings" class="block text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-4 py-2">Configuraciones</a></li>

            <hr class="w-1/2 border-gray-300 dark:border-gray-600 my-2">

            <!-- Saludo y cerrar sesión -->
            <li class="text-gray-700 dark:text-gray-300 px-4 py-2">Hola, <?php echo htmlspecialchars($nombre_usuario); ?></li>
            <li><a href="mediagenda-backend/logout.php" class="block text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 px-4 py-2">Cerrar Sesión</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="w-full py-10 dark:bg-gray-800">
        <div class="container mx-auto px-6">
            <!-- Sección de Gestión de Usuarios -->
            <section id="users" class="mb-12 scroll-mt-20">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold dark:text-gray-200">Gestión de Usuarios</h2>
                    <button id="btn-mostrar-crear-usuario" class="absolute left-0 hidden group-hover:flex flex-col bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-all duration-700 ease-in-out min-w-max">
                        <i class="bi bi-plus-lg mr-1"></i> Crear Nuevo Usuario
                    </button>
                </div>

                <!-- Formulario para Crear/Editar Usuario (Inicialmente oculto) -->
                <div id="admin-user-form-container" class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800 mb-8" style="display: none;">
                    <h3 id="admin-user-form-title" class="text-lg font-semibold mb-4">Crear Nuevo Usuario</h3>
                    <form id="admin-user-form">
                        <!-- Campo oculto para ID en modo edición -->
                        <input type="hidden" name="idUsuario" id="admin-user-id" value="">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label for="admin-user-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo</label>
                                <input type="text" id="admin-user-nombre" name="nombre" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="admin-user-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico</label>
                                <input type="email" id="admin-user-email" name="email" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="admin-user-rol" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol</label>
                                <select id="admin-user-rol" name="rol" required class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="" disabled selected>Seleccionar Rol</option>
                                    <option value="paciente">Paciente</option>
                                    <option value="medico">Médico</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                            <div>
                                <label for="admin-user-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contraseña</label>
                                <input type="password" id="admin-user-password" name="password" placeholder="Dejar vacío para no cambiar (en edición)" class="border rounded-md p-2 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres. Obligatoria al crear.</p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-4">
                            <button type="button" id="btn-cancelar-edicion-usuario" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md transition duration-200 text-sm">Cancelar</button>
                            <button type="submit" id="btn-guardar-usuario" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition duration-200 text-sm">Guardar Usuario</button>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Usuarios -->
                <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800 overflow-x-auto">
                    <h3 class="text-lg font-semibold mb-4 dark:text-gray-200">Lista de Usuarios Registrados</h3>
                    <div id="admin-user-list-loading" class="text-center py-4 text-gray-500 dark:text-gray-400 italic">Cargando usuarios...</div>
                    <table id="admin-user-table" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 hidden">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="admin-user-list-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Las filas de usuarios se insertarán aquí por JS -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Aquí irían las otras secciones del panel admin (Doctores, Reportes, etc.) -->
            <!-- <section id="doctors" class="mb-12 scroll-mt-20"> ... </section> -->
            <!-- <section id="reports" class="mb-12 scroll-mt-20"> ... </section> -->
        </div> <!-- Fin del div container interno -->
    </main>

    <!-- Área para mostrar notificaciones -->
    <div id="notification-area" class="fixed top-5 right-5 z-[100] space-y-2 w-full max-w-xs sm:max-w-sm"></div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 dark:bg-gray-800">
    <div class="container mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Columna 1: Logo y Descripción -->
            <div class="md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <img src="img/logo.png" alt="MediAgenda Logo" class="h-10 w-auto object-contain">
                    <span class="logo-pacifico text-blue-600 text-2xl">MediAgenda</span>
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
            &copy; <?php echo date("Y"); ?> MediAgenda. Todos los derechos reservados.
        </div>
    </div>
</footer>

    <!-- Enlaces a JavaScript -->
    <!-- Añadir SweetAlert2 ANTES de los otros scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- Incluir scripts.js PRIMERO si necesitas funciones comunes como modo oscuro, menú, etc. -->
    <script src="scripts.js"></script>
    <!-- Incluir panel-admin.js DESPUÉS -->
    <script src="panel-admin.js"></script>

</body>

</html>