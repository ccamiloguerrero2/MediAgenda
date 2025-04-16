<?php
/**
 * Componente de Cabecera (Header)
 *
 * Genera el HTML para la cabecera común utilizada en las diferentes
 * vistas de la aplicación MediAgenda.
 * Incluye el logo, el menú de navegación principal (renderizado por menu.php),
 * el botón para alternar el modo oscuro y el botón de menú hamburguesa para móviles.
 *
 * @package MediAgenda\App\Views\Layout
 */

// --- Dependencias Core ---
// Necesitamos session_utils para obtener las variables necesarias por menu.php
// Incluirlo aquí asegura que esté disponible antes de llamar a renderMenuLinks.
require_once __DIR__ . '/../../Core/session_utils.php'; // $loggedIn, $rolUsuario, $panelLink, etc.
// Incluir el componente que renderiza el menú
require_once __DIR__ . '/menu.php'; // Contiene la función renderMenuLinks()

?>
<header class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-50 dark:bg-opacity-90 backdrop-blur-sm"> {/* Añadidos efectos visuales */}
    <div class="container mx-auto flex justify-between items-center py-3 px-4 md:px-6">

        <!-- Logo y Nombre de la Aplicación -->
        <div class="flex items-center gap-2 flex-shrink-0"> {/* flex-shrink-0 evita que se encoja */}
            <a href="/index.php" aria-label="Página de inicio de MediAgenda">
                <img src="/img/logo.png" alt="MediAgenda Logo" class="h-10 w-auto object-contain">
            </a>
            <span class="logo-pacifico text-blue-600 dark:text-blue-400 text-2xl hidden sm:inline">MediAgenda</span> {/* Ocultar texto en pantallas muy pequeñas */}
        </div>

        <!-- Menú de Navegación Principal (Renderizado por PHP) -->
        <?php
            // Llama a la función de menu.php para generar el HTML del menú de escritorio
            // Pasando las variables necesarias obtenidas de session_utils.php
            echo renderMenuLinks($loggedIn, $rolUsuario, $panelLink, $agendarCitaLink, $nombreUsuario);
        ?>

        <!-- Contenedor para Botones Derechos (Modo Oscuro y Hamburguesa) -->
        <div class="flex items-center gap-3 sm:gap-4">
            <!-- Botón Modo Oscuro -->
            <button id="dark-mode-toggle" type="button"
                class="p-2 rounded-full text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-blue-500 dark:focus:ring-yellow-400 transition-colors duration-200"
                aria-label="Alternar modo claro/oscuro">
                {/* El icono se actualiza mediante JavaScript (scripts.js) */}
                <i id="dark-mode-icon" class="fas fa-moon text-xl"></i>
            </button>

            <!-- Botón Menú Hamburguesa (Visible solo en pantallas pequeñas, definido por clases de menú) -->
            <button class="hamburger-menu lg:hidden flex flex-col gap-1 cursor-pointer p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" id="hamburger-menu" aria-label="Abrir menú móvil" aria-expanded="false">
                 {/* Las clases dark:bg-blue-300 estaban bien, pero usar text color puede ser más flexible */}
                <span class="w-6 h-0.5 bg-gray-700 dark:bg-gray-300 rounded-full"></span>
                <span class="w-6 h-0.5 bg-gray-700 dark:bg-gray-300 rounded-full"></span>
                <span class="w-6 h-0.5 bg-gray-700 dark:bg-gray-300 rounded-full"></span>
            </button>
        </div>

    </div>

    <!-- Contenedor del MENÚ MÓVIL (HTML generado por menu.php, gestionado por scripts.js) -->
    <!-- Es importante que el ID coincida con el que busca scripts.js -->
    <div id="mobile-menu" class="hidden lg:hidden bg-white dark:bg-gray-800 shadow-lg border-t border-gray-200 dark:border-gray-700">
         <!-- El contenido UL se genera dinámicamente o se copia desde menu.php/scripts.js -->
         <!-- Ejemplo de estructura que podría generar menu.php o scripts.js -->
         <ul class="flex flex-col items-center gap-2 py-4">
             <?php
                // Re-renderizar enlaces simplificados para móvil o dejar que JS lo maneje
                // Ejemplo simple:
                if ($loggedIn) {
                    echo '<li><a href="'.htmlspecialchars($panelLink).'" class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-md">Mi Panel</a></li>';
                }
                 echo '<li><a href="/blog.html" class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-md">Blog</a></li>';
                 echo '<li><a href="/contacto.html" class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-md">Contacto</a></li>';
                 echo '<li><a href="/politicas.html" class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-md">Políticas</a></li>';

                 if (!$loggedIn || $rolUsuario !== 'admin') {
                     echo '<li><a href="'.htmlspecialchars($agendarCitaLink).'" class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-md font-medium text-blue-600 dark:text-blue-400">Agendar Cita</a></li>';
                 }

                 echo '<hr class="w-3/4 border-gray-300 dark:border-gray-600 my-2">';

                 if (!$loggedIn) {
                    echo '<li><a href="/registro.php" class="block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700 rounded-md">Registro / Iniciar Sesión</a></li>';
                 } else {
                    echo '<li class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm">Hola, '.htmlspecialchars($nombreUsuario).'</li>';
                    echo '<li><a href="/api/Auth/logout.php" class="block px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 rounded-md font-medium">Cerrar Sesión</a></li>';
                 }
             ?>
         </ul>
    </div>
</header>