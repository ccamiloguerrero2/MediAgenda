<?php
// =================== MENÚ DE NAVEGACIÓN ===================
function renderMenuLinks($loggedIn, $rolUsuario, $panelLink, $agendarCitaLink, $nombreUsuario) {
    ob_start();
    ?>
    <!-- Menú de navegación de ESCRITORIO con submenús -->
    <nav style="font-family: 'Roboto', 'Montserrat', Arial, sans-serif;">
        <ul id="nav-links" class="hidden lg:flex gap-x-4 items-center font-medium tracking-wide text-gray-700 dark:text-gray-200 transition-colors duration-200 bg-white/70 dark:bg-gray-900/70 rounded-xl px-2 py-1 shadow-sm border border-gray-200 dark:border-gray-700">
            <?php if ($loggedIn): ?>
                <li class="relative group">
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md">Panel</a>
                    <ul class="absolute left-0 hidden group-hover:flex flex-col bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-all duration-700 ease-in-out min-w-max">
                        <li><a href="<?php echo $panelLink; ?>" class="block px-4 py-2 hover:bg-blue-600 hover:text-white dark:text-gray-300 font-semibold rounded-t-lg">Ir a mi Panel</a></li>
                    </ul>
                </li>
            <?php endif; ?>
            <li><a href="blog.html" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md">Blog</a></li>
            <li class="relative group">
                <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md">Ayuda</a>
                <ul class="absolute left-0 hidden group-hover:flex flex-col bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden transition-all duration-700 ease-in-out">
                    <li><a href="politicas.html" class="block px-4 py-2 hover:bg-blue-600 hover:text-white dark:text-gray-300 rounded-t-lg">Políticas</a></li>
                    <li><a href="contacto.html" class="block px-4 py-2 hover:bg-blue-600 hover:text-white dark:text-gray-300 rounded-b-lg">Contacto</a></li>
                </ul>
            </li>
            <?php if (!$loggedIn): ?>
                <li><a href="registro.php" class="text-gray-600 dark:text-gray-300 hover:text-white hover:bg-blue-600 px-4 py-2 rounded-md transition">Registro / Iniciar Sesión</a></li>
            <?php else: ?>
                <li class="text-gray-700 dark:text-gray-300 px-4 py-2">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></li>
                <li><a href="mediagenda-backend/logout.php" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-500 font-medium px-3 py-2 rounded-md hover:bg-red-50 dark:hover:bg-gray-700 transition text-sm"><i class="bi bi-box-arrow-right mr-1"></i>Cerrar Sesión</a></li>
            <?php endif; ?>
            <?php if (!$loggedIn || $rolUsuario !== 'admin'): ?>
                <li><a href="<?php echo $agendarCitaLink; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-200">Agendar Cita</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
    return ob_get_clean();
}
