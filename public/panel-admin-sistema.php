<?php
/**
 * Panel de Administración del Sistema
 *
 * Permite a los administradores gestionar usuarios (CRUD), doctores,
 * ver reportes y ajustar configuraciones del sistema.
 * Requiere que el usuario esté autenticado y tenga el rol 'admin'.
 *
 * @package MediAgenda\Public
 */

// --- Dependencias Core ---
require_once __DIR__ . '/../app/Core/session_utils.php'; // Carga sesión y funciones helper

// --- Autorización ---
// Verificar si el usuario está autenticado Y es administrador
if (!is_authenticated() || !is_admin()) { // Usa las funciones helper de session_utils
    // Redirigir a la página de inicio con un mensaje de error específico
    header('Location: index.php?error=unauthorized'); // O login.php si se prefiere
    exit();
}

// Obtener datos del admin logueado (para saludo, etc.)
$nombre_usuario = get_user_name(); // Usa helper

?>
<!DOCTYPE html>
<html lang="es" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Administración del Sistema</title>

    <!-- --- CSS --- -->
    <link rel="stylesheet" href="/dist/output.css"> <!-- Tailwind Compilado -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- --- Estilos Específicos --- -->
    <style>
        .logo-pacifico { font-family: 'Pacifico', cursive; }

        /* Ocultar formulario de usuario por defecto */
        #admin-user-form-container { display: none; }

        /* Estilos para estado de carga y tabla (mejorados) */
        #admin-user-list-loading {
            padding: 2rem;
            text-align: center;
            font-style: italic;
            color: #6b7280; /* gray-500 */
        }
        .dark #admin-user-list-loading { color: #9ca3af; /* gray-400 */ }

        #admin-user-table.hidden { display: none; }

        /* Animación del icono Hamburguesa */
        #hamburger-menu span { transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out; }
        #hamburger-menu.open span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        #hamburger-menu.open span:nth-child(2) { opacity: 0; }
        #hamburger-menu.open span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-gray-100 flex flex-col min-h-screen">

    <!-- Incluir Cabecera (Usa la versión estándar que carga el menú dinámico) -->
    <?php require_once __DIR__ . '/../app/Views/Layout/header.php'; ?>

    <!-- Contenido Principal -->
    <main class="w-full py-10 dark:bg-gray-800 flex-grow">
        <div class="container mx-auto px-4 md:px-6">

            <!-- --- Sección: Gestión de Usuarios --- -->
            <section id="users" class="mb-12 scroll-mt-20">
                <!-- Cabecera de la sección -->
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Usuarios</h2>
                    <button id="btn-mostrar-crear-usuario" type="button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow transition duration-200 flex items-center gap-2 w-full sm:w-auto justify-center">
                        <i class="bi bi-plus-lg"></i> Crear Nuevo Usuario
                    </button>
                </div>

                <!-- Formulario para Crear/Editar Usuario (Oculto inicialmente) -->
                <!-- Manejado por panel-admin.js -> admin_crear_usuario.php / admin_actualizar_usuario.php -->
                <div id="admin-user-form-container" class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-8">
                    <h3 id="admin-user-form-title" class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Crear Nuevo Usuario</h3>
                    <form id="admin-user-form" novalidate>
                        <!-- Campo oculto para ID en modo edición -->
                        <input type="hidden" name="idUsuario" id="admin-user-id" value="">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label for="admin-user-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                                <input type="text" id="admin-user-nombre" name="nombre" required autocomplete="name"
                                       class="border rounded-md p-2 w-full bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-red-600 mt-1 error-message" id="admin-user-nombre-error"></p>
                            </div>
                            <div>
                                <label for="admin-user-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                                <input type="email" id="admin-user-email" name="email" required autocomplete="email"
                                       class="border rounded-md p-2 w-full bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-red-600 mt-1 error-message" id="admin-user-email-error"></p>
                            </div>
                            <div>
                                <label for="admin-user-rol" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol <span class="text-red-500">*</span></label>
                                <select id="admin-user-rol" name="rol" required
                                        class="border rounded-md p-2 w-full bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500 appearance-none pr-8 bg-no-repeat bg-right"
                                        style="background-image: url('data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3e%3cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27M6 8l4 4 4-4%27/%3e%3c/svg%3e'); background-position: right 0.5rem center; background-size: 1.5em 1.5em;">
                                    <option value="" disabled selected>Seleccionar Rol</option>
                                    <option value="paciente">Paciente</option>
                                    <option value="medico">Médico</option>
                                    <option value="admin">Administrador</option>
                                </select>
                                <p class="text-xs text-red-600 mt-1 error-message" id="admin-user-rol-error"></p>
                            </div>
                            <div>
                                <label for="admin-user-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contraseña <span class="text-red-500" id="password-required-indicator">*</span></label>
                                <input type="password" id="admin-user-password" name="password" placeholder="Mínimo 6 caracteres" autocomplete="new-password"
                                       class="border rounded-md p-2 w-full bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1" id="password-help-text">Obligatoria al crear. Dejar vacío para no cambiar en edición.</p>
                                <p class="text-xs text-red-600 mt-1 error-message" id="admin-user-password-error"></p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-4 border-t pt-4 dark:border-gray-700">
                            <button type="button" id="btn-cancelar-edicion-usuario" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md transition duration-200 text-sm">Cancelar</button>
                            <button type="submit" id="btn-guardar-usuario" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition duration-200 text-sm flex items-center">
                                <span class="button-text">Guardar Usuario</span>
                                <!-- Spinner Opcional -->
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabla de Usuarios -->
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 overflow-x-auto">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Lista de Usuarios Registrados</h3>
                    <!-- Estado de carga -->
                    <div id="admin-user-list-loading" class="text-center py-4">Cargando usuarios...</div>
                    <!-- Tabla (inicialmente oculta) -->
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
                            <!-- Las filas de usuarios se insertarán aquí por panel-admin.js -->
                            <!-- Ejemplo de fila (para referencia, no descomentar):
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">1</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">Admin Uno</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">admin@example.com</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">admin</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button data-action="editar-usuario" data-id="1" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Editar</button>
                                    <button data-action="eliminar-usuario" data-id="1" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 ml-4">Eliminar</button>
                                </td>
                            </tr>
                             -->
                        </tbody>
                    </table>
                     <!-- Mensaje si no hay usuarios -->
                    <div id="admin-user-list-empty" class="hidden text-center py-4 text-gray-500 dark:text-gray-400 italic">
                        No hay usuarios registrados.
                    </div>
                </div>
            </section>

            <!-- --- Sección: Gestión de Doctores (Placeholder) --- -->
            <section id="doctors" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Gestión de Doctores</h2>
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                    <p class="text-gray-600 dark:text-gray-300 italic">Funcionalidad de gestión específica para doctores (asignar horarios, especialidades adicionales, etc.) próximamente.</p>
                    <!-- Aquí iría una tabla/formulario similar a la de usuarios pero para doctores -->
                </div>
            </section>

            <!-- --- Sección: Reportes (Placeholder) --- -->
            <section id="reports" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Reportes del Sistema</h2>
                 <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                    <p class="text-gray-600 dark:text-gray-300 italic">Generación de reportes (citas por médico, usuarios registrados, etc.) próximamente.</p>
                </div>
            </section>

             <!-- --- Sección: Configuraciones (Placeholder) --- -->
            <section id="settings" class="scroll-mt-20">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Configuraciones</h2>
                 <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                    <p class="text-gray-600 dark:text-gray-300 italic">Ajustes generales del sistema próximamente.</p>
                </div>
            </section>

        </div> <!-- Fin container mx-auto -->
    </main>

    <!-- Incluir Pie de Página -->
    <?php require_once __DIR__ . '/../app/Views/Layout/footer.php'; ?>

    <!-- --- JavaScript --- -->
    <!-- SweetAlert2 JS (antes de scripts.js) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- Scripts principales de la aplicación (contiene helpers globales) -->
    <!-- Scripts específicos del panel de administración -->

</body>
</html>