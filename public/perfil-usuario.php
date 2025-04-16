<?php
/**
 * Panel de Control del Paciente
 *
 * Muestra la información del perfil del paciente, sus citas programadas,
 * el historial médico y permite agendar nuevas citas a través de un modal.
 * Requiere que el usuario esté autenticado con el rol 'paciente'.
 *
 * @package MediAgenda\Public
 */

// --- Dependencias Core ---
// Inicia/reanuda la sesión y carga utilidades (autenticación, rol, etc.)
require_once __DIR__ . '/../app/Core/session_utils.php';

// --- Autorización ---
// Redirigir si no está autenticado o no es paciente
if (!is_authenticated() || get_user_role() !== 'paciente') {
    // Podrías añadir un mensaje flash si tu sistema lo soporta
    header('Location: /index.php?error=unauthorized_role'); // Redirige a inicio
    exit;
}

// Variables de sesión ya disponibles desde session_utils.php:
// $loggedIn, $nombreUsuario, $rolUsuario, $panelLink, $agendarCitaLink

?>
<!DOCTYPE html>
<html lang="es" class="<?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : ''; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAgenda - Panel de Paciente</title>

    <!-- --- CSS --- -->
    <link rel="stylesheet" href="/dist/output.css"> <!-- Tailwind Compilado -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"> <!-- SweetAlert2 CSS -->
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- --- Estilos Específicos --- -->
    <style>
        .logo-pacifico { font-family: 'Pacifico', cursive; }
        body { font-family: 'Roboto', sans-serif; } /* Asegurar fuente base */

        /* Estilos para placeholders de carga en listas JS */
        #appointments-list li.placeholder,
        #history-list li.placeholder {
            color: #9ca3af; /* gray-400 */
            font-style: italic;
            padding: 1rem; /* Padding para que no se vea vacío */
            text-align: center;
            border: 1px dashed #e5e7eb; /* Borde punteado opcional */
            border-radius: 0.375rem; /* rounded-md */
        }
        .dark #appointments-list li.placeholder,
        .dark #history-list li.placeholder {
            color: #6b7280; /* gray-500 */
            border-color: #4b5563; /* gray-600 */
        }

        /* Clases para errores de validación JS */
        .error-message { display: none; /* Oculto por defecto */ }
        input.border-red-500,
        select.border-red-500,
        textarea.border-red-500 {
             border-color: #ef4444 !important; /* Tailwind red-500 */
             /* Opcional: añadir un anillo rojo tenue */
             /* box-shadow: 0 0 0 1px #fecaca; */ /* Tailwind red-200 */
        }
        .dark input.border-red-500,
        .dark select.border-red-500,
        .dark textarea.border-red-500 {
             border-color: #f87171 !important; /* Tailwind red-400 */
        }

        /* Asegurar visibilidad del modal */
        #schedule-modal { transition: opacity 0.3s ease-out; }
        #schedule-modal-content { transition: transform 0.3s ease-out, opacity 0.3s ease-out; }

    </style>
</head>

<body class="font-sans antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-gray-100 flex flex-col min-h-screen">

    <!-- Cabecera (Logo, Menú, Dark Mode) -->
    <?php require_once __DIR__ . '/../app/Views/Layout/header.php'; ?>

    <!-- Contenido Principal -->
    <main class="dark:bg-gray-900 flex-grow w-full"> {/* Fondo oscuro aquí también */}
        <div class="container mx-auto py-10 px-4 sm:px-6 lg:px-8">

            {/* <!-- Título del Panel (Opcional, puede estar en el header) --> */}
            {/* <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Panel del Paciente</h1> */}

            <!-- Grid Layout para las secciones -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8"> {/* Cambiado a 3 columnas */}

                <!-- Columna 1: Perfil -->
                <div class="lg:col-span-1 flex flex-col gap-8">
                    <!-- Sección: Perfil del Paciente -->
                    <section id="profile" class="scroll-mt-20 bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden h-full"> {/* h-full para intentar igualar altura */}
                         <header class="bg-gray-50 dark:bg-gray-700 p-4 sm:p-5 border-b dark:border-gray-600 flex items-center gap-3">
                            <i class="bi bi-person-circle text-xl text-blue-600 dark:text-blue-400"></i>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Tu Perfil</h2>
                        </header>
                        <div class="p-4 sm:p-6">
                            {/* Formulario manejado por scripts.js -> actualizar_perfil.php */}
                            <form id="update-profile-form" novalidate>
                                <div class="space-y-4 mb-6">
                                    <div>
                                        <label for="profile-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                                        <input type="text" id="profile-nombre" name="nombre" placeholder="Tu nombre completo" required autocomplete="name"
                                               class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-500 text-sm">
                                        <p class="text-xs text-red-600 mt-1 error-message" id="profile-nombre-error"></p>
                                    </div>
                                    <div>
                                        <label for="profile-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                                        <input type="email" id="profile-email" name="email" placeholder="tu@correo.com" required autocomplete="email"
                                               class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-500 text-sm">
                                        <p class="text-xs text-red-600 mt-1 error-message" id="profile-email-error"></p>
                                    </div>
                                    <div>
                                        <label for="profile-telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono (Opcional)</label>
                                        <input type="tel" id="profile-telefono" name="telefono" placeholder="+57 3XX XXX XXXX" autocomplete="tel"
                                               class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-500 text-sm">
                                        <p class="text-xs text-red-600 mt-1 error-message" id="profile-telefono-error"></p>
                                    </div>
                                    <div>
                                        <label for="profile-direccion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección (Opcional)</label>
                                        <input type="text" id="profile-direccion" name="direccion" placeholder="Tu dirección de residencia" autocomplete="street-address"
                                               class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-500 text-sm">
                                        <p class="text-xs text-red-600 mt-1 error-message" id="profile-direccion-error"></p>
                                    </div>
                                    {/* Futuro: Opción para cambiar contraseña */}
                                </div>
                                <div class="text-right border-t dark:border-gray-600 pt-4">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-md transition duration-200 text-sm flex items-center justify-center gap-1 ml-auto">
                                        <i class="bi bi-save"></i>
                                        <span class="button-text">Actualizar Datos</span>
                                        {/* Spinner (opcional) */}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>

                <!-- Columna 2 & 3: Citas e Historial -->
                <div class="lg:col-span-2 flex flex-col gap-8">

                    <!-- Sección: Citas Programadas -->
                    <section id="appointments" class="scroll-mt-20 bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                        <header class="bg-gray-50 dark:bg-gray-700 p-4 sm:p-5 border-b dark:border-gray-600 flex items-center justify-between gap-4 flex-wrap">
                            <div class="flex items-center gap-3">
                                <i class="bi bi-calendar-check text-xl text-green-600 dark:text-green-400"></i>
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Tus Citas</h2>
                            </div>
                            <button id="btn-show-schedule" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition duration-200 flex items-center justify-center gap-1 text-sm shadow-sm">
                                <i class="bi bi-calendar-plus"></i>
                                <span>Agendar Nueva</span>
                            </button>
                        </header>
                        <div class="p-4 sm:p-6">
                            {/* Lista poblada por JS -> cargarCitasUsuario */}
                            <ul id="appointments-list" class="space-y-5">
                                <li class="placeholder">Cargando citas...</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Sección: Historial Médico -->
                    <section id="medical-history" class="scroll-mt-20 bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                         <header class="bg-gray-50 dark:bg-gray-700 p-4 sm:p-5 border-b dark:border-gray-600 flex items-center gap-3">
                            <i class="bi bi-heart-pulse text-xl text-red-600 dark:text-red-400"></i>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Historial Médico</h2>
                        </header>
                        <div class="p-4 sm:p-6">
                            {/* Lista poblada por JS -> cargarHistorialUsuario */}
                            <ul id="history-list" class="space-y-4">
                                <li class="placeholder">Cargando historial...</li>
                                {/* Ejemplo de item (generado por JS):
                                <li class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700 text-sm">
                                    <div><strong>Fecha:</strong> 15 de mayo de 2024</div>
                                    <div><strong>Médico:</strong> Dr. Juan Pérez</div>
                                    <div><strong>Diagnóstico/Notas:</strong><p class="mt-1 whitespace-pre-wrap">Presión arterial ligeramente elevada. Se recomienda monitorizar.</p></div>
                                </li>
                                */}
                            </ul>
                            <div class="mt-4 pt-4 border-t dark:border-gray-600 text-center">
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                                    Funcionalidad de descarga de historial próximamente.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>

            </div> <!-- Fin Envoltura del Grid -->

        </div> <!-- Cierre del div container -->
    </main>

    <!-- --- Modal: Agendar Nueva Cita --- -->
    {/* Incluir el mismo HTML de modal que en registro.php (o mover a un include si se usa en más sitios) */}
    <div id="schedule-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900 bg-opacity-75 backdrop-blur-sm p-4 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl transform transition-all scale-95 opacity-0"
             id="schedule-modal-content">
            {/* Encabezado del Modal */}
            <div class="flex justify-between items-center p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Agendar Nueva Cita Médica
                </h3>
                <button type="button" id="close-schedule-modal" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" aria-label="Cerrar modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                    <span class="sr-only">Cerrar modal</span>
                </button>
            </div>
            {/* Cuerpo del Modal (Formulario) */}
            <form id="schedule-appointment-form" class="p-4 md:p-5" novalidate>
                <div class="grid gap-4 mb-4 grid-cols-1 sm:grid-cols-2">
                    <div>
                        <label for="modal-schedule-medico" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Médico <span class="text-red-500">*</span></label>
                        <select id="modal-schedule-medico" name="idMedico" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                            <option value="" disabled selected>Cargando médicos...</option>
                        </select>
                         <p class="text-xs text-red-600 mt-1 error-message" id="modal-schedule-medico-error"></p>
                    </div>
                    <div>
                        <label for="modal-schedule-fecha" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" id="modal-schedule-fecha" name="fecha" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" min="<?php echo date('Y-m-d'); ?>">
                         <p class="text-xs text-red-600 mt-1 error-message" id="modal-schedule-fecha-error"></p>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label for="modal-schedule-hora" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Hora <span class="text-red-500">*</span></label>
                        <input type="time" id="modal-schedule-hora" name="hora" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        <p class="text-xs text-red-600 mt-1 error-message" id="modal-schedule-hora-error"></p>
                        <p class="text-xs text-gray-500 mt-1 dark:text-gray-400">Verifique disponibilidad horaria.</p>
                    </div>
                     <div class="col-span-2">
                        <label for="modal-schedule-motivo" class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Motivo (Opcional)</label>
                        <textarea id="modal-schedule-motivo" name="motivo" rows="3" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Describa brevemente el motivo..."></textarea>
                         <p class="text-xs text-red-600 mt-1 error-message" id="modal-schedule-motivo-error"></p>
                    </div>
                </div>
                 <!-- Pie del Modal (Botón) -->
                 <div class="flex justify-end pt-4 border-t dark:border-gray-600">
                    <button type="submit" class="text-white inline-flex items-center bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-green-800">
                        <i class="bi bi-calendar-plus mr-1"></i>
                        <span class="button-text">Confirmar Cita</span>
                        {/* <!-- Spinner (opcional) --> */}
                    </button>
                 </div>
            </form>
        </div>
    </div>
    <!-- Fin Modal Agendar Cita -->

    <!-- Pie de Página -->
    <?php require_once __DIR__ . '/../app/Views/Layout/footer.php'; ?>

    <!-- --- JavaScript --- -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/js/scripts.js"></script>
    <!-- Script local para animación del modal -->
    <script>
       // Reutilizar la lógica de animación del modal de registro.php
       const scheduleModal = document.getElementById('schedule-modal');
       const scheduleModalContent = document.getElementById('schedule-modal-content');
       const openScheduleBtn = document.getElementById('btn-show-schedule');
       const closeScheduleBtn = document.getElementById('close-schedule-modal');

        const openScheduleModalAnimate = () => {
            if (!scheduleModal || !scheduleModalContent) return;
            scheduleModal.classList.remove('hidden');
            scheduleModal.classList.add('flex');
            requestAnimationFrame(() => {
                scheduleModal.style.opacity = '1';
                scheduleModalContent.style.opacity = '1';
                scheduleModalContent.style.transform = 'scale(1)';
                document.getElementById('modal-schedule-medico')?.focus();
            });
            if (typeof cargarListaMedicos === 'function') cargarListaMedicos();
        };

        const closeScheduleModalAnimate = () => {
            if (!scheduleModal || !scheduleModalContent) return;
            scheduleModal.style.opacity = '0';
            scheduleModalContent.style.opacity = '0';
            scheduleModalContent.style.transform = 'scale(0.95)';
            setTimeout(() => {
                scheduleModal.classList.add('hidden');
                scheduleModal.classList.remove('flex');
                const form = document.getElementById('schedule-appointment-form');
                if (form) {
                    form.reset();
                    form.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
                    form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
                }
            }, 300);
        };

        if (openScheduleBtn) openScheduleBtn.addEventListener('click', openScheduleModalAnimate);
        if (closeScheduleBtn) closeScheduleBtn.addEventListener('click', closeScheduleModalAnimate);
        if (scheduleModal) {
            scheduleModal.addEventListener('click', (e) => { if (e.target === scheduleModal) closeScheduleModalAnimate(); });
        }
        // Exponer función de cierre para scripts.js
        window.closeScheduleModalGlobal = closeScheduleModalAnimate;
    </script>

</body>
</html>