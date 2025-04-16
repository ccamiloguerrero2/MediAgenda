<?php
/**
 * Componente de Pie de Página (Footer)
 *
 * Genera el HTML para el pie de página común utilizado en las
 * diferentes vistas de la aplicación MediAgenda.
 * Incluye información de copyright, enlaces rápidos, de soporte,
 * redes sociales y contacto.
 * También carga los scripts JavaScript necesarios al final del body.
 *
 * @package MediAgenda\App\Views\Layout
 */

// No se requieren dependencias PHP específicas aquí,
// pero asume que $loggedIn, $rolUsuario, etc. podrían estar disponibles
// si se necesitaran enlaces condicionales en el footer en el futuro.
// require_once __DIR__ . '/../../Core/session_utils.php'; // <-- Generalmente no necesario aquí

?>
<footer class="bg-gray-900 text-gray-300 dark:bg-gray-800 mt-auto"> <!-- mt-auto ayuda a empujar el footer hacia abajo en páginas cortas si el body es flex-col min-h-screen -->
    <div class="container mx-auto px-4 md:px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

            <!-- Columna 1: Logo y Descripción -->
            <div class="md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <img src="/img/logo.png" alt="MediAgenda Logo" class="h-10 w-auto object-contain">
                    <!-- Asegurar que la clase CSS 'logo-pacifico' esté definida globalmente o en los estilos de esta página -->
                    <span class="logo-pacifico text-blue-600 text-2xl">MediAgenda</span>
                </div>
                <p class="text-sm text-gray-400">Facilitando el acceso a la salud, conectando pacientes y profesionales.</p>
            </div>

            <!-- Columna 2: Enlaces Rápidos -->
            <div>
                <h3 class="text-base font-semibold text-white mb-4 uppercase tracking-wider">Navegación</h3>
                <ul class="space-y-2">
                    <li><a href="/index.php#how-it-works" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Cómo Funciona</a></li>
                    <li><a href="/index.php#services" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Servicios</a></li>
                     <li><a href="/index.php#doctors" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Doctores</a></li>
                    <li><a href="/blog.html" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Blog</a></li>
                    <li><a href="/registro.php" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Acceso / Registro</a></li>
                </ul>
            </div>

            <!-- Columna 3: Legal y Soporte -->
            <div>
                <h3 class="text-base font-semibold text-white mb-4 uppercase tracking-wider">Soporte</h3>
                <ul class="space-y-2">
                    <li><a href="/contacto.html" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Contacto</a></li>
                    <li><a href="/politicas.html" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Políticas de Privacidad</a></li>
                    <li><a href="/politicas.html#terms" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Términos de Servicio</a></li>
                    <li><a href="/contacto.html#faq" class="text-sm text-gray-400 hover:text-white transition-colors duration-300">Preguntas Frecuentes</a></li>
                </ul>
            </div>

            <!-- Columna 4: Redes Sociales y Contacto -->
            <div>
                <h3 class="text-base font-semibold text-white mb-4 uppercase tracking-wider">Síguenos</h3>
                <div class="flex space-x-4 mb-6">
                    <!-- Reemplazar '#' con enlaces reales a redes sociales -->
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300" aria-label="Facebook"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300" aria-label="Twitter"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300" aria-label="Instagram"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300" aria-label="LinkedIn"><i class="fab fa-linkedin-in fa-lg"></i></a>
                </div>
                <h3 class="text-base font-semibold text-white mb-3 uppercase tracking-wider">Contacto Directo</h3>
                <p class="text-sm text-gray-400 flex items-center gap-2 mb-1">
                    <i class="bi bi-envelope-fill text-blue-400"></i>
                    <a href="mailto:info@mediagenda.local" class="hover:text-white">info@mediagenda.local</a> <!-- Cambiar email -->
                </p>
                <p class="text-sm text-gray-400 flex items-center gap-2">
                     <i class="bi bi-telephone-fill text-blue-400"></i>
                     <span>+57 315 288 5138</span> <!-- Cambiar teléfono -->
                </p>
            </div>

        </div>
    </div>

    <!-- Línea de Copyright -->
    <div class="border-t border-gray-700 dark:border-gray-600 mt-8 py-6">
        <div class="container mx-auto px-4 md:px-6 text-center text-sm text-gray-500">
            © <?php echo date("Y"); ?> MediAgenda. Todos los derechos reservados.
        </div>
    </div>

    <!-- --- Carga de Scripts JavaScript --- -->
    <!-- Es importante cargar SweetAlert2 ANTES de scripts.js si este último lo utiliza -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- Script principal de la aplicación (maneja UI común, AJAX, etc.) -->
    <!-- Usar ruta absoluta desde el DocumentRoot (public/) -->
    <script src="/js/scripts.js"></script>

    <!-- Cargar scripts específicos de página DESPUÉS de scripts.js si es necesario -->
    <?php
        // Ejemplo de carga condicional de script específico del panel admin
        // Obtener el nombre del script actual
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page === 'panel-admin-sistema.php') {
            echo '<script src="/js/panel-admin.js"></script>' . "\n";
        }
        // Añadir más condiciones para otros scripts específicos de página si los hubiera
    ?>

    <!-- (Opcional) Área para mostrar notificaciones flotantes si se usara un método diferente a SweetAlert modales -->
    <!-- <div id="notification-area" class="fixed top-5 right-5 z-[100] space-y-2 w-full max-w-xs sm:max-w-sm"></div> -->

</footer>