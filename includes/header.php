<?php
// =================== HEADER ===================
?>
<header class="bg-white shadow-md sticky top-0 z-50 dark:bg-gray-800">
    <div class="container mx-auto flex justify-between items-center py-3 px-4">
        <!-- Logo de MediAgenda -->
        <div class="flex items-center gap-2">
            <a href="index.php">
                <img src="img/logo.png" alt="MediAgenda Logo" class="h-10 w-auto object-contain">
            </a>
            <span class="logo-pacifico text-blue-600 text-2xl">MediAgenda</span>
        </div>
        <?php
            include_once __DIR__ . '/menu.php';
            echo renderMenuLinks($loggedIn, $rolUsuario, $panelLink, $agendarCitaLink, $nombreUsuario);
        ?>
        <!-- Contenedor para botones derechos (modo oscuro y hamburguesa) -->
        <div class="flex items-center gap-4">
            <button id="dark-mode-toggle" type="button"
                class="ml-2 p-2 rounded-full bg-gray-100 dark:bg-gray-700 shadow-sm hover:bg-blue-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-400 dark:focus:ring-yellow-400 transition-colors duration-300 text-xl"
                aria-label="Toggle dark mode">
                <i id="dark-mode-icon" class="fas fa-moon text-blue-600 dark:fa-sun dark:text-yellow-400 transition-colors duration-300"></i>
            </button>
            <div class="hamburger-menu lg:hidden flex flex-col gap-1 cursor-pointer" id="hamburger-menu">
                <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
                <span class="w-6 h-0.5 bg-blue-600 dark:bg-blue-300"></span>
            </div>
        </div>
    </div>
</header>
