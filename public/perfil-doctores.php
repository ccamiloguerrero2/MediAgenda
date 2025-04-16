<?php
/**
 * Panel de Control para Médicos (Doctores)
 *
 * Muestra el perfil del médico, sus citas agendadas, pacientes del día
 * y funcionalidades futuras como consulta online. Permite la actualización
 * del perfil y la gestión del estado de las citas.
 * Requiere que el usuario esté autenticado y tenga el rol 'medico'.
 *
 * @package MediAgenda\Public
 */

// --- Dependencias Core ---
require_once __DIR__ . '/../app/Core/session_utils.php'; // Carga sesión y utilidades

// --- Autorización ---
// Verificar si el usuario está autenticado Y es un médico
if (!is_authenticated() || get_user_role() !== 'medico') {
    // Redirigir a la página de inicio o login con un mensaje de error
    // Usamos un error específico para roles no autorizados
    header('Location: index.php?error=unauthorized_role');
    exit();
}

// (Opcional) Obtener datos básicos del médico para usar en la página si fuera necesario
// $user_id = get_user_id();
// $nombre_usuario = get_user_name(); // Ya disponible desde session_utils.php

?>
<!DOCTYPE html>
<html lang="es" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Panel de Doctores</title>

    <!-- --- CSS --- -->
    <link rel="stylesheet" href="/dist/output.css"> <!-- Tailwind Compilado -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- --- Estilos Específicos de la Página --- -->
    <style>
        /* Estilo para la fuente del logo */
        .logo-pacifico { font-family: 'Pacifico', cursive; }

        /* Placeholder styles para listas cargadas por JS */
        #appointments-list-doctor li.placeholder,
        #patients-list-doctor li.placeholder {
            color: #9ca3af; /* gray-400 */
            font-style: italic;
            padding: 1rem;
            text-align: center;
        }

        button[data-action]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Forzar fondo oscuro en cards específicamente en esta página (si es necesario) */
        /* Considerar usar selectores más específicos o variantes dark: de Tailwind */
        /*
        .dark .force-dark-card {
            background-color: #1f2937 !important; // gray-800
            color: #f3f4f6 !important; // gray-100
        }
        */
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-gray-100 flex flex-col min-h-screen">

    <!-- Incluir Cabecera (con lógica de menú actualizada) -->
    <?php require_once __DIR__ . '/../app/Views/Layout/header.php'; ?>

    <!-- Contenido Principal -->
    <main class="w-full min-h-screen py-10 px-0 bg-white dark:bg-gray-900 flex-grow">
        <div class="container mx-auto px-4 md:px-6">

            <!-- Sección: Perfil Profesional del Doctor -->
            <section id="profile" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Tu Perfil Profesional</h2>
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 md:p-8">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Actualiza tu información</h3>
                    <!-- Formulario manejado por scripts.js -> actualizar_perfil.php -->
                    <form id="doctor-profile-form" novalidate>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label for="doc-profile-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                                <input type="text" id="doc-profile-nombre" name="nombre" placeholder="Tu nombre" required autocomplete="name"
                                       class="border rounded-md p-2 w-full bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-red-600 mt-1 error-message" id="doc-profile-nombre-error"></p>
                            </div>
                            <div>
                                <label for="doc-profile-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                                <input type="email" id="doc-profile-email" name="email" placeholder="tu@correo.com" required autocomplete="email"
                                       class="border rounded-md p-2 w-full bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-red-600 mt-1 error-message" id="doc-profile-email-error"></p>
                            </div>
                            <div>
                                <label for="doc-profile-especialidad" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Especialidad</label>
                                <input type="text" id="doc-profile-especialidad" name="especialidad" placeholder="Ej: Cardiología"
                                       class="border rounded-md p-2 w-full bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500">
                                <!-- No se requiere validación de error JS aquí si no es obligatorio -->
                            </div>
                            <div>
                                <label for="doc-profile-horario" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Horario (Texto descriptivo)</label>
                                <textarea id="doc-profile-horario" name="horario" rows="3" placeholder="Ej: Lunes y Miércoles 9am-1pm, Viernes 2pm-5pm"
                                          class="border rounded-md p-2 w-full bg-gray-50 dark:bg-gray-700 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            <!-- Futuro: Añadir campos para cambiar contraseña si es necesario -->
                        </div>
                        <div class="text-right mt-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-md transition duration-200">
                                <i class="bi bi-save mr-1"></i> Actualizar Perfil
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Sección: Gestión de Citas del Médico -->
            <section id="appointments" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Tus Citas Agendadas</h2>
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 md:p-8">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Próximas y Pasadas Citas</h3>
                    <!-- Lista poblada por JS (scripts.js -> cargarCitasMedico) -->
                    <ul id="appointments-list-doctor" class="space-y-4">
                        <!-- Placeholder inicial -->
                        <li class="placeholder">Cargando citas...</li>
                        <!-- Las citas generadas por JS se insertarán aquí -->
                    </ul>
                </div>
            </section>

            <!-- Sección: Pacientes del Día y Notas de Consulta -->
            <section id="patients" class="mb-12 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Pacientes del Día</h2>
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 md:p-8">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Lista de pacientes agendados para hoy</h3>
                    <!-- Lista poblada por JS (scripts.js -> cargarPacientesHoy) -->
                    <ul id="patients-list-doctor" class="space-y-3 mb-6">
                         <!-- Placeholder inicial -->
                        <li class="placeholder">Cargando pacientes de hoy...</li>
                        <!-- Los pacientes generados por JS se insertarán aquí -->
                    </ul>

                    <!-- Formulario para Notas de Consulta -->
                    <div class="border-t pt-6 dark:border-gray-700">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Registrar Notas de Consulta</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            Selecciona una cita de la lista de "Pacientes del Día" o "Citas Agendadas" usando el botón <kbd class="px-1.5 py-0.5 text-xs font-semibold text-blue-800 bg-blue-100 border border-blue-300 rounded-sm dark:bg-blue-900 dark:text-blue-300 dark:border-blue-700">Cargar p/ Notas</kbd> para asociar estas notas.
                        </p>
                         <!-- Formulario manejado por scripts.js -> guardarNotasConsulta -->
                        <form id="consulta-notes-form" novalidate>
                            <!-- No necesita campo oculto, el ID se guarda en variable JS (selectedCitaIdForNotes) -->
                            <div>
                                <label for="consulta-notas" class="sr-only">Notas de la consulta</label> <!-- Screen reader only label -->
                                <textarea id="consulta-notas" name="diagnostico_tratamiento" rows="5"
                                          class="border rounded-md p-3 w-full dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Escribir diagnóstico, tratamiento, notas para la cita seleccionada..." required></textarea>
                                <p class="text-xs text-red-600 mt-1 error-message" id="consulta-notas-error"></p>
                            </div>
                            <div class="text-right mt-3">
                                <!-- Botón submit -> apunta a /api/Citas/guardar_notas_consulta.php -->
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                                    <i class="bi bi-save mr-1"></i> Guardar Notas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Sección: Consultas en Línea (Placeholder) -->
            <section id="consultations" class="scroll-mt-20">
                <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Consultas en Línea</h2>
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 md:p-8">
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Funcionalidad de consulta virtual y chat (Próximamente).</p>
                    <!-- Ejemplo de elementos deshabilitados -->
                    <button class="bg-blue-500 text-white py-2 px-4 rounded-md opacity-50 cursor-not-allowed" disabled>Iniciar Consulta Virtual</button>
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-100">Notas rápidas durante consulta virtual</h3>
                        <textarea name="notas_consulta_online" rows="3" class="border rounded-md p-2 w-full dark:bg-gray-700 opacity-50 cursor-not-allowed" placeholder="Escribir notas médicas..." disabled></textarea>
                        <button class="mt-2 bg-blue-500 text-white py-2 px-4 rounded-md opacity-50 cursor-not-allowed" disabled>Guardar Notas</button>
                    </div>
                </div>
            </section>

        </div> <!-- Fin container mx-auto -->
    </main>

    <!-- Incluir Pie de Página -->
    <?php require_once __DIR__ . '/../app/Views/Layout/footer.php'; ?>

    <!-- --- JavaScript --- -->
    <!-- SweetAlert2 JS (antes de scripts.js) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- Scripts principales de la aplicación -->
    <!-- panel-admin.js NO se necesita aquí -->

</body>
</html>