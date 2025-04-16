<?php
// =================== FOOTER ===================
?>
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
    <div class="border-t border-gray-700 mt-8 py-6">
        <div class="container mx-auto px-6 text-center text-sm text-gray-500">
            &copy; <?php echo date("Y"); ?> MediAgenda. Todos los derechos reservados.
        </div>
    </div>
</footer>
