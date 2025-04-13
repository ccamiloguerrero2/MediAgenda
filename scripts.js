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
        handleFormSubmit('#schedule-appointment-form', 'programar_cita.php', (d, f) => { f.reset(); const s = f.querySelector('#schedule-medico'); if (s) s.selectedIndex = 0; cargarCitasUsuario(); showNotification(d.message || "Cita programada.", 'success'); });
        attachCitaActionListeners('#appointments-list');
    }

    // --- Lógica para: perfil-doctores.php ---
    else if (currentPath.includes('perfil-doctores.php')) {
        console.log('[Routing] Ejecutando lógica para perfil-doctores.php');
        cargarDatosPerfilMedico();
        cargarCitasMedico();
        // cargarPacientesHoy(); // <-- Descomentar si la función está implementada
        // handleFormSubmit('#consulta-notes-form', 'guardar_notas_consulta.php', (d,f)=>{...}); // <-- Descomentar si está implementado
        attachCitaActionListeners('#appointments-list-doctor');
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
        const eC = getEstadoClass(c.estado);
        const hF = formatTime(c.hora);
        const hoy = new Date().toISOString().split('T')[0];
        if (c.fecha === hoy) li.classList.add('border-l-4', 'border-blue-500');
        li.innerHTML = `<div class="flex justify-between items-start mb-1 flex-wrap gap-x-2"><strong class="text-purple-600 dark:text-purple-400">P: 
        ${c.nombrePaciente || 'N/A'}</strong><span class="text-xs font-semibold px-2 py-0.5 rounded 
        ${eC}">${c.estado || '?'}</span></div><div class="text-sm..."><i class="bi bi-telephone"></i> 
        ${c.telefonoPaciente || '-'}</div><div class="text-sm..."><i class="bi bi-calendar-event"></i> 
        ${formatDate(c.fecha)} <i class="bi bi-clock"></i> 
        ${hF}</div>${c.motivo ? `<div class="text-sm mt-2 pt-2 border-t..."><stron>Motivo:</stron> 
            ${c.motivo}</div>` : ''}<div class="mt-3 flex flex-wrap gap-2">
            ${(c.estado === 'Programada') ? `<button data-action="confirmar" data-id="${c.idCita}" class="btn-cita-accion btn-confirmar">Confirmar</button>` : ''}
            ${(c.estado !== 'Cancelada Paciente' && c.estado !== 'Cancelada Doctor' && c.estado !== 'Completada') ? `<button data-action="cancelar-doctor" data-id="${c.idCita}" class="btn-cita-accion btn-cancelar">Cancelar</button>` : ''}
            ${(c.estado === 'Confirmada') ? `<button data-action="completar" data-id="${c.idCita}" class="btn-cita-accion btn-completar">Completada</button>` : ''}
            ${(c.estado !== 'Cancelada Paciente' && c.estado !== 'Cancelada Doctor' && c.estado !== 'Completada') ? `<button data-action="cargar-notas" data-id="${c.idCita}" class="btn-cita-accion btn-notas">Cargar p/ Notas</button>` : ''}
            </div>`;
        return li;
    }

    // --- Funciones Panel Paciente ---
    async function cargarDatosPerfilUsuario() { /* ... */ }
    async function cargarCitasUsuario() { /* ... */ }
    async function cargarListaMedicos() { /* ... */ }
    async function cargarHistorialUsuario() { /* ... */ }

    // --- Funciones Panel Médico ---
    async function cargarDatosPerfilMedico() { /* ... */ }
    async function cargarCitasMedico() { /* ... */ }
    async function cargarPacientesHoy() { /* ... */ }
    async function guardarNotasConsulta(event) { /* ... */ }

    // --- Funciones Panel Paciente ---
    async function cargarDatosPerfilUsuario() {
         /* ... */ const form = document.querySelector('#update-profile-form');
        setLoadingState(form, true);
        try {
            const data = await fetchData('obtener_perfil.php');
            if (data?.success && data.perfil) {
                if (form) populateForm(form, data.perfil);
                else console.error("Form #update-profile-form no encontrado.");
            }
            else if (data && data.message?.toLowerCase().includes("sesión no iniciada")) { setTimeout(() => window.location.href = 'registro.php#login', 2000); }
        } catch (error) { /* Handled */ } finally { setLoadingState(form, false, 'Actualizar Datos'); }
    }
    async function cargarCitasUsuario() {
        console.log("[Funciones Panel] Cargando citas del usuario...");
        const listElement = document.querySelector('#appointments-list');
        if (!listElement) { console.error("Elemento #appointments-list no encontrado."); return; }
        listElement.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando citas...</li>';
        try {
            const data = await fetchData('obtener_citas.php?rol=paciente');
            // <<< LOG DETALLADO DE LA RESPUESTA COMPLETA >>>
            console.log("[cargarCitasUsuario] Datos recibidos del backend:", JSON.stringify(data, null, 2)); // Muestra el JSON formateado

            listElement.innerHTML = ''; // Limpiar carga

            if (data?.success && Array.isArray(data.citas)) { // Verifica que sea un array
                if (data.citas.length === 0) {
                    listElement.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">No tiene citas programadas.</li>';
                } else {
                    console.log(`[cargarCitasUsuario] Procesando ${data.citas.length} citas...`);
                    data.citas.forEach((cita, index) => {
                        console.log(`[cargarCitasUsuario]   Procesando cita #${index + 1}:`, cita); // Log de cada cita
                        let li = null; // Inicializar li
                        try {
                            // <<< LOG ANTES DE CREAR ELEMENTO >>>
                            console.log(`[cargarCitasUsuario]     Intentando crear elemento LI para cita ${cita.idCita}...`);
                            li = crearElementoCitaPaciente(cita); // Llama a la función helper
                            // <<< LOG DESPUÉS DE CREAR (SI NO HAY ERROR) >>>
                            console.log(`[cargarCitasUsuario]     Elemento LI creado para cita ${cita.idCita}.`);

                            // <<< LOG ANTES DE AÑADIR AL DOM >>>
                            console.log(`[cargarCitasUsuario]     Intentando añadir LI al DOM...`);
                            listElement.appendChild(li);
                            // <<< LOG DESPUÉS DE AÑADIR (SI NO HAY ERROR) >>>
                            console.log(`[cargarCitasUsuario]     LI añadido al DOM para cita ${cita.idCita}.`);

                        } catch (error) {
                            // <<< LOG SI HAY ERROR DURANTE CREACIÓN O AÑADIDO >>>
                            console.error(`[cargarCitasUsuario]   ¡ERROR procesando cita #${index + 1} (ID: ${cita.idCita})!`, error);
                            // Opcional: Añadir un LI de error para esta cita específica
                            if (listElement) {
                                const errorLi = document.createElement('li');
                                errorLi.className = 'placeholder text-red-500';
                                errorLi.textContent = `Error al mostrar cita ID ${cita.idCita}.`;
                                listElement.appendChild(errorLi);
                            }
                        }
                    });
                    // Llamar a attach listeners DESPUÉS de añadir todos los elementos
                    attachCitaActionListeners('#appointments-list');
                }
            } else {
                // Error en la estructura de la respuesta (no success o citas no es array)
                console.error("[cargarCitasUsuario] Respuesta del backend no válida o sin éxito:", data);
                listElement.innerHTML = `<li class="placeholder text-red-500">Error: ${data?.message || 'Formato de respuesta incorrecto'}.</li>`;
            }
        } catch (error) {
            // Error en fetchData (comunicación, etc.)
            console.error("[cargarCitasUsuario] Error en fetchData:", error);
            listElement.innerHTML = '<li class="placeholder text-red-500">Error de comunicación al cargar citas.</li>';
        }
    }
    async function cargarListaMedicos() {
        /* ... */ const sel = document.getElementById('schedule-medico');
        if (!sel) return; sel.disabled = true; sel.innerHTML = '<option value="" disabled selected>Cargando...</option>';
        try {
            const data = await fetchData('obtener_medicos.php'); sel.innerHTML = '<option value="" disabled selected>Seleccione...</option>';
            if (data?.success && data.medicos) {
                if (data.medicos.length > 0) { data.medicos.forEach(m => sel.add(new Option(`${m.nombre} - ${m.especialidad || 'Gral.'}`, m.idMedico))); sel.disabled = false; }
                else sel.innerHTML = '<option value="" disabled>No disponibles</option>';
            } else sel.innerHTML = '<option value="" disabled>Error</option>';
        } catch (error) { sel.innerHTML = '<option value="" disabled>Error</option>'; }
    }
    async function cargarHistorialUsuario() { // <-- NUEVA FUNCIÓN IMPLEMENTADA
        console.log("[Funciones Panel] Cargando historial médico del paciente...");
        const listElement = document.querySelector('#history-list');
        if (!listElement) { console.error("Elemento #history-list no encontrado."); return; }
        listElement.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">Cargando historial...</li>';
        try {
            const data = await fetchData('obtener_historial.php');
            listElement.innerHTML = ''; // Limpiar
            if (data?.success && data.historial) {
                if (data.historial.length === 0) {
                    listElement.innerHTML = '<li class="placeholder text-gray-500 dark:text-gray-400 italic">No hay registros en tu historial.</li>';
                } else {
                    data.historial.forEach(registro => {
                        const li = document.createElement('li');
                        li.className = 'mb-3 pb-3 border-b dark:border-gray-700 text-sm';
                        li.innerHTML = `
                            <div class="flex justify-between items-center mb-1">
                                <strong class="text-gray-800 dark:text-gray-200">${formatDate(registro.fecha)}</strong>
                                <span class="text-gray-600 dark:text-gray-400 text-xs">Dr. ${registro.nombreMedico || 'N/A'}</span>
                            </div>
                            ${registro.diagnostico ? `<div class="mb-1"><strong class="font-medium text-gray-700 dark:text-gray-300">Diagnóstico:</strong> ${registro.diagnostico}</div>` : ''}
                            ${registro.tratamiento ? `<div><strong class="font-medium text-gray-700 dark:text-gray-300">Tratamiento:</strong> ${registro.tratamiento}</div>` : ''}
                             ${!registro.diagnostico && !registro.tratamiento ? `<div class="text-gray-500 italic">Sin detalles.</div>` : ''}
                        `;
                        listElement.appendChild(li);
                    });
                }
            } else { listElement.innerHTML = `<li class="placeholder text-red-500">Error al cargar historial: ${data?.message || '?'}.</li>`; }
        } catch (error) { listElement.innerHTML = '<li class="placeholder text-red-500">Error de comunicación al cargar historial.</li>'; }
    }

    // --- Funciones Panel Médico ---
    async function cargarDatosPerfilMedico() { /* ... */
        const form = document.querySelector('#doctor-profile-form'); setLoadingState(form, true);
        try {
            const data = await fetchData('obtener_perfil.php'); if (data?.success && data.perfil) {
                if (form) populateForm(form, data.perfil);
                else console.error("Form #doctor-profile-form no encontrado.");
            } else if (data && data.message?.toLowerCase().includes("sesión no iniciada")) { setTimeout(() => window.location.href = 'registro.php#login', 2000); }
        } catch (error) { /* Handled */ } finally { setLoadingState(form, false, 'Actualizar Perfil'); }
    }
    async function cargarCitasMedico() { /* ... */
        const el = document.querySelector('#appointments-list-doctor');
        if (!el) return; el.innerHTML = '<li class="plh">Cargando...</li>';
        try {
            const data = await fetchData('obtener_citas.php?rol=medico'); el.innerHTML = '';
            if (data?.success && data.citas) {
                if (data.citas.length === 0) el.innerHTML = '<li class="plh">No hay citas.</li>';
                else {
                    data.citas.forEach(c => el.appendChild(crearElementoCitaMedico(c)));
                    attachCitaActionListeners('#appointments-list-doctor');
                }
            } else el.innerHTML = `<li class="plh err">Error: ${data?.message || '?'}.</li>`;
        } catch (error) { el.innerHTML = '<li class="plh err">Error red.</li>'; }
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
        // TODO: Reemplazar confirm con Swal.fire
        if (!confirm(`¿Cambiar estado a "${nuevoEstado}"?`)) return;
        try {
            const fd = new FormData(); fd.append('idCita', idCita); fd.append('nuevoEstado', nuevoEstado);
            const data = await fetchData('cambiar_estado_cita.php', { method: 'POST', body: fd });
            if (data?.success) { showNotification(data.message || "Estado actualizado.", 'success'); if (currentPath.includes('perfil-doctores.php')) cargarCitasMedico(); if (currentPath.includes('perfil-usuario.php')) cargarCitasUsuario(); }
        } catch (error) { /* Handled */ }
    }

    function attachCitaActionListeners(containerSelector) {
        const container = document.querySelector(containerSelector); if (!container) return;
        const currentListener = container.handleCitaClick; // Intentar obtener listener previo
        if (currentListener) container.removeEventListener('click', currentListener); // Limpiar si existe

        const handleCitaActionClick = (event) => { /* ... (código interno sin cambios) ... */
            const button = event.target.closest('button[data-action]'); if (!button || button.disabled) return;
            const action = button.dataset.action; const idCita = button.dataset.id; console.log(`%c[Click Acción Cita] ${containerSelector}: Acción=${action}, ID=${idCita}`, 'color: darkcyan;');
            if (!idCita) { console.error("Botón sin data-id!"); return; }
            button.disabled = true; const originalText = button.textContent; button.textContent = '...';
            const executeAndEnable = async (fn) => { try { await fn(); } catch (err) { } finally { button.disabled = false; button.textContent = originalText; } };
            switch (action) {
                case 'confirmar': executeAndEnable(() => cambiarEstadoCita(idCita, 'Confirmada')); break;
                case 'cancelar-doctor': executeAndEnable(() => cambiarEstadoCita(idCita, 'Cancelada Doctor')); break;
                case 'cancelar-paciente': executeAndEnable(() => cambiarEstadoCita(idCita, 'Cancelada Paciente')); break;
                case 'completar': executeAndEnable(() => cambiarEstadoCita(idCita, 'Completada')); break;
                case 'cargar-notas':
                    selectedCitaIdForNotes = idCita; showNotification(`Cita ${idCita} seleccionada para notas.`, 'info'); document.querySelector('#consulta-notes-form textarea')?.focus();
                    container.querySelectorAll('li[data-cita-id]').forEach(li => li.classList.remove('bg-blue-100', 'dark:bg-blue-900')); button.closest('li[data-cita-id]')?.classList.add('bg-blue-100', 'dark:bg-blue-900');
                    button.disabled = false; button.textContent = originalText; // Rehabilitar inmediato
                    break;
                default: console.warn(`Acción desconocida: ${action}`); showNotification(`Acción no implementada: ${action}`, 'warning'); button.disabled = false; button.textContent = originalText;
            }
        };
        container.addEventListener('click', handleCitaActionClick);
        container.handleCitaClick = handleCitaActionClick; // Guardar referencia para posible limpieza futura
        console.log(`[Listeners] Listener delegado adjuntado a ${containerSelector}`);
    }

    // ========================================================================
    // == 8. FINALIZACIÓN =====================================================
    // ========================================================================
    console.log('[MediAgenda] Inicialización del script finalizada.');

}); // Fin de DOMContentLoaded