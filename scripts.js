// --- MediAgenda scripts.js (v3 - Con Historial, Pacientes Hoy, Notas Doctor) ---

// ========================================================================
// == 1. CONFIGURACIÓN GLOBAL Y FUNCIONES AUXILIARES GLOBALES ============
// ========================================================================
const backendUrl = 'mediagenda-backend/';
// let notificationArea = null; // Ya no es necesario

// --- Función para Mostrar Notificaciones (Modales Completos en Español) ---
function showNotification(message, type = 'info', title = null) {
    let iconType = 'info';
    let titleText = title; // Usar title si se proporciona

    if (!titleText) { // Si no se proporciona title, usar el type para determinarlo
        switch (type) {
            case 'success': titleText = 'Éxito'; break;
            case 'error': titleText = 'Error'; break;
            case 'warning': titleText = 'Advertencia'; break;
            default: titleText = 'Información'; break;
        }
    }

    Swal.fire({
        icon: iconType,
        title: titleText, // Título en español
        text: message,
        confirmButtonText: 'Aceptar', // Botón en español
        // Mantenemos las otras opciones de modal completo
        showConfirmButton: true,
        customClass: {
            // popup: 'dark:bg-gray-800', // Descomentar si necesitas estilos dark específicos
            // title: 'dark:text-white',
            // htmlContainer: 'dark:text-gray-300',
            // confirmButton: 'bg-blue-600 hover:bg-blue-700 ...', // Puedes aplicar clases Tailwind al botón si quieres
        },
    });
}

// --- Función Auxiliar para Fetch ---
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(backendUrl + url, options);
        if (!response.ok) { let eD = { m: `HTTP ${response.status}`, c: response.status }; try { const eJ = await response.json(); eD.m = eJ.message || eD.m; } catch (e) { } const err = new Error(eD.m); err.code = eD.c; console.error(`[Fetch] Error ${err.code || ''}: ${err.message} en ${url}`); throw err; }
        if (response.status === 204) return null; return await response.json();
    } catch (error) {
        if (!error.code && !(error instanceof SyntaxError)) { console.error('[Fetch] Error Red:', error); showNotification('Error comunicación.', 'error'); throw new Error('Error comunicación.'); }
        else if (error instanceof SyntaxError) { console.error('[Fetch] JSON inválido:', error); showNotification('Respuesta inválida.', 'error'); throw new Error('Respuesta inválida.'); }
        else { showNotification(`Error: ${error.message || '?'}`, 'error'); throw error; }
    }
}

// --- Helper para Rellenar Formularios ---
function populateForm(form, data) { if (!form || !data) return; for (const k in data) { const f = form.querySelector(`[name="${k}"]`); if (f) f.value = data[k] ?? ''; } }

// --- Helper para Estado de Carga de Botones ---
function setLoadingState(form, isLoading, loadingText = 'Cargando...') { if (!form) return; const b = form.querySelector('button[type="submit"]'); if (!b) return; if (isLoading) { b.dataset.originalText = b.textContent; b.disabled = true; b.textContent = loadingText; } else { b.disabled = false; b.textContent = b.dataset.originalText || 'Enviar'; } }

// --- Helper para Formatear Hora ---
function formatTime(t) { if (!t) return 'N/A'; try { return new Date(`1970-01-01T${t}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }); } catch (e) { return t; } }

// --- Helper para Formatear Fecha ---
function formatDate(d) { if (!d) return 'N/A'; try { return new Date(d + 'T00:00:00').toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }); } catch (e) { return d; } }

// --- Helper para Clases de Estado CSS ---
function getEstadoClass(st) { const c = { 'Programada': 'bg-blue-100 ...', 'Confirmada': 'bg-green-100 ...', 'Cancelada Paciente': 'bg-red-100 ...', 'Cancelada Doctor': 'bg-red-100 ...', 'Completada': 'bg-gray-100 ...', 'No Asistió': 'bg-yellow-100 ...' }; return c[st] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'; } // Clases abreviadas para brevedad


document.addEventListener('DOMContentLoaded', function () {
    console.log('[MediAgenda] Scripts inicializados (scripts.js).');

    // ========================================================================
    // == 2. REFERENCIAS A ELEMENTOS DOM Y VARIABLES ESPECÍFICAS DEL ÁMBITO ==
    // ========================================================================

    // notificationArea = document.getElementById('notification-area'); // Ya no es necesario
    const body = document.body;
    let selectedCitaIdForNotes = null; // Panel Médico


    // ========================================================================
    // == 3. FUNCIONES AUXILIARES ESPECÍFICAS DE ESTE ÁMBITO ==============
    // ========================================================================

    // --- Helper para Manejo de Formularios (Usa helpers globales) ---
    function handleFormSubmit(formSelector, phpScript, successCallback) {
        const form = document.querySelector(formSelector); if (!form) return;
        const btn = form.querySelector('button[type="submit"]'); const btnTxt = btn ? btn.textContent : 'Enviar';

        // Limpiar errores previos al añadir listener
        form.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
        form.querySelectorAll('[required]').forEach(el => el.classList.remove('border-red-500'));

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log(`%c[Submit] ${formSelector} -> ${phpScript}`, 'color:orange;');

            // Limpiar errores al intentar enviar
            form.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
            form.querySelectorAll('[required]').forEach(el => el.classList.remove('border-red-500'));

            // --- Validación básica Client-Side ---
            let isValid = true;
            // Iterar sobre los campos requeridos DENTRO del formulario actual
            form.querySelectorAll('[required]').forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    const errorElement = form.querySelector(`#${input.id}-error`); // Asume id="campo-error"
                    if (errorElement) {
                        errorElement.textContent = 'Este campo es obligatorio.';
                        errorElement.classList.remove('hidden');
                    }
                    input.classList.add('border-red-500');
                }
                // Añadir más validaciones específicas aquí si es necesario (ej. formato email)
                if (input.type === 'email' && input.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                    isValid = false;
                    const errorElement = form.querySelector(`#${input.id}-error`);
                    if (errorElement) {
                        errorElement.textContent = 'Formato de correo inválido.';
                        errorElement.classList.remove('hidden');
                    }
                    input.classList.add('border-red-500');
                }
            });

            if (!isValid) {
                showNotification('Por favor, corrija los errores en el formulario.', 'warning');
                return; // Detener si no es válido
            }
            // --- Fin Validación Client-Side ---

            const fd = new FormData(form);
            setLoadingState(form, true, 'Enviando...');
            try {
                const data = await fetchData(phpScript, { method: 'POST', body: fd });
                console.log(`[Submit] Resp ${phpScript}:`, data);
                if (data?.success) {
                    if (successCallback) successCallback(data, form);
                    else showNotification(data.message || "Éxito.", 'success');
                } else if (data) {
                    showNotification(data.message || "Error desconocido desde el servidor.", 'error'); // Mensaje por defecto más claro
                }
            } catch (err) {
                console.error(`[Submit] Catch ${formSelector}:`, err);
                // El error de comunicación ya es notificado por fetchData
            } finally {
                setLoadingState(form, false, btnTxt);
            }
        });
    }

    // --- Helpers UI (Específicos o que usan globales) ---
    // Los formatters y getEstadoClass ya son globales

    // ========================================================================
    // == 4. INICIALIZACIÓN UI COMÚN =========================================
    // ========================================================================

    // if (!notificationArea && document.body) { ... } // Ya no es necesario crear el div

    // El resto de la inicialización UI (dark mode, hamburger, scroll, parallax, fade-in) permanece aquí
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        const icon = darkModeToggle.querySelector('i');
        const applyDarkMode = (isDark) => {
            body.classList.toggle('dark', isDark);
            if (icon) {
                icon.classList.toggle('fa-sun', isDark);
                icon.classList.toggle('fa-moon', !isDark);
            }
            localStorage.theme = isDark ? 'dark' : 'light';
        };
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyDarkMode(localStorage.theme === 'dark' || (!localStorage.theme && prefersDark));
        darkModeToggle.addEventListener('click', () => applyDarkMode(!body.classList.contains('dark')));
    }

    const hamburgerBtn = document.getElementById('hamburger-menu');
    const mobileMenu = document.getElementById('mobile-menu');

    function closeMobileMenu() {
        if (mobileMenu) mobileMenu.classList.add('hidden');
        if (hamburgerBtn) {
            hamburgerBtn.classList.remove('open');
            const spans = hamburgerBtn.querySelectorAll('span');
            if (spans.length === 3) {
                spans[0].style.transform = '';
                spans[1].style.opacity = '1';
                spans[2].style.transform = '';
            }
        }
    }

    if (hamburgerBtn && mobileMenu) {
        const spans = hamburgerBtn.querySelectorAll('span');
        hamburgerBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const isOpen = hamburgerBtn.classList.toggle('open');
            if (spans.length === 3) {
                spans[0].style.transform = isOpen ? 'rotate(45deg) translate(5px, 5px)' : '';
                spans[1].style.opacity = isOpen ? '0' : '1';
                spans[2].style.transform = isOpen ? 'rotate(-45deg) translate(5px, -5px)' : '';
            }
        });
        mobileMenu.querySelectorAll('a, button').forEach(link => {
            link.addEventListener('click', () => {
                // Cerrar menú si es un enlace de navegación o un botón que cambia la pestaña (si aplica)
                if (link.tagName === 'A' || link.hasAttribute('data-target')) {
                    closeMobileMenu();
                }
            });
        });
    } // Fin del if (hamburgerBtn && mobileMenu)

    // Scroll suave para enlaces ancla (#...)
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href && href.length > 1 && href !== '#') {
                try {
                    const targetElement = document.querySelector(href);
                    if (targetElement) {
                        e.preventDefault();
                        targetElement.scrollIntoView({ behavior: 'smooth' });
                    }
                } catch (err) {
                    console.error(`Error de scroll suave para ${href}:`, err);
                }
            }
        });
    });

    // Efecto Parallax (sin cambios)
    const parallaxElems = document.querySelectorAll('.parallax, .parallax-doctors, .parallax-testimonials');
    if (parallaxElems.length > 0) {
        window.addEventListener('scroll', () => {
            let offset = window.pageYOffset;
            parallaxElems.forEach(elem => {
                if (elem.getBoundingClientRect) { // Asegurar que es un elemento válido
                    let speed = 0.5; // Ajusta la velocidad del parallax
                    elem.style.backgroundPositionY = (offset - elem.offsetTop) * speed + 'px';
                }
            });
        }, { passive: true }); // Optimización del listener de scroll
    }

    // Efecto Fade-in (sin cambios)
    const fadeInElems = document.querySelectorAll('.fade-in');
    if (fadeInElems.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target); // Dejar de observar una vez visible
                }
            });
        }, { threshold: 0.1 }); // El elemento debe ser visible al 10%
        fadeInElems.forEach(el => observer.observe(el));
    }

    // --- Lógica para mostrar notificación de logout --- (Se ejecuta en todas las páginas)
    console.log("[Logout Check] Verificando parámetros de URL..."); // <-- LOG 1
    const currentSearchParams = window.location.search;
    console.log("[Logout Check] window.location.search:", currentSearchParams); // <-- LOG 2
    const urlParams = new URLSearchParams(currentSearchParams);
    if (urlParams.has('logout') && urlParams.get('logout') === 'success') {
        console.log("[Logout Check] Parámetro logout=success detectado."); // <-- LOG 3
        // Usar showNotification para mantener consistencia
        console.log("[Logout Check] Llamando a showNotification..."); // <-- LOG 4
        showNotification('Has cerrado sesión correctamente.', 'success', 'Sesión Cerrada');
        console.log("[Logout Check] showNotification llamada."); // <-- LOG 5
        // Limpiar el parámetro de la URL para evitar que se muestre de nuevo al recargar
        if (history.replaceState) {
            const cleanUrl = window.location.pathname + window.location.hash; // Mantiene el hash si existe
            history.replaceState(null, '', cleanUrl);
        }
    }

    // ========================================================================
    // == 5. LÓGICA ESPECÍFICA POR PÁGINA (ROUTING) ==========================
    // ========================================================================

    const currentPath = window.location.pathname;
    console.log(`[Routing] Path actual: ${currentPath}`);

    // --- Lógica para: registro.php ---
    if (currentPath.includes('registro.php')) {
        console.log('[Routing] Ejecutando lógica para registro.php');
        const tabs = document.querySelectorAll('.tab-button'); const tabContents = document.querySelectorAll('.tab-content'); if (tabs.length > 0 && tabContents.length > 0) { let showTab = (id) => { let f = 0; tabContents.forEach(c => c.style.display = (c.id === id) ? (f = 1, 'block') : 'none'); tabs.forEach(b => { const a = b.getAttribute('data-target') === id; b.classList.toggle('text-blue-600', a); b.classList.toggle('dark:text-blue-400', a); b.classList.toggle('border-blue-500', a); b.classList.toggle('text-gray-600', !a); b.classList.toggle('dark:text-gray-400', !a); b.classList.toggle('border-transparent', !a); }); return f; }; tabs.forEach(b => { b.addEventListener('click', (e) => { e.preventDefault(); const t = b.getAttribute('data-target'); if (t && typeof showTab === 'function') showTab(t); if (mobileMenu && !mobileMenu.classList.contains('hidden') && mobileMenu.contains(b)) closeMobileMenu(); }); }); let iT = 'login'; if (window.location.hash && document.getElementById(window.location.hash.substring(1))) iT = window.location.hash.substring(1); if (typeof showTab === 'function' && !showTab(iT) && tabContents.length > 0) showTab(tabContents[0].id); }
        handleFormSubmit('#patient-register-form', 'registrar_paciente.php', (d, f) => { if (typeof showTab === 'function') showTab('login'); f.reset(); showNotification(d.message || "Registro exitoso.", 'success'); });
        handleFormSubmit('#doctor-register-form', 'registrar_medico.php', (d, f) => { if (typeof showTab === 'function') showTab('login'); f.reset(); showNotification(d.message || "Registro exitoso.", 'success'); });
        handleFormSubmit('#login-form', 'login.php', (d) => {
            if (d.success && d.rol) {
                const r = {
                    'paciente': 'perfil-usuario.php',
                    'medico': 'perfil-doctores.php',
                    'admin': 'panel-admin-sistema.php',
                    'administrador': 'panel-admin-sistema.php'
                };
                const u = r[d.rol.toLowerCase()] || 'index.php';
                if (!r[d.rol.toLowerCase()]) {
                    showNotification("Rol desconocido. Redirigiendo al inicio.", 'warning');
                    setTimeout(() => window.location.href = u, 1500);
                } else {
                    window.location.href = u;
                }
            }
        });

        // --- Lógica para Modal "Olvidó Contraseña" --- (En registro.php)
        const btnForgotPassword = document.getElementById('btn-forgot-password');
        const forgotPasswordModal = document.getElementById('forgot-password-modal');
        const closeForgotModalBtn = document.getElementById('close-forgot-modal');
        const forgotPasswordForm = document.getElementById('forgot-password-form');

        if (btnForgotPassword && forgotPasswordModal && closeForgotModalBtn && forgotPasswordForm) {
            console.log("[Forgot Password] Listeners modal añadidos.");
            btnForgotPassword.addEventListener('click', () => {
                forgotPasswordModal.classList.remove('hidden');
            });
            closeForgotModalBtn.addEventListener('click', () => {
                forgotPasswordModal.classList.add('hidden');
            });
            // Cerrar modal si se hace clic fuera de ella
            forgotPasswordModal.addEventListener('click', (event) => {
                if (event.target === forgotPasswordModal) {
                    forgotPasswordModal.classList.add('hidden');
                }
            });

            // Manejar envío del form de la modal
            handleFormSubmit('#forgot-password-form', 'solicitar_reset.php', (data, form) => {
                // Independientemente del resultado del backend (por seguridad), mostrar mensaje genérico
                forgotPasswordModal.classList.add('hidden'); // Ocultar modal
                form.reset(); // Limpiar el campo de email
                showNotification(
                    'Si existe una cuenta asociada a ese correo, recibirás un enlace para restablecer tu contraseña en breve.',
                    'info', // Usar 'info' o 'success' aquí es debatible, info es más neutral
                    'Solicitud Enviada'
                );
            });
        } else {
            console.log("[Forgot Password] Elementos modal no encontrados en esta página.");
        }

    }

    // --- Lógica para: perfil-usuario.php ---
    else if (currentPath.includes('perfil-usuario.php')) {
        console.log('[Routing] Ejecutando lógica para perfil-usuario.php');
        cargarDatosPerfilUsuario();
        cargarCitasUsuario();
        cargarListaMedicos();
        // cargarHistorialUsuario(); // <-- Descomentar si la función está implementada
        handleFormSubmit('#update-profile-form', 'actualizar_perfil.php', (d) => { showNotification(d.message || "Perfil actualizado.", 'success'); });
        handleFormSubmit('#schedule-appointment-form', 'programar_cita.php', (d, f) => {
            f.reset();
            // const s = f.querySelector('#schedule-medico'); // Ya no es necesario resetear select aquí
            // if (s) s.selectedIndex = 0; 
            cargarCitasUsuario();
            closeScheduleModal(); // Cerrar modal al agendar con éxito
            showNotification(d.message || "Cita programada.", 'success');
        });
        attachCitaActionListeners('#appointments-list');

        // Listeners para mostrar/cerrar el modal de agendar cita
        const btnShowSchedule = document.getElementById('btn-show-schedule');
        const linkShowSchedule = document.querySelector('a[href="#schedule"]');
        const scheduleModal = document.getElementById('schedule-modal');
        const btnCloseSchedule = document.getElementById('close-schedule-modal');

        if (btnShowSchedule) {
            btnShowSchedule.addEventListener('click', (e) => {
                e.preventDefault();
                openScheduleModal();
            });
        }
        if (linkShowSchedule) {
            linkShowSchedule.addEventListener('click', (e) => {
                e.preventDefault();
                openScheduleModal();
            });
        }
        if (btnCloseSchedule) {
            btnCloseSchedule.addEventListener('click', closeScheduleModal);
        }
        if (scheduleModal) {
            // Cerrar modal si se hace clic fuera del contenido (en el overlay)
            scheduleModal.addEventListener('click', (e) => {
                if (e.target === scheduleModal) { // Solo si el clic es directo en el overlay
                    closeScheduleModal();
                }
            });
        }
    }

    // --- Lógica para: perfil-doctores.php ---
    else if (currentPath.includes('perfil-doctores.php')) {
        console.log('[Routing] Ejecutando lógica para perfil-doctores.php');
        cargarDatosPerfilMedico();
        cargarCitasMedico(); // Esta función ya llama a attachCitaActionListeners internamente
        // cargarPacientesHoy(); // <-- Descomentar si la función está implementada
        // handleFormSubmit('#consulta-notes-form', 'guardar_notas_consulta.php', (d,f)=>{...}); // <-- Descomentar si está implementado
        // Ya no es necesario llamar attachCitaActionListeners aquí directamente
        // attachCitaActionListeners('#appointments-list-doctor'); 
        // attachCitaActionListeners('#patients-list-doctor'); // <-- Descomentar si está implementado
    }

    // --- Lógica para Paneles Admin (Futuro) ---
    // else if (currentPath.includes('panel-admin-sistema.php')) { ... } // <-- CORREGIDO .php
    else {
        console.log('[Routing] Sin lógica específica para esta página en scripts.js.');
    }

    // ========================================================================
    // == 6. DEFINICIÓN DE FUNCIONES ESPECÍFICAS DE PANELES ===================
    // ========================================================================

    /**
     * Abre el modal para agendar una cita.
     */
    function openScheduleModal() {
        const modal = document.getElementById('schedule-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex'); // Usar flex para centrar el contenido
            // Opcional: resetear el formulario al abrir
            // const form = modal.querySelector('#schedule-appointment-form');
            // if (form) form.reset(); 
            // Opcional: cargar médicos si aún no se han cargado
            cargarListaMedicos(); // Asegurarse de que los médicos estén listos
            // Opcional: enfocar el primer campo
            const firstInput = modal.querySelector('select, input');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 50); // Pequeño delay para asegurar que esté visible
            }
        } else {
            console.error("Modal #schedule-modal no encontrado.");
        }
    }

    /**
     * Cierra el modal para agendar una cita.
     */
    function closeScheduleModal() {
        const modal = document.getElementById('schedule-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    /**
     * Crea el elemento LI para mostrar una cita en el panel del paciente.
     * @param {object} cita - El objeto de datos de la cita.
     * @returns {HTMLLIElement} El elemento LI creado.
     */
    function crearElementoCitaPaciente(c) {
        const li = document.createElement('li');
        // Añadir un borde izquierdo coloreado según el estado
        const estadoClasses = {
            'Programada': 'border-l-4 border-blue-500',
            'Confirmada': 'border-l-4 border-green-500',
            'Cancelada Paciente': 'border-l-4 border-red-500',
            'Cancelada Doctor': 'border-l-4 border-red-500',
            'Completada': 'border-l-4 border-gray-500',
            'No Asistió': 'border-l-4 border-yellow-500'
        };
        const estadoBorde = estadoClasses[c.estado] || 'border-l-4 border-gray-300';
        li.className = `mb-4 p-3 border rounded-md dark:border-gray-700 bg-gray-50 dark:bg-gray-800 ${estadoBorde} shadow-sm`;
        li.dataset.citaId = c.idCita;
        const estadoBadgeClasses = { // Clases específicas para la insignia de estado
            'Programada': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'Confirmada': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'Cancelada Paciente': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'Cancelada Doctor': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'Completada': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'No Asistió': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
        };
        const eC = estadoBadgeClasses[c.estado] || 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200'; // Insignia por defecto más visible
        const hF = formatTime(c.hora);
        li.innerHTML = `
            <div class="flex justify-between items-center mb-2 flex-wrap gap-x-2">
                <strong class="text-blue-600 dark:text-blue-400 text-lg"><i class="bi bi-person-badge mr-1"></i> Dr. ${c.nombreMedico || 'N/A'}</strong>
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full ${eC}">${c.estado || '?'}</span>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1"><i class="bi bi-tag mr-1"></i> ${c.especialidadMedico || 'General'}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2"><i class="bi bi-calendar-event mr-1"></i> ${formatDate(c.fecha)} <span class="ml-2"><i class="bi bi-clock mr-1"></i> ${hF}</span></div>
            ${c.motivo ? `<div class="text-sm mt-2 pt-2 border-t border-gray-200 dark:border-gray-700"><strong class="text-gray-700 dark:text-gray-300">Motivo:</strong><p class="mt-1 text-gray-600 dark:text-gray-400">${c.motivo}</p></div>` : ''}
            <div class="mt-3 flex gap-2">
                ${(c.estado === 'Programada' || c.estado === 'Confirmada') ? `<button data-action="cancelar-paciente" data-id="${c.idCita}" class="text-xs bg-red-500 hover:bg-red-600 text-white font-medium py-1 px-3 rounded-md transition duration-150 appointment-action-button"><i class="bi bi-x-circle mr-1"></i> Cancelar</button>` : ''}
            </div>`;
        return li;
    }
    function crearElementoCitaMedico(c) {
        const li = document.createElement('li');
        li.className = 'mb-4 p-3 border rounded-md dark:border-gray-700 bg-gray-50 dark:bg-gray-800';
        li.dataset.citaId = c.idCita;

        // Clases para la insignia de estado
        const estadoBadgeClasses = {
            'Programada': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'Confirmada': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'Cancelada Paciente': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'Cancelada Doctor': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'Completada': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'No Asistió': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
        };

        const estadoClase = estadoBadgeClasses[c.estado] || 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200';
        const hoy = new Date().toISOString().split('T')[0];

        // Destacar las citas de hoy
        if (c.fecha === hoy) {
            li.classList.add('border-l-4', 'border-blue-500');
        }

        li.innerHTML = `
            <div class="flex justify-between items-start mb-1 flex-wrap gap-x-2">
                <strong class="text-purple-600 dark:text-purple-400">P: ${c.nombrePaciente || 'N/A'}</strong>
                <span class="text-xs font-semibold px-2 py-0.5 rounded ${estadoClase}">${c.estado || '?'}</span>
            </div>
            
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                <i class="bi bi-telephone mr-1"></i> ${c.telefonoPaciente || '-'}
            </div>
            
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                <i class="bi bi-calendar-event mr-1"></i> ${formatDate(c.fecha)} 
                <span class="ml-2"><i class="bi bi-clock mr-1"></i> ${formatTime(c.hora)}</span>
            </div>
            
            ${c.motivo ? `
                <div class="text-sm mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <strong class="text-gray-700 dark:text-gray-300">Motivo:</strong>
                    <p class="mt-1 text-gray-600 dark:text-gray-400">${c.motivo}</p>
                </div>` : ''}
                
            <div class="mt-3 flex flex-wrap gap-2">
                ${(c.estado === 'Programada') ?
                `<button data-action="confirmar" data-id="${c.idCita}" class="text-xs bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 rounded transition duration-200">
                        <i class="bi bi-check-circle mr-1"></i> Confirmar
                    </button>` : ''}
                    
                ${(c.estado !== 'Cancelada Paciente' && c.estado !== 'Cancelada Doctor' && c.estado !== 'Completada') ?
                `<button data-action="cancelar-doctor" data-id="${c.idCita}" class="text-xs bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded transition duration-200">
                        <i class="bi bi-x-circle mr-1"></i> Cancelar
                    </button>` : ''}
                    
                ${(c.estado === 'Confirmada') ?
                `<button data-action="completar" data-id="${c.idCita}" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded transition duration-200">
                        <i class="bi bi-check-square mr-1"></i> Completada
                    </button>` : ''}
                    
                ${(c.estado !== 'Cancelada Paciente' && c.estado !== 'Cancelada Doctor' && c.estado !== 'Completada') ?
                `<button data-action="cargar-notas" data-id="${c.idCita}" class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded transition duration-200">
                        <i class="bi bi-clipboard-plus mr-1"></i> Cargar p/ Notas
                    </button>` : ''}
            </div>
        `;

        return li;
    }

    // --- Funciones Panel Paciente ---
    async function cargarDatosPerfilUsuario() {
        console.log("[Funciones Panel] Cargando datos del perfil del usuario..."); // LOG INICIO
        const form = document.querySelector('#update-profile-form');
        if (!form) {
            console.error("[cargarDatosPerfilUsuario] Error: Formulario #update-profile-form no encontrado.");
            return;
        }
        setLoadingState(form, true); // Poner estado de carga mientras se obtienen los datos
        try {
            console.log("[cargarDatosPerfilUsuario] Realizando fetch a obtener_perfil.php..."); // LOG FETCH
            const data = await fetchData('obtener_perfil.php');
            console.log("[cargarDatosPerfilUsuario] Datos recibidos:", data); // LOG RESPUESTA

            if (data?.success && data.perfil) {
                console.log("[cargarDatosPerfilUsuario] Éxito. Rellenando formulario con:", data.perfil); // LOG ÉXITO Y DATOS
                populateForm(form, data.perfil);
            } else if (data && data.message?.toLowerCase().includes("sesión no iniciada")) {
                console.warn("[cargarDatosPerfilUsuario] Sesión no iniciada detectada. Redirigiendo...");
                // Opcional: Mostrar notificación antes de redirigir
                // showNotification("Tu sesión ha expirado. Por favor, inicia sesión de nuevo.", "warning");
                setTimeout(() => window.location.href = 'registro.php#login', 1500); // Dar tiempo a ver mensaje
            } else {
                console.error("[cargarDatosPerfilUsuario] Error en la respuesta del backend o formato inválido.", data); // LOG ERROR RESPUESTA
                // Opcional: Mostrar un error en el propio formulario
                // form.innerHTML = '<p class="text-red-500">Error al cargar los datos del perfil.</p>';
            }
        } catch (error) {
            console.error("[cargarDatosPerfilUsuario] Error en la comunicación (fetch):"); // LOG ERROR FETCH
            // Opcional: Mostrar un error en el formulario
            // form.innerHTML = '<p class="text-red-500">Error de comunicación al cargar el perfil.</p>';
        } finally {
            setLoadingState(form, false, 'Actualizar Datos'); // Quitar estado de carga
        }
    }
    async function cargarCitasUsuario() {
        console.log("[Funciones Panel] Cargando citas del usuario..."); // LOG INICIO
        const listElement = document.querySelector('#appointments-list');
        if (!listElement) {
            console.error("[cargarCitasUsuario] Error: Elemento #appointments-list no encontrado.");
            return;
        }
        listElement.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando citas...</li>';
        try {
            console.log("[cargarCitasUsuario] Realizando fetch a obtener_citas.php?rol=paciente..."); // LOG FETCH
            const data = await fetchData('obtener_citas.php?rol=paciente');
            console.log("[cargarCitasUsuario] Datos recibidos:", data); // LOG RESPUESTA

            listElement.innerHTML = ''; // Limpiar carga

            if (data?.success && Array.isArray(data.citas)) {
                console.log(`[cargarCitasUsuario] Éxito. Procesando ${data.citas.length} citas.`); // LOG ÉXITO
                if (data.citas.length === 0) {
                    listElement.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">No tiene citas programadas.</li>';
                } else {
                    data.citas.forEach((cita, index) => {
                        try {
                            console.log(`[cargarCitasUsuario] Creando elemento para cita ${index + 1}:`, cita); // LOG CITA
                            const li = crearElementoCitaPaciente(cita);
                            listElement.appendChild(li);
                            console.log(`[cargarCitasUsuario] Elemento añadido para cita ${index + 1}.`); // LOG AÑADIDO
                        } catch (error) {
                            console.error(`[cargarCitasUsuario] Error al crear/añadir elemento para cita ${index + 1}:`, error, cita);
                            const errorLi = document.createElement('li');
                            errorLi.className = 'placeholder text-red-500';
                            errorLi.textContent = `Error al mostrar cita ID ${cita.idCita || '(desconocido)'}.`;
                            listElement.appendChild(errorLi);
                        }
                    });
                    attachCitaActionListeners('#appointments-list');
                }
            } else {
                console.error("[cargarCitasUsuario] Error en la respuesta del backend o formato inválido.", data); // LOG ERROR RESPUESTA
                listElement.innerHTML = `<li class="placeholder text-red-500">Error al cargar citas: ${data?.message || 'Formato de respuesta incorrecto'}.</li>`;
            }
        } catch (error) {
            console.error("[cargarCitasUsuario] Error en la comunicación (fetch):"); // LOG ERROR FETCH
            listElement.innerHTML = '<li class="placeholder text-red-500">Error de comunicación al cargar citas.</li>';
        }
    }
    async function cargarListaMedicos() {
        console.log("[Funciones Panel] Cargando lista de médicos..."); // LOG INICIO
        const sel = document.getElementById('modal-schedule-medico');
        if (!sel) {
            console.error("[cargarListaMedicos] Error: Elemento #modal-schedule-medico no encontrado.");
            return;
        }
        sel.disabled = true;
        sel.innerHTML = '<option value="" disabled selected>Cargando médicos...</option>';
        try {
            console.log("[cargarListaMedicos] Realizando fetch a obtener_medicos.php..."); // LOG FETCH
            const data = await fetchData('obtener_medicos.php');
            console.log("[cargarListaMedicos] Datos recibidos:", data); // LOG RESPUESTA
            sel.innerHTML = '<option value="" disabled selected>Seleccione un médico...</option>'; // Resetear con placeholder
            if (data?.success && Array.isArray(data.medicos)) {
                console.log(`[cargarListaMedicos] Éxito. Procesando ${data.medicos.length} médicos.`); // LOG ÉXITO
                if (data.medicos.length > 0) {
                    data.medicos.forEach(m => {
                        console.log(`[cargarListaMedicos] Añadiendo médico:`, m); // LOG MÉDICO
                        sel.add(new Option(`${m.nombre} - ${m.especialidad || 'General'}`, m.idMedico));
                    });
                    sel.disabled = false;
                } else {
                    sel.innerHTML = '<option value="" disabled>No hay médicos disponibles</option>';
                }
            } else {
                console.error("[cargarListaMedicos] Error en la respuesta del backend o formato inválido.", data); // LOG ERROR RESPUESTA
                sel.innerHTML = '<option value="" disabled>Error al cargar médicos</option>';
            }
        } catch (error) {
            console.error("[cargarListaMedicos] Error en la comunicación (fetch):"); // LOG ERROR FETCH
            sel.innerHTML = '<option value="" disabled>Error de comunicación</option>';
        }
    }
    async function cargarPacientesHoy() { // <-- NUEVA FUNCIÓN IMPLEMENTADA
        console.log("[Funciones Panel] Cargando pacientes de hoy...");
        const listElement = document.querySelector('#patients-list-doctor');
        if (!listElement) { console.error("Elemento #patients-list-doctor no encontrado."); return; }
        listElement.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando pacientes...</li>';
        try {
            const data = await fetchData('obtener_pacientes_hoy.php');
            listElement.innerHTML = ''; // Limpiar
            if (data?.success && data.pacientes) {
                if (data.pacientes.length === 0) {
                    listElement.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">No hay pacientes agendados para hoy.</li>';
                } else {
                    data.pacientes.forEach(paciente => {
                        const li = document.createElement('li');
                        li.className = 'mb-2 pb-2 border-b dark:border-gray-700 flex justify-between items-center text-sm';
                        // Añadir clase de estado
                        const estadoClass = getEstadoClass(paciente.estado);
                        li.innerHTML = `
                            <span>
                                <i class="bi bi-clock mr-1"></i> ${formatTime(paciente.hora)} -
                                <strong class="ml-1">${paciente.nombrePaciente || 'N/A'}</strong>
                                <span class="text-xs ml-2 px-1.5 py-0.5 rounded ${estadoClass}">${paciente.estado || 'N/A'}</span>
                            </span>
                            ${(paciente.estado === 'Confirmada' || paciente.estado === 'Programada') ? `<button data-action="cargar-notas" data-id="${paciente.idCita}" class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded">Cargar p/ Notas</button>` : ''}
                        `;
                        listElement.appendChild(li);
                    });
                    attachCitaActionListeners('#patients-list-doctor'); // Adjuntar listeners a los nuevos botones
                }
            } else { listElement.innerHTML = `<li class="placeholder text-red-500">Error al cargar pacientes: ${data?.message || '?'}.</li>`; }
        } catch (error) { listElement.innerHTML = '<li class="placeholder text-red-500">Error de comunicación.</li>'; }
    }
    async function guardarNotasConsulta(event) { // <-- NUEVA FUNCIÓN IMPLEMENTADA
        event.preventDefault();
        console.log("%c[Submit] Guardando notas de consulta...", 'color: purple; font-weight: bold;');
        const form = event.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');

        if (!selectedCitaIdForNotes) { showNotification("Seleccione una cita ('Cargar p/ Notas') primero.", 'warning'); return; }
        formData.append('idCitaActual', selectedCitaIdForNotes);
        setLoadingState(form, true, 'Guardando...');

        try {
            const data = await fetchData('guardar_notas_consulta.php', { method: 'POST', body: formData });
            if (data?.success) {
                showNotification(data.message || "Notas guardadas.", 'success'); form.reset(); selectedCitaIdForNotes = null;
                // Podrías querer recargar las citas para ver si afecta algo
                cargarCitasMedico();
            } // Error ya notificado por fetchData
        } catch (error) { /* Ya notificado */ }
        finally { setLoadingState(form, false, 'Guardar Notas'); }
    }

    // ========================================================================
    // == 7. MANEJADORES DE EVENTOS DELEGADOS / FUNCIONES GLOBALES ==========
    // ========================================================================

    async function cambiarEstadoCita(idCita, nuevoEstado) {
        console.log(`%c[Acción Cita] Solicitando: ${nuevoEstado} para cita ${idCita}`, 'color: teal;');

        // Obtener la ruta actual para saber qué vista actualizar después
        const currentPath = window.location.pathname;

        // Configurar opciones según el tipo de estado
        let confirmOptions = {
            title: `¿Cambiar estado de la cita?`,
            text: `La cita pasará a estado "${nuevoEstado}"`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        };

        // Personalizar botones y mensajes según el tipo de acción
        if (nuevoEstado === 'Confirmada') {
            confirmOptions.title = '¿Confirmar esta cita?';
            confirmOptions.text = 'El paciente será notificado de la confirmación';
            confirmOptions.icon = 'info';
            confirmOptions.confirmButtonColor = '#10B981'; // Verde
        }
        else if (nuevoEstado.includes('Cancelada')) {
            confirmOptions.title = '¿Cancelar esta cita?';
            confirmOptions.text = 'Esta acción no se puede deshacer';
            confirmOptions.icon = 'warning';
            confirmOptions.confirmButtonColor = '#EF4444'; // Rojo
        }
        else if (nuevoEstado === 'Completada') {
            confirmOptions.title = '¿Marcar cita como completada?';
            confirmOptions.text = 'Se registrará la cita como atendida';
            confirmOptions.icon = 'info';
            confirmOptions.confirmButtonColor = '#6366F1'; // Índigo
        }

        // Mostrar el modal de confirmación
        console.log('[cambiarEstadoCita] Mostrando SweetAlert de confirmación...'); // LOG
        const result = await Swal.fire(confirmOptions);
        console.log('[cambiarEstadoCita] Resultado de SweetAlert:', result); // LOG
        if (!result.isConfirmed) {
            console.log('[cambiarEstadoCita] Confirmación cancelada por el usuario.'); // LOG
            return;
        }

        console.log('[cambiarEstadoCita] Confirmación aceptada. Procediendo con fetch...'); // LOG
        try {
            const fd = new FormData();
            fd.append('idCita', idCita);
            fd.append('nuevoEstado', nuevoEstado);

            // Mostrar indicador de carga
            console.log('[cambiarEstadoCita] Mostrando SweetAlert de carga...'); // LOG
            Swal.fire({
                title: 'Actualizando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            console.log('[cambiarEstadoCita] Realizando fetch a cambiar_estado_cita.php...'); // LOG
            const data = await fetchData('cambiar_estado_cita.php', { method: 'POST', body: fd });
            console.log('[cambiarEstadoCita] Respuesta del fetch:', data); // LOG

            if (data?.success) {
                console.log('[cambiarEstadoCita] Fetch exitoso. Mostrando SweetAlert de éxito.'); // LOG
                Swal.fire({
                    title: '¡Actualizado!',
                    text: data.message || "Estado de la cita actualizado correctamente",
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true
                });

                // Recargar las citas según la página actual
                console.log('[cambiarEstadoCita] Recargando citas para:', currentPath); // LOG
                if (currentPath.includes('perfil-doctores.php')) cargarCitasMedico();
                if (currentPath.includes('perfil-usuario.php')) cargarCitasUsuario();
            }
        } catch (error) {
            console.error('[cambiarEstadoCita] Error durante el fetch o proceso:', error); // LOG
            Swal.fire({
                title: 'Error',
                text: 'No se pudo actualizar el estado de la cita',
                icon: 'error'
            });
        }
    }

    function attachCitaActionListeners(containerSelector) {
        const container = document.querySelector(containerSelector); if (!container) return;
        const currentListener = container.handleCitaClick; // Intentar obtener listener previo
        if (currentListener) container.removeEventListener('click', currentListener); // Limpiar si existe

        const handleCitaActionClick = (event) => { /* ... (código interno sin cambios) ... */
            const button = event.target.closest('button[data-action]'); if (!button || button.disabled) return;
            const action = button.dataset.action; const idCita = button.dataset.id; console.log(`%c[Click Acción Cita] ${containerSelector}: Acción=${action}, ID=${idCita}`, 'color: darkcyan;');
            if (!idCita) { console.error("Botón sin data-id!"); return; }

            // Guardar estado original y deshabilitar
            const originalDisabledState = button.disabled;
            button.disabled = true;
            // Ya no cambiamos el texto a "..." para no perder el icono
            // const originalText = button.textContent; button.textContent = '...'; 

            const executeAndEnable = async (fn) => {
                try {
                    await fn();
                } catch (err) {
                    // El error debería ser manejado y notificado dentro de fn si es necesario
                    console.error(`[executeAndEnable] Error en acción ${action} para cita ${idCita}:`, err);
                } finally {
                    // Rehabilitar botón solo si no era ya `cargar-notas` (que se rehabilita inmediatamente)
                    if (action !== 'cargar-notas') {
                        button.disabled = originalDisabledState;
                    }
                    // Ya no restauramos el texto porque no lo cambiamos
                    // button.textContent = originalText;
                }
            };
            switch (action) {
                case 'confirmar': executeAndEnable(() => cambiarEstadoCita(idCita, 'Confirmada')); break;
                case 'cancelar-doctor': executeAndEnable(() => cambiarEstadoCita(idCita, 'Cancelada Doctor')); break;
                case 'cancelar-paciente': executeAndEnable(() => cambiarEstadoCita(idCita, 'Cancelada Paciente')); break;
                case 'completar': executeAndEnable(() => cambiarEstadoCita(idCita, 'Completada')); break;
                case 'cargar-notas':
                    selectedCitaIdForNotes = idCita;
                    // Reemplazamos la notificación simple por un SweetAlert2 más atractivo
                    Swal.fire({
                        title: 'Cita seleccionada',
                        text: `Cita #${idCita} lista para añadir notas médicas`,
                        icon: 'info',
                        timer: 1500,
                        timerProgressBar: true,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false
                    });

                    document.querySelector('#consulta-notes-form textarea')?.focus();
                    container.querySelectorAll('li[data-cita-id]').forEach(li => li.classList.remove('bg-blue-100', 'dark:bg-blue-900'));
                    button.closest('li[data-cita-id]')?.classList.add('bg-blue-100', 'dark:bg-blue-900');
                    button.disabled = false; // Rehabilitar inmediato
                    break;
                default: console.warn(`Acción desconocida: ${action}`); showNotification(`Acción no implementada: ${action}`, 'warning'); button.disabled = false;
            }
        };
        container.addEventListener('click', handleCitaActionClick);
        container.handleCitaClick = handleCitaActionClick; // Guardar referencia para posible limpieza futura
        console.log(`[Listeners] Listener delegado adjuntado a ${containerSelector}`);
    }

    // --- Funciones Panel Médico ---
    async function cargarDatosPerfilMedico() {
        console.log("[Funciones Panel] Cargando datos del perfil del médico..."); // LOG INICIO
        const form = document.querySelector('#doctor-profile-form');
        if (!form) {
            console.error("[cargarDatosPerfilMedico] Error: Formulario #doctor-profile-form no encontrado.");
            return;
        }
        setLoadingState(form, true);
        try {
            console.log("[cargarDatosPerfilMedico] Realizando fetch a obtener_perfil.php..."); // LOG FETCH
            const data = await fetchData('obtener_perfil.php');
            console.log("[cargarDatosPerfilMedico] Datos recibidos:", data); // LOG RESPUESTA
            if (data?.success && data.perfil) {
                console.log("[cargarDatosPerfilMedico] Éxito. Rellenando formulario con:", data.perfil); // LOG DATOS
                populateForm(form, data.perfil);
            } else if (data && data.message?.toLowerCase().includes("sesión no iniciada")) {
                console.warn("[cargarDatosPerfilMedico] Sesión no iniciada. Redirigiendo...");
                setTimeout(() => window.location.href = 'registro.php#login', 1500);
            } else {
                console.error("[cargarDatosPerfilMedico] Error en la respuesta del backend.", data);
            }
        } catch (error) {
            console.error("[cargarDatosPerfilMedico] Error en fetch:", error);
        } finally {
            setLoadingState(form, false, 'Actualizar Perfil');
        }
    }
    async function cargarCitasMedico() {
        console.log("[Funciones Panel] Cargando citas del médico..."); // LOG INICIO
        const el = document.querySelector('#appointments-list-doctor');
        if (!el) {
            console.error("[cargarCitasMedico] Error: Elemento #appointments-list-doctor no encontrado.");
            return;
        }
        el.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando citas...</li>';
        try {
            console.log("[cargarCitasMedico] Realizando fetch a obtener_citas.php?rol=medico..."); // LOG FETCH
            const data = await fetchData('obtener_citas.php?rol=medico');
            console.log("[cargarCitasMedico] Datos recibidos:", data); // LOG RESPUESTA
            el.innerHTML = ''; // Limpiar carga
            if (data?.success && Array.isArray(data.citas)) {
                console.log(`[cargarCitasMedico] Éxito. Procesando ${data.citas.length} citas.`); // LOG ÉXITO
                if (data.citas.length === 0) {
                    el.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">No hay citas programadas.</li>';
                } else {
                    data.citas.forEach(c => el.appendChild(crearElementoCitaMedico(c)));
                    attachCitaActionListeners('#appointments-list-doctor');
                }
            } else {
                console.error("[cargarCitasMedico] Error en la respuesta del backend.", data);
                el.innerHTML = `<li class="placeholder text-red-500">Error al cargar citas: ${data?.message || '?'}.</li>`;
            }
        } catch (error) {
            console.error("[cargarCitasMedico] Error en fetch:", error);
            el.innerHTML = '<li class="placeholder text-red-500">Error de comunicación al cargar citas.</li>';
        }
    }

    // ========================================================================
    // == 8. FINALIZACIÓN =====================================================
    // ========================================================================
    console.log('[MediAgenda] Inicialización del script finalizada.');

}); // Fin de DOMContentLoaded