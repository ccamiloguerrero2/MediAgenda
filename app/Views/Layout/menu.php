<?php
/**
 * Componente de Menú de Navegación Principal (Escritorio)
 *
 * Define la función `renderMenuLinks` que genera el HTML para la barra
 * de navegación principal que se muestra en pantallas grandes (>=lg).
 * Muestra enlaces condicionalmente basados en el estado de autenticación
 * y el rol del usuario.
 *
 * @param bool   $loggedIn        True si el usuario está autenticado, False si no.
 * @param string $rolUsuario      El rol del usuario ('paciente', 'medico', 'admin', '') en minúsculas.
 * @param string $panelLink       URL al panel correspondiente del usuario logueado.
 * @param string $agendarCitaLink URL para el botón/enlace de agendar cita.
 * @param string $nombreUsuario   Nombre del usuario logueado (ya sanitizado).
 *
 * @return string El HTML generado para la lista del menú (`<ul>...</ul>`).
 *
 * @package MediAgenda\App\Views\Layout
 */

// --- Modo Estricto ---
declare(strict_types=1);

/**
 * Renderiza la lista de enlaces del menú de navegación principal de escritorio.
 *
 * @param bool   $loggedIn        Estado de autenticación.
 * @param string $rolUsuario      Rol del usuario actual.
 * @param string $panelLink       Enlace al panel del usuario.
 * @param string $agendarCitaLink Enlace para agendar cita.
 * @param string $nombreUsuario   Nombre del usuario.
 *
 * @return string HTML del menú.
 */
function renderMenuLinks(
    bool $loggedIn,
    string $rolUsuario,
    string $panelLink,
    string $agendarCitaLink,
    string $nombreUsuario
): string {
    // Usar buffer de salida para construir el HTML
    ob_start();
    ?>
    <!-- Menú de navegación de ESCRITORIO con submenús -->
    <!-- Nota: La visibilidad (hidden lg:flex) se controla en el <nav> que llama a esta función en header.php -->
    <ul id="nav-links-desktop" class="flex gap-x-1 items-center font-medium tracking-wide text-gray-700 dark:text-gray-200 transition-colors duration-200">

        <?php // --- Enlace: Inicio (Opcional, el logo ya enlaza a index) --- ?>
        <?php /* Eliminar o mantener según preferencia
        <li>
            <a href="/index.php" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-3 py-2 rounded-md transition text-sm">
                Inicio
            </a>
        </li>
         */ ?>

        <?php // --- Enlace/Submenú: Panel (Solo si está logueado) --- ?>
        <?php if ($loggedIn): ?>
            <li class="relative group">
                <!-- Enlace principal del submenú -->
                <a href="<?php echo htmlspecialchars($panelLink); ?>" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-3 py-2 rounded-md transition text-sm flex items-center">
                    <i class="bi bi-speedometer2 mr-1"></i> Mi Panel
                    <i class="bi bi-chevron-down text-xs ml-1 group-hover:rotate-180 transition-transform"></i> <!-- Icono flecha opcional -->
                </a>
                <!-- Contenido del submenú (aparece al hacer hover) -->
                <ul class="absolute left-0 mt-1 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-opacity duration-200 ease-in-out min-w-[160px] border dark:border-gray-700">
                    <li>
                        <a href="<?php echo htmlspecialchars($panelLink); ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700 font-semibold">
                           <i class="bi bi-layout-text-sidebar-reverse mr-2"></i> Ir a mi Panel
                        </a>
                    </li>
                    <?php // Añadir más enlaces específicos del panel si se desea (ej. Perfil, Citas)
                        /*
                        if ($rolUsuario === 'paciente') {
                           echo '<li><a href="/perfil-usuario.php#profile" class="block px-4 py-2 text-sm ..."><i class="bi bi-person-circle mr-2"></i> Mi Perfil</a></li>';
                           echo '<li><a href="/perfil-usuario.php#appointments" class="block px-4 py-2 text-sm ..."><i class="bi bi-calendar-check mr-2"></i> Mis Citas</a></li>';
                        } elseif ($rolUsuario === 'medico') {
                           echo '<li><a href="/perfil-doctores.php#profile" class="block px-4 py-2 text-sm ..."><i class="bi bi-person-badge mr-2"></i> Mi Perfil</a></li>';
                           echo '<li><a href="/perfil-doctores.php#appointments" class="block px-4 py-2 text-sm ..."><i class="bi bi-calendar-week mr-2"></i> Mis Citas</a></li>';
                        }
                        */
                    ?>
                     <li class="border-t dark:border-gray-700"></li> <!-- Separador -->
                     <li>
                         <a href="/api/Auth/logout.php" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 font-medium">
                           <i class="bi bi-box-arrow-right mr-2"></i> Cerrar Sesión
                        </a>
                     </li>
                </ul>
            </li>
        <?php endif; // Fin $loggedIn ?>

        <?php // --- Enlace: Blog --- ?>
        <li>
            <a href="/blog.html" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-3 py-2 rounded-md transition text-sm">
                Blog
            </a>
        </li>

        <?php // --- Enlace/Submenú: Ayuda --- ?>
        <li class="relative group">
            <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-3 py-2 rounded-md transition text-sm flex items-center">
                Ayuda <i class="bi bi-chevron-down text-xs ml-1 group-hover:rotate-180 transition-transform"></i>
            </a>
            <ul class="absolute left-0 mt-1 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-opacity duration-200 ease-in-out min-w-[160px] border dark:border-gray-700">
                <li><a href="/politicas.html" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700"><i class="bi bi-file-earmark-lock mr-2"></i>Políticas</a></li>
                <li><a href="/contacto.html" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700"><i class="bi bi-envelope mr-2"></i>Contacto</a></li>
                 <li><a href="/contacto.html#faq" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-gray-700"><i class="bi bi-question-circle mr-2"></i>Preguntas Frec.</a></li>
            </ul>
        </li>

        <?php // --- Separador Visual (Opcional) --- ?>
        <li class="border-l border-gray-300 dark:border-gray-600 h-6 mx-2"></li>

        <?php // --- Bloque Usuario/Login --- ?>
        <?php if (!$loggedIn): ?>
            <li><a href="/registro.php" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-3 py-2 rounded-md transition text-sm">Registro / Iniciar Sesión</a></li>
        <?php else: ?>
            <li class="text-gray-700 dark:text-gray-300 px-2 py-2 text-sm truncate" title="Usuario: <?php echo $nombreUsuario; /* nombreUsuario ya está sanitizado */ ?>">
                Hola, <?php echo $nombreUsuario; ?>
            </li>
            <?php /* El botón de cerrar sesión ahora está dentro del submenú "Mi Panel"
            <li>
                <a href="/api/Auth/logout.php"
                   class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-500 font-medium px-3 py-2 rounded-md hover:bg-red-50 dark:hover:bg-gray-700 transition text-sm"
                   title="Cerrar Sesión">
                   <i class="bi bi-box-arrow-right"></i> {/* Icono solo quizás *}
                </a>
            </li>
             */ ?>
        <?php endif; ?>

        <?php // --- Botón Agendar Cita (si no es admin) --- ?>
        <?php if (!$loggedIn || $rolUsuario !== 'admin'): ?>
            <li>
                <a href="<?php echo htmlspecialchars($agendarCitaLink); ?>"
                   class="ml-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-200 text-sm flex items-center shadow-sm">
                   <i class="bi bi-calendar-plus mr-1"></i> Agendar Cita
                </a>
            </li>
        <?php endif; ?>

    </ul>
    <?php
    // Devuelve el HTML capturado por el buffer
    return ob_get_clean();
} // Fin de la función renderMenuLinks

// No añadir ?>