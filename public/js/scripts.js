// public/js/scripts.js
// ========================================================================
// == MediAgenda - Scripts Principales (v4 - Estructura Refactorizada) ====
// ========================================================================
// Maneja la lógica común de UI, llamadas AJAX, manejo de formularios
// y funcionalidades específicas de los paneles de Paciente y Médico.

// --- Modo Estricto ---
"use strict";

// ========================================================================
// == 1. CONFIGURACIÓN GLOBAL Y FUNCIONES AUXILIARES =====================
// ========================================================================

/**
 * URL base para los endpoints de la API del backend.
 * Asume que el servidor está configurado para mapear /api/ a app/Api/
 * o que existe un router en /api/ que dirige las solicitudes.
 * @type {string}
 */
const backendUrl = '/api/'; // ¡Actualizado para la nueva estructura!

/**
 * Muestra una notificación modal usando SweetAlert2.
 * @param {string} message - El mensaje principal a mostrar.
 * @param {'success'|'error'|'warning'|'info'|'question'} [type='info'] - El tipo de icono y estilo.
 * @param {string|null} [title=null] - El título del modal. Si es null, se genera uno por defecto basado en 'type'.
 */
function showNotification(message, type = 'info', title = null) {
    let iconType = type; // Mapeo directo para SweetAlert2
    let titleText = title;

    if (!titleText) {
        switch (type) {
            case 'success': titleText = 'Éxito'; break;
            case 'error': titleText = 'Error'; break;
            case 'warning': titleText = 'Advertencia'; break;
            case 'question': titleText = 'Confirmación'; break;
            default: titleText = 'Información'; break;
        }
    }

    Swal.fire({
        icon: iconType,
        title: titleText,
        text: message,
        confirmButtonText: 'Aceptar',
        customClass: { // Clases opcionales para compatibilidad con Tailwind dark mode
            // popup: 'dark:bg-gray-800 dark:text-white',
            // title: 'dark:text-white',
            // htmlContainer: 'dark:text-gray-300',
            // confirmButton: '...', // Aplicar clases Tailwind si se personaliza
        },
    });
    console.log(`[Notification] Type: ${type}, Title: ${titleText}, Message: ${message}`);
}

/**
 * Realiza una solicitud Fetch a un endpoint del backend.
 * Maneja errores comunes y parsea la respuesta JSON.
 * @param {string} endpoint - La ruta del endpoint RELATIVA a `backendUrl` (ej. 'Auth/login.php').
 * @param {RequestInit} [options={}] - Opciones de configuración para Fetch (method, headers, body, etc.).
 * @returns {Promise<any>} - Promesa que resuelve con los datos JSON o null (para 204), o rechaza con un Error.
 * @throws {Error} - Lanza un error si la comunicación falla, la respuesta no es OK, o el JSON es inválido.
 */
async function fetchData(endpoint, options = {}) {
    const url = backendUrl + endpoint;
    console.log(`[Fetch] Requesting: ${options.method || 'GET'} ${url}`);
    try {
        const response = await fetch(url, options);

        // Log básico de la respuesta
        console.log(`[Fetch] Response Status: ${response.status} for ${url}`);

        if (!response.ok) {
            let errorData = { message: `Error HTTP ${response.status}`, code: response.status };
            try {
                // Intenta obtener un mensaje de error más específico del cuerpo JSON
                const errorJson = await response.json();
                errorData.message = errorJson.message || errorData.message;
                console.warn(`[Fetch] Server error response body for ${url}:`, errorJson);
            } catch (e) {
                // Si el cuerpo no es JSON o está vacío, usa el mensaje HTTP
                console.warn(`[Fetch] Non-JSON or empty error response body for ${url}. Status: ${response.status}`);
            }
            const error = new Error(errorData.message);
            error.code = errorData.code;
            throw error; // Lanza el error para ser capturado por el bloque catch externo
        }

        // Manejo de respuesta 204 No Content
        if (response.status === 204) {
            console.log(`[Fetch] Success (204 No Content) for ${url}`);
            return null;
        }

        // Parsear JSON
        const data = await response.json();
        console.log(`[Fetch] Success data for ${url}:`, data);
        return data;

    } catch (error) {
        console.error(`[Fetch] Error during fetch to ${url}:`, error);
        // Notificar al usuario del error (si no es un error ya manejado como 401/403)
        if (error.code !== 401 && error.code !== 403) { // Evita notificaciones duplicadas si el backend ya envió error específico
             showNotification(`Error de comunicación con el servidor: ${error.message || 'Intente más tarde.'}`, 'error');
        }
        // Relanzar el error para que la función llamante pueda manejarlo si es necesario
        throw error;
    }
}


/**
 * Rellena un formulario HTML con datos de un objeto.
 * Las claves del objeto deben coincidir con los atributos 'name' de los campos del formulario.
 * @param {HTMLFormElement} form - El elemento del formulario a rellenar.
 * @param {object} data - El objeto con los datos.
 */
function populateForm(form, data) {
    if (!form || !data) {
        console.warn('[PopulateForm] Formulario u objeto de datos no válidos.');
        return;
    }
    console.log('[PopulateForm] Rellenando formulario:', form.id, 'con datos:', data);
    for (const key in data) {
        // Usar Object.hasOwn o hasOwnProperty para seguridad
        if (Object.hasOwn(data, key)) {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                // Manejar diferentes tipos de input si es necesario (checkbox, radio)
                if (field.type === 'checkbox') {
                    field.checked = !!data[key]; // Convertir a booleano
                } else if (field.type === 'radio') {
                    // Buscar el radio específico con ese valor
                    const radio = form.querySelector(`[name="${key}"][value="${data[key]}"]`);
                    if (radio) radio.checked = true;
                } else {
                    field.value = data[key] ?? ''; // Usar ?? para manejar null/undefined
                }
            } else {
                // console.warn(`[PopulateForm] Campo no encontrado en el formulario ${form.id} para la clave: ${key}`);
            }
        }
    }
}

/**
 * Establece el estado de carga visual de un botón submit dentro de un formulario.
 * @param {HTMLFormElement} form - El formulario que contiene el botón.
 * @param {boolean} isLoading - true para mostrar estado de carga, false para restaurar.
 * @param {string} [loadingText='Cargando...'] - Texto a mostrar mientras carga.
 */
function setLoadingState(form, isLoading, loadingText = 'Cargando...') {
    if (!form) return;
    const button = form.querySelector('button[type="submit"]');
    if (!button) return;

    const textElement = button.querySelector('.button-text'); // Asume que el texto está en un span
    const spinnerElement = button.querySelector('.button-spinner'); // Asume un icono spinner

    if (isLoading) {
        // Guardar texto original si no se ha guardado antes
        if (!button.dataset.originalText && textElement) {
             button.dataset.originalText = textElement.textContent;
        }
        button.disabled = true;
        if (textElement) textElement.textContent = loadingText; // Cambiar texto o ocultarlo
        if (spinnerElement) spinnerElement.classList.remove('hidden'); // Mostrar spinner
    } else {
        button.disabled = false;
        if (textElement && button.dataset.originalText) {
             textElement.textContent = button.dataset.originalText; // Restaurar texto
        }
        if (spinnerElement) spinnerElement.classList.add('hidden'); // Ocultar spinner
    }
}

/**
 * Formatea una cadena de tiempo (HH:MM:SS o HH:MM) a un formato localizado AM/PM.
 * @param {string|null} timeString - La cadena de tiempo (ej. "14:30:00").
 * @returns {string} - El tiempo formateado (ej. "2:30 PM") o 'N/A'.
 */
function formatTime(timeString) {
    if (!timeString) return 'N/A';
    try {
        // Crear objeto Date usando una fecha base + la hora
        const [hours, minutes] = timeString.split(':');
        const date = new Date();
        date.setHours(parseInt(hours, 10), parseInt(minutes, 10), 0, 0);
        return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
    } catch (e) {
        console.error(`[FormatTime] Error formateando hora: ${timeString}`, e);
        return timeString; // Devolver original si falla
    }
}

/**
 * Formatea una cadena de fecha (YYYY-MM-DD) a un formato localizado legible.
 * @param {string|null} dateString - La cadena de fecha (ej. "2024-05-15").
 * @returns {string} - La fecha formateada (ej. "15 de mayo de 2024") o 'N/A'.
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        // Añadir T00:00:00 para evitar problemas de zona horaria al parsear solo la fecha
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    } catch (e) {
        console.error(`[FormatDate] Error formateando fecha: ${dateString}`, e);
        return dateString; // Devolver original si falla
    }
}

/**
 * Devuelve clases CSS (Tailwind) para estilizar un badge según el estado de la cita.
 * @param {string} estado - El estado de la cita (ej. "Programada", "Confirmada").
 * @returns {string} - Las clases CSS correspondientes.
 */
function getEstadoClasses(estado) {
    const classes = {
        'Programada': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'Confirmada': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'Cancelada Paciente': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'Cancelada Doctor': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'Completada': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'No Asistió': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
    };
    return classes[estado] || 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200'; // Default
}

/**
 * Devuelve clases CSS (Tailwind) para el borde lateral coloreado según el estado.
 * @param {string} estado - El estado de la cita.
 * @returns {string} - Las clases CSS del borde.
 */
function getEstadoBordeClasses(estado) {
     const classes = {
            'Programada': 'border-l-4 border-blue-500 dark:border-blue-400',
            'Confirmada': 'border-l-4 border-green-500 dark:border-green-400',
            'Cancelada Paciente': 'border-l-4 border-red-500 dark:border-red-400',
            'Cancelada Doctor': 'border-l-4 border-red-500 dark:border-red-400',
            'Completada': 'border-l-4 border-gray-400 dark:border-gray-500',
            'No Asistió': 'border-l-4 border-yellow-500 dark:border-yellow-400'
        };
     return classes[estado] || 'border-l-4 border-gray-300 dark:border-gray-600'; // Default
}

// ========================================================================
// == 2. MANEJO DE FORMULARIOS GENÉRICO =================================
// ========================================================================

/**
 * Adjunta un listener a un formulario para manejar su envío vía Fetch API.
 * Incluye validación básica del lado del cliente y manejo de estados de carga.
 * @param {string} formSelector - Selector CSS para el formulario.
 * @param {string} phpScriptEndpoint - Endpoint PHP relativo a `backendUrl` (ej. 'Auth/login.php').
 * @param {function(data: object, form: HTMLFormElement): void} successCallback - Función a ejecutar en caso de éxito. Recibe los datos de respuesta y el elemento form.
 */
function handleFormSubmit(formSelector, phpScriptEndpoint, successCallback) {
    const form = document.querySelector(formSelector);
    if (!form) {
        console.warn(`[HandleFormSubmit] Formulario no encontrado: ${formSelector}`);
        return;
    }

    // Obtener texto original del botón para restaurarlo después
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton?.querySelector('.button-text')?.textContent || submitButton?.textContent || 'Enviar';

    // Limpiar errores previos al añadir listener (por si acaso)
    form.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
    form.querySelectorAll('[required]').forEach(el => el.classList.remove('border-red-500'));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        console.log(`%c[Submit] Iniciando envío para ${formSelector} -> ${phpScriptEndpoint}`, 'color:orange;');

        // --- Limpieza y Validación Client-Side ---
        form.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
        form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));

        let isValid = true;
        // Iterar sobre los campos requeridos DENTRO del formulario actual
        form.querySelectorAll('[required]').forEach(input => {
            const fieldContainer = input.closest('div'); // Asume que el mensaje de error está en el mismo div
            const errorElement = fieldContainer?.querySelector('.error-message');

            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('border-red-500');
                if (errorElement) {
                    errorElement.textContent = 'Este campo es obligatorio.';
                    errorElement.classList.remove('hidden');
                }
            } else if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                isValid = false;
                input.classList.add('border-red-500');
                if (errorElement) {
                    errorElement.textContent = 'Formato de correo inválido.';
                    errorElement.classList.remove('hidden');
                }
            } else if (input.type === 'password' && input.hasAttribute('minlength') && input.value.length < parseInt(input.getAttribute('minlength'), 10)) {
                 isValid = false;
                 input.classList.add('border-red-500');
                 if (errorElement) {
                    errorElement.textContent = `La contraseña debe tener al menos ${input.getAttribute('minlength')} caracteres.`;
                    errorElement.classList.remove('hidden');
                }
            }
            // Añadir más validaciones específicas si es necesario
        });

        if (!isValid) {
            showNotification('Por favor, corrija los errores en el formulario.', 'warning');
            console.warn(`[Submit] Formulario ${formSelector} no válido.`);
            return; // Detener si no es válido
        }
        // --- Fin Validación Client-Side ---

        const formData = new FormData(form);
        setLoadingState(form, true); // Mostrar estado de carga

        try {
            const data = await fetchData(phpScriptEndpoint, { method: 'POST', body: formData });
            console.log(`[Submit] Respuesta recibida para ${formSelector}:`, data);

            // fetchData ya maneja errores HTTP y de comunicación.
            // Aquí solo procesamos la respuesta exitosa.
            if (data?.success) {
                if (successCallback) {
                    successCallback(data, form); // Ejecutar callback personalizado
                } else {
                    // Comportamiento por defecto si no hay callback
                    showNotification(data.message || 'Operación realizada con éxito.', 'success');
                    form.reset(); // Limpiar formulario
                }
            } else if (data) {
                // Si success es false o falta, mostrar mensaje de error del backend
                showNotification(data.message || 'Ocurrió un error en el servidor.', 'error');
            }
            // Si data es null (ej. 204), no hacemos nada aquí.

        } catch (error) {
            console.error(`[Submit] Error capturado en el manejador de ${formSelector}:`, error);
            // La notificación de error ya debería haberse mostrado por fetchData
            // pero podrías añadir lógica específica aquí si es necesario.
        } finally {
            setLoadingState(form, false); // Restaurar estado del botón
            // Restaurar el texto original explícitamente si se usa .button-text
             const textElement = submitButton?.querySelector('.button-text');
             if (textElement) {
                 textElement.textContent = originalButtonText;
             } else if (submitButton) {
                  submitButton.textContent = originalButtonText;
             }
        }
    });
    console.log(`[HandleFormSubmit] Listener añadido a ${formSelector}`);
}

// ========================================================================
// == 3. INICIALIZACIÓN UI COMÚN (Dark Mode, Menú, Scroll, etc.) =======
// ========================================================================

/**
 * Inicializa funcionalidades comunes de la interfaz de usuario.
 */
function initializeCommonUI() {
    console.log('[UI] Inicializando UI común...');
    const body = document.body;

    // --- Dark Mode ---
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        const icon = darkModeToggle.querySelector('i'); // fas fa-moon o fa-sun

        const applyDarkMode = (isDark) => {
            body.classList.toggle('dark', isDark);
            if (icon) {
                icon.classList.toggle('fa-sun', isDark); // Icono sol si es dark
                icon.classList.toggle('fa-moon', !isDark); // Icono luna si es light
                icon.classList.toggle('text-yellow-400', isDark); // Color amarillo para sol
                icon.classList.toggle('text-blue-600', !isDark); // Color azul para luna
            }
            // Guardar preferencia en cookie para persistencia entre páginas PHP
            document.cookie = `theme=${isDark ? 'dark' : 'light'};path=/;max-age=31536000;samesite=lax`; // Expira en 1 año
            console.log(`[UI] Modo oscuro ${isDark ? 'activado' : 'desactivado'}.`);
        };

        // Aplicar tema al cargar la página basado en cookie o preferencia del sistema
        // La clase 'dark' ya se aplica en el body server-side leyendo la cookie,
        // aquí solo sincronizamos el icono.
        const currentThemeIsDark = body.classList.contains('dark');
         if (icon) {
                icon.classList.toggle('fa-sun', currentThemeIsDark);
                icon.classList.toggle('fa-moon', !currentThemeIsDark);
                icon.classList.toggle('text-yellow-400', currentThemeIsDark);
                icon.classList.toggle('text-blue-600', !currentThemeIsDark);
         }
        console.log(`[UI] Tema inicial: ${currentThemeIsDark ? 'oscuro' : 'claro'}`);

        // Listener para el botón
        darkModeToggle.addEventListener('click', () => {
            applyDarkMode(!body.classList.contains('dark'));
        });
    } else { console.warn('[UI] Botón de modo oscuro no encontrado.'); }

    // --- Menú Hamburguesa ---
    const hamburgerBtn = document.getElementById('hamburger-menu');
    const mobileMenu = document.getElementById('mobile-menu'); // El menú desplegable

    const closeMobileMenu = () => {
        if (mobileMenu) mobileMenu.classList.add('hidden');
        if (hamburgerBtn) {
            hamburgerBtn.classList.remove('open');
            // Resetear estilos de animación del icono (si aplica el estilo de X)
            const spans = hamburgerBtn.querySelectorAll('span');
            if (spans.length === 3) {
                spans[0].style.transform = '';
                spans[1].style.opacity = '1';
                spans[2].style.transform = '';
            }
        }
         console.log('[UI] Menú móvil cerrado.');
    };

    if (hamburgerBtn && mobileMenu) {
        hamburgerBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Evitar que cierre inmediatamente si hay listener en body
            const isOpen = mobileMenu.classList.toggle('hidden');
            hamburgerBtn.classList.toggle('open', !isOpen);
            // Animar icono a X (si se usa el CSS correspondiente)
            const spans = hamburgerBtn.querySelectorAll('span');
             if (spans.length === 3) {
                spans[0].style.transform = !isOpen ? 'rotate(45deg) translate(5px, 5px)' : '';
                spans[1].style.opacity = !isOpen ? '0' : '1';
                spans[2].style.transform = !isOpen ? 'rotate(-45deg) translate(5px, -5px)' : '';
            }
            console.log(`[UI] Menú móvil ${!isOpen ? 'abierto' : 'cerrado'}.`);
        });

        // Cerrar menú si se hace clic en un enlace dentro de él
        mobileMenu.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

        // Cerrar menú si se hace clic fuera de él (opcional)
        // document.addEventListener('click', (e) => {
        //     if (!mobileMenu.classList.contains('hidden') && !mobileMenu.contains(e.target) && e.target !== hamburgerBtn && !hamburgerBtn.contains(e.target)) {
        //         closeMobileMenu();
        //     }
        // });

    } else { console.warn('[UI] Botón hamburguesa o menú móvil no encontrados.'); }

    // --- Scroll Suave para Enlaces Ancla ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            // Asegurarse que es un ancla válida y no solo "#"
            if (href && href.length > 1 && href.startsWith('#')) {
                try {
                    const targetElement = document.querySelector(href);
                    if (targetElement) {
                        e.preventDefault();
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        console.log(`[UI] Scroll suave a: ${href}`);
                        // Opcional: cerrar menú móvil si estaba abierto
                        closeMobileMenu();
                    } else {
                        console.warn(`[UI] Elemento target para scroll suave no encontrado: ${href}`);
                    }
                } catch (err) {
                    console.error(`[UI] Error en scroll suave para ${href}:`, err);
                }
            }
        });
    });

    // --- Efecto Parallax (Simple con background-attachment) ---
    // No requiere JS si se usa `background-attachment: fixed;` en el CSS
    // Si se necesita parallax más complejo, el código JS iría aquí.
    console.log('[UI] Parallax manejado por CSS (background-attachment: fixed).');

    // --- Efecto Fade-in al Scroll (Intersection Observer) ---
    const fadeInElems = document.querySelectorAll('.fade-in'); // Usar una clase específica
    if (fadeInElems.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible'); // Añade clase para activar animación/opacidad CSS
                    console.log('[UI] Elemento fade-in visible:', entry.target.id || entry.target.tagName);
                    obs.unobserve(entry.target); // Dejar de observar una vez activado
                }
            });
        }, { threshold: 0.1 }); // Activar cuando el 10% sea visible

        fadeInElems.forEach(el => observer.observe(el));
        console.log(`[UI] Observador Fade-in activado para ${fadeInElems.length} elementos.`);
    } else {
        // Si no hay soporte o elementos, simplemente mostrar todo
        fadeInElems.forEach(el => el.classList.add('visible'));
    }

    // --- Manejo Notificación Logout ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('logout') && urlParams.get('logout') === 'success') {
        console.log("[UI] Parámetro logout=success detectado.");
        showNotification('Has cerrado sesión correctamente.', 'success', 'Sesión Cerrada');
        // Limpiar URL sin recargar
        if (history.replaceState) {
            const cleanUrl = window.location.pathname + window.location.hash;
            history.replaceState(null, '', cleanUrl);
            console.log("[UI] Parámetro logout limpiado de la URL.");
        }
    }
     // --- Manejo Notificación Error Acceso --- (Si se quiere en JS)
     if (urlParams.has('error') && urlParams.get('error') === 'unauthorized') {
          console.log("[UI] Parámetro error=unauthorized detectado.");
          showNotification('Acceso denegado. Debes iniciar sesión.', 'error', 'No Autorizado');
          if (history.replaceState) { /* ... limpiar URL ... */ }
     } else if (urlParams.has('error') && urlParams.get('error') === 'unauthorized_role') {
          console.log("[UI] Parámetro error=unauthorized_role detectado.");
          showNotification('No tienes permiso para acceder a esta página con tu rol actual.', 'error', 'Permiso Denegado');
           if (history.replaceState) { /* ... limpiar URL ... */ }
     }


    console.log('[UI] Inicialización UI común finalizada.');
}

// ========================================================================
// == 4. FUNCIONES ESPECIÍFICAS DE PANELES (Paciente y Médico) ==========
// ========================================================================

// --- Funciones Panel Paciente (`perfil-usuario.php`) ---

/**
 * Carga y muestra los datos del perfil del paciente en el formulario.
 */
async function cargarDatosPerfilUsuario() {
    console.log("[Panel Paciente] Cargando datos del perfil...");
    const form = document.querySelector('#update-profile-form'); // ID del form en perfil-usuario.php
    if (!form) { console.error("[Perfil Usuario] Formulario #update-profile-form no encontrado."); return; }
    setLoadingState(form, true, 'Cargando Perfil...');
    try {
        // Llama al endpoint de perfil (común para todos los roles)
        const data = await fetchData('Perfil/obtener_perfil.php');
        if (data?.success && data.perfil) {
            populateForm(form, data.perfil); // Rellena el form
        } else {
            // Error o sesión inválida (ya notificado por fetchData o backend)
            // Opcional: deshabilitar formulario o mostrar mensaje aquí
        }
    } catch (error) {
        // Error de comunicación (ya notificado por fetchData)
        // Opcional: deshabilitar formulario o mostrar mensaje
    } finally {
        setLoadingState(form, false); // Restaurar botón (usa texto original guardado)
    }
}

/**
 * Crea el elemento LI para mostrar una cita en el panel del paciente.
 * @param {object} cita - El objeto de datos de la cita.
 * @returns {HTMLLIElement} El elemento LI creado.
 */
function crearElementoCitaPaciente(cita) {
    const li = document.createElement('li');
    const estadoBorde = getEstadoBordeClasses(cita.estado);
    li.className = `mb-4 p-4 border rounded-lg dark:border-gray-700 bg-white dark:bg-gray-800 ${estadoBorde} shadow-sm transition-shadow hover:shadow-md`;
    li.dataset.citaId = cita.idCita; // Guardar ID para referencia

    const estadoBadge = getEstadoClasses(cita.estado);
    const horaFormateada = formatTime(cita.hora);
    const fechaFormateada = formatDate(cita.fecha);

    // Usar template literals para construir el HTML de forma más legible
    li.innerHTML = `
        <div class="flex justify-between items-start mb-2 flex-wrap gap-x-2 gap-y-1">
            <div class="flex items-center gap-2">
                 <i class="bi bi-person-badge text-xl text-blue-600 dark:text-blue-400"></i>
                <strong class="text-blue-700 dark:text-blue-300 text-lg font-semibold">Dr. ${cita.nombreMedico || 'N/A'}</strong>
                 <span class="text-xs font-medium text-gray-500 dark:text-gray-400">(${cita.especialidadMedico || 'General'})</span>
            </div>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full ${estadoBadge}">${cita.estado || '?'}</span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 flex items-center gap-4 flex-wrap">
            <span><i class="bi bi-calendar-event mr-1 opacity-80"></i> ${fechaFormateada}</span>
            <span><i class="bi bi-clock mr-1 opacity-80"></i> ${horaFormateada}</span>
        </div>
        ${cita.motivo ? `
            <div class="text-sm mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <strong class="text-gray-700 dark:text-gray-300 font-medium">Motivo:</strong>
                <p class="mt-1 text-gray-600 dark:text-gray-400 whitespace-pre-wrap">${cita.motivo}</p> {/* Usar pre-wrap para saltos de línea */}
            </div>` : ''}
        <div class="mt-4 flex gap-2 justify-end">
            ${(cita.estado === 'Programada' || cita.estado === 'Confirmada') ? `
                <button data-action="cancelar-paciente" data-id="${cita.idCita}"
                        class="text-xs bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800 font-medium py-1 px-3 rounded-md transition duration-150 flex items-center gap-1 appointment-action-button">
                    <i class="bi bi-x-circle"></i> Cancelar Cita
                </button>` : ''}
            <!-- Aquí podrían ir otros botones, ej. 'Reprogramar' si se implementa -->
        </div>`;
    return li;
}


/**
 * Carga y muestra la lista de citas del paciente.
 */
async function cargarCitasUsuario() {
    console.log("[Panel Paciente] Cargando citas...");
    const listElement = document.querySelector('#appointments-list'); // ID en perfil-usuario.php
    if (!listElement) { console.error("[Perfil Usuario] Lista #appointments-list no encontrada."); return; }

    listElement.innerHTML = '<li class="placeholder">Cargando citas...</li>'; // Mostrar carga

    try {
        const data = await fetchData('Citas/obtener_citas.php?rol=paciente'); // Llama al endpoint correcto
        listElement.innerHTML = ''; // Limpiar placeholder/contenido anterior

        if (data?.success && Array.isArray(data.citas)) {
            if (data.citas.length === 0) {
                listElement.innerHTML = '<li class="placeholder">No tiene citas programadas.</li>';
            } else {
                data.citas.forEach(cita => {
                    const citaElement = crearElementoCitaPaciente(cita);
                    listElement.appendChild(citaElement);
                });
                // Adjuntar listeners a los botones de acción recién creados
                attachCitaActionListeners('#appointments-list');
            }
        } else {
            listElement.innerHTML = `<li class="placeholder text-red-500 dark:text-red-400">Error al cargar citas: ${data?.message || 'Respuesta inválida'}.</li>`;
        }
    } catch (error) {
        listElement.innerHTML = '<li class="placeholder text-red-500 dark:text-red-400">Error de comunicación al cargar citas.</li>';
        // El error ya fue notificado por fetchData
    }
}

/**
 * Carga la lista de médicos en el select del modal de agendar cita.
 */
async function cargarListaMedicos() {
    console.log("[Panel Paciente] Cargando lista de médicos para modal...");
    const selectMedico = document.getElementById('modal-schedule-medico'); // ID en perfil-usuario.php modal
    if (!selectMedico) { console.error("[Perfil Usuario] Select #modal-schedule-medico no encontrado."); return; }

    // Evitar recargar si ya tiene opciones (excepto la placeholder)
    if(selectMedico.options.length > 1) {
        console.log("[Panel Paciente] Lista de médicos ya cargada.");
        selectMedico.disabled = false; // Asegurarse que esté habilitado
        return;
    }

    selectMedico.disabled = true;
    selectMedico.innerHTML = '<option value="" disabled selected>Cargando médicos...</option>';

    try {
        const data = await fetchData('General/obtener_medicos.php'); // Endpoint correcto
        selectMedico.innerHTML = '<option value="" disabled selected>Seleccione un médico...</option>'; // Resetear

        if (data?.success && Array.isArray(data.medicos)) {
            if (data.medicos.length > 0) {
                data.medicos.forEach(medico => {
                    const optionText = `${medico.nombre} - ${medico.especialidad || 'General'}`;
                    selectMedico.add(new Option(optionText, medico.idMedico));
                });
                selectMedico.disabled = false; // Habilitar select
            } else {
                selectMedico.innerHTML = '<option value="" disabled>No hay médicos disponibles</option>';
            }
        } else {
            selectMedico.innerHTML = '<option value="" disabled>Error al cargar médicos</option>';
        }
    } catch (error) {
        selectMedico.innerHTML = '<option value="" disabled>Error de comunicación</option>';
        // Error ya notificado por fetchData
    }
}

/**
 * Carga y muestra el historial médico del paciente.
 * (IMPLEMENTACIÓN PENDIENTE - Requiere HTML en perfil-usuario.php)
 */
async function cargarHistorialUsuario() {
    console.log("[Panel Paciente] Cargando historial médico...");
    const historyList = document.querySelector('#history-list'); // Asegúrate que este ID exista en perfil-usuario.php
    if (!historyList) { console.warn("[Perfil Usuario] Lista #history-list no encontrada. Omitiendo carga de historial."); return; }

    historyList.innerHTML = '<li class="placeholder">Cargando historial...</li>';

    try {
        const data = await fetchData('Perfil/obtener_historial.php'); // Endpoint correcto
        historyList.innerHTML = ''; // Limpiar

        if (data?.success && Array.isArray(data.historial)) {
             if (data.historial.length === 0) {
                historyList.innerHTML = '<li class="placeholder">No hay registros en tu historial médico.</li>';
            } else {
                 data.historial.forEach(registro => {
                     const li = document.createElement('li');
                     li.className = "mb-3 pb-3 border-b border-gray-200 dark:border-gray-700 text-sm";
                     li.innerHTML = `
                         <div><strong>Fecha:</strong> ${formatDate(registro.fecha)}</div>
                         <div><strong>Médico:</strong> ${registro.nombreMedico || 'N/A'}</div>
                         ${registro.diagnostico ? `<div><strong>Diagnóstico/Notas:</strong><p class="mt-1 whitespace-pre-wrap">${registro.diagnostico}</p></div>` : ''}
                         ${registro.tratamiento ? `<div class="mt-1"><strong>Tratamiento:</strong><p class="mt-1 whitespace-pre-wrap">${registro.tratamiento}</p></div>` : ''}
                     `;
                     historyList.appendChild(li);
                 });
            }
        } else {
             historyList.innerHTML = `<li class="placeholder text-red-500 dark:text-red-400">Error al cargar historial: ${data?.message || '?'}.</li>`;
        }
    } catch(error) {
        historyList.innerHTML = '<li class="placeholder text-red-500 dark:text-red-400">Error de comunicación al cargar historial.</li>';
    }
}


/**
 * Abre el modal para agendar una cita.
 */
function openScheduleModal() {
    const modal = document.getElementById('schedule-modal'); // ID en perfil-usuario.php
    if (!modal) { console.error("[Perfil Usuario] Modal #schedule-modal no encontrado."); return; }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    console.log('[UI] Modal Agendar Cita abierto.');
    // Cargar médicos si es necesario (ya lo hace al abrir, pero por si acaso)
    cargarListaMedicos();
    // Opcional: enfocar el primer campo
    const firstInput = modal.querySelector('select, input');
    if (firstInput) setTimeout(() => firstInput.focus(), 50);
}

/**
 * Cierra el modal para agendar una cita.
 */
function closeScheduleModal() {
    const modal = document.getElementById('schedule-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    console.log('[UI] Modal Agendar Cita cerrado.');
    // Opcional: resetear formulario al cerrar
    const form = modal.querySelector('#schedule-appointment-form');
    if(form) form.reset();
}

// --- Funciones Panel Médico (`perfil-doctores.php`) ---

/**
 * Carga y muestra los datos del perfil del médico en el formulario.
 */
async function cargarDatosPerfilMedico() {
    console.log("[Panel Médico] Cargando datos del perfil...");
    const form = document.querySelector('#doctor-profile-form'); // ID en perfil-doctores.php
    if (!form) { console.error("[Perfil Médico] Formulario #doctor-profile-form no encontrado."); return; }
    setLoadingState(form, true, 'Cargando Perfil...');
    try {
        const data = await fetchData('Perfil/obtener_perfil.php');
        if (data?.success && data.perfil) {
            populateForm(form, data.perfil);
        }
    } catch (error) { /* Error ya manejado */ }
    finally { setLoadingState(form, false); }
}


/**
 * Crea el elemento LI para mostrar una cita en el panel del médico.
 * @param {object} cita - El objeto de datos de la cita.
 * @returns {HTMLLIElement} El elemento LI creado.
 */
function crearElementoCitaMedico(cita) {
    const li = document.createElement('li');
    const estadoBorde = getEstadoBordeClasses(cita.estado);
    li.className = `mb-4 p-4 border rounded-lg dark:border-gray-700 bg-white dark:bg-gray-800 ${estadoBorde} shadow-sm transition-shadow hover:shadow-md`;
    li.dataset.citaId = cita.idCita;

    const estadoBadge = getEstadoClasses(cita.estado);
    const horaFormateada = formatTime(cita.hora);
    const fechaFormateada = formatDate(cita.fecha);
    const esHoy = new Date().toISOString().split('T')[0] === cita.fecha;

    // Añadir un indicador visual si la cita es hoy
    const indicadorHoy = esHoy ? '<span class="ml-2 text-xs font-bold text-red-600 dark:text-red-400">[HOY]</span>' : '';

    li.innerHTML = `
        <div class="flex justify-between items-start mb-2 flex-wrap gap-x-2 gap-y-1">
             <div class="flex items-center gap-2">
                 <i class="bi bi-person-fill text-xl text-purple-600 dark:text-purple-400"></i>
                 <strong class="text-purple-700 dark:text-purple-300 text-lg font-semibold">Paciente: ${cita.nombrePaciente || 'N/A'}</strong>
                 ${cita.telefonoPaciente ? `<span class="text-xs text-gray-500 dark:text-gray-400">(<i class="bi bi-telephone text-xs"></i> ${cita.telefonoPaciente})</span>` : ''}
             </div>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full ${estadoBadge}">${cita.estado || '?'}</span>
        </div>

        <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 flex items-center gap-4 flex-wrap">
            <span><i class="bi bi-calendar-event mr-1 opacity-80"></i> ${fechaFormateada} ${indicadorHoy}</span>
            <span><i class="bi bi-clock mr-1 opacity-80"></i> ${horaFormateada}</span>
        </div>

        ${cita.motivo ? `
            <div class="text-sm mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <strong class="text-gray-700 dark:text-gray-300 font-medium">Motivo:</strong>
                <p class="mt-1 text-gray-600 dark:text-gray-400 whitespace-pre-wrap">${cita.motivo}</p>
            </div>` : ''}

        <div class="mt-4 flex flex-wrap gap-2 justify-start">
            ${(cita.estado === 'Programada') ? `
                <button data-action="confirmar" data-id="${cita.idCita}"
                        class="text-xs bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 rounded transition duration-200 flex items-center gap-1">
                    <i class="bi bi-check-circle"></i> Confirmar
                </button>` : ''}

            ${(cita.estado === 'Programada' || cita.estado === 'Confirmada') ? `
                <button data-action="cancelar-doctor" data-id="${cita.idCita}"
                        class="text-xs bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded transition duration-200 flex items-center gap-1">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>` : ''}

            ${(cita.estado === 'Confirmada') ? `
                <button data-action="completar" data-id="${cita.idCita}"
                        class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded transition duration-200 flex items-center gap-1">
                    <i class="bi bi-check-square"></i> Completada
                </button>` : ''}

            ${(cita.estado !== 'Cancelada Paciente' && cita.estado !== 'Cancelada Doctor' && cita.estado !== 'Completada') ? `
                <button data-action="cargar-notas" data-id="${cita.idCita}"
                        class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded transition duration-200 flex items-center gap-1">
                    <i class="bi bi-clipboard-plus"></i> Cargar p/ Notas
                </button>` : ''}
             <!-- Podrían ir otros botones como 'No Asistió' -->
             ${(cita.estado === 'Confirmada') ? `
                 <button data-action="no-asistio" data-id="${cita.idCita}"
                         class="text-xs bg-yellow-500 hover:bg-yellow-600 text-yellow-900 font-bold py-1 px-3 rounded transition duration-200 flex items-center gap-1">
                    <i class="bi bi-person-x"></i> No Asistió
                 </button>` : ''}
        </div>
    `;
    return li;
}


/**
 * Carga y muestra la lista de citas del médico.
 */
async function cargarCitasMedico() {
    console.log("[Panel Médico] Cargando citas...");
    const listElement = document.querySelector('#appointments-list-doctor'); // ID en perfil-doctores.php
    if (!listElement) { console.error("[Perfil Médico] Lista #appointments-list-doctor no encontrada."); return; }
    listElement.innerHTML = '<li class="placeholder">Cargando citas...</li>';
    try {
        const data = await fetchData('Citas/obtener_citas.php?rol=medico'); // Endpoint correcto
        listElement.innerHTML = '';
        if (data?.success && Array.isArray(data.citas)) {
            if (data.citas.length === 0) {
                listElement.innerHTML = '<li class="placeholder">No tiene citas programadas.</li>';
            } else {
                data.citas.forEach(cita => listElement.appendChild(crearElementoCitaMedico(cita)));
                attachCitaActionListeners('#appointments-list-doctor'); // Adjuntar listeners
            }
        } else {
             listElement.innerHTML = `<li class="placeholder text-red-500 dark:text-red-400">Error al cargar citas: ${data?.message || '?'}.</li>`;
        }
    } catch (error) {
        listElement.innerHTML = '<li class="placeholder text-red-500 dark:text-red-400">Error de comunicación al cargar citas.</li>';
    }
}

/**
 * Carga y muestra la lista de pacientes agendados para hoy para el médico.
 */
async function cargarPacientesHoy() {
    console.log("[Panel Médico] Cargando pacientes de hoy...");
    const listElement = document.querySelector('#patients-list-doctor'); // ID en perfil-doctores.php
    if (!listElement) { console.error("[Perfil Médico] Lista #patients-list-doctor no encontrada."); return; }
    listElement.innerHTML = '<li class="placeholder">Cargando pacientes...</li>';
    try {
        const data = await fetchData('Citas/obtener_pacientes_hoy.php'); // Endpoint correcto
        listElement.innerHTML = '';
        if (data?.success && Array.isArray(data.pacientes)) {
            if (data.pacientes.length === 0) {
                listElement.innerHTML = '<li class="placeholder">No hay pacientes agendados para hoy.</li>';
            } else {
                data.pacientes.forEach(paciente => {
                    const li = document.createElement('li');
                    li.className = 'mb-2 pb-2 border-b dark:border-gray-700 flex justify-between items-center text-sm flex-wrap gap-2';
                    const estadoBadge = getEstadoClasses(paciente.estado);
                    const horaFormateada = formatTime(paciente.hora);

                    li.innerHTML = `
                        <span class="flex items-center gap-2">
                            <i class="bi bi-clock text-gray-500"></i> ${horaFormateada} -
                            <strong class="ml-1 text-gray-800 dark:text-gray-100">${paciente.nombrePaciente || 'N/A'}</strong>
                            <span class="text-xs px-1.5 py-0.5 rounded ${estadoBadge}">${paciente.estado || 'N/A'}</span>
                        </span>
                        ${(paciente.estado === 'Confirmada' || paciente.estado === 'Programada') ? `
                            <button data-action="cargar-notas" data-id="${paciente.idCita}"
                                    class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded flex items-center gap-1">
                                <i class="bi bi-clipboard-plus"></i> Cargar p/ Notas
                            </button>` : ''}
                    `;
                    listElement.appendChild(li);
                });
                attachCitaActionListeners('#patients-list-doctor'); // Adjuntar listeners a botones
            }
        } else {
             listElement.innerHTML = `<li class="placeholder text-red-500 dark:text-red-400">Error al cargar pacientes: ${data?.message || '?'}.</li>`;
        }
    } catch (error) {
        listElement.innerHTML = '<li class="placeholder text-red-500 dark:text-red-400">Error de comunicación.</li>';
    }
}

/**
 * Guarda las notas de consulta introducidas por el médico.
 * @param {Event} event - El evento submit del formulario.
 */
async function guardarNotasConsulta(event) {
    event.preventDefault(); // Prevenir recarga de página
    const form = event.target; // El formulario que disparó el evento
    if (!form) return;

    console.log("%c[Panel Médico] Intentando guardar notas de consulta...", 'color: purple; font-weight: bold;');
    const formData = new FormData(form);
    const notasTextarea = form.querySelector('#consulta-notas'); // ID en perfil-doctores.php

    // Recuperar el ID de la cita seleccionada (debe establecerse con 'cargar-notas')
    if (!window.selectedCitaIdForNotes) { // Usar una variable global o un atributo data en el form
        showNotification("Por favor, selecciona una cita ('Cargar p/ Notas') antes de guardar.", 'warning', 'Seleccione Cita');
        return;
    }
    formData.append('idCitaActual', window.selectedCitaIdForNotes);

    // Validación simple
    if (!notasTextarea || notasTextarea.value.trim() === '') {
         showNotification("El campo de notas no puede estar vacío.", 'warning', 'Notas Requeridas');
         notasTextarea?.classList.add('border-red-500');
         notasTextarea?.focus();
         return;
    } else {
         notasTextarea.classList.remove('border-red-500');
    }

    setLoadingState(form, true, 'Guardando...');

    try {
        const data = await fetchData('Citas/guardar_notas_consulta.php', { method: 'POST', body: formData });
        if (data?.success) {
            showNotification(data.message || "Notas guardadas correctamente.", 'success');
            form.reset(); // Limpiar textarea
            window.selectedCitaIdForNotes = null; // Resetear cita seleccionada
            // Opcional: Desmarcar visualmente la cita seleccionada
            document.querySelectorAll('.cita-seleccionada-para-notas').forEach(el => el.classList.remove('cita-seleccionada-para-notas', 'bg-blue-100', 'dark:bg-blue-900'));
            // Opcional: Recargar listas para reflejar cambios si aplica
            // cargarCitasMedico();
            // cargarPacientesHoy();
        } // Errores ya manejados por fetchData/showNotification
    } catch (error) { /* Error ya manejado */ }
    finally { setLoadingState(form, false); }
}


// ========================================================================
// == 5. MANEJADORES DE EVENTOS GLOBALES Y DELEGADOS =====================
// ========================================================================

/**
 * Cambia el estado de una cita mediante una llamada a la API.
 * Muestra modales de confirmación y feedback.
 * @param {number|string} idCita - El ID de la cita a modificar.
 * @param {string} nuevoEstado - El nuevo estado deseado (ej. "Confirmada", "Cancelada Paciente").
 */
async function cambiarEstadoCita(idCita, nuevoEstado) {
    console.log(`%c[Acción Cita] Solicitando: ${nuevoEstado} para cita ${idCita}`, 'color: teal;');
    const currentPath = window.location.pathname; // Para saber qué lista recargar

    // --- Configuración de SweetAlert ---
    let confirmOptions = {
        title: `¿Cambiar estado?`,
        text: `La cita #${idCita} pasará a estado "${nuevoEstado}".`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6', // Azul por defecto
        cancelButtonColor: '#6b7280', // Gris
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'No, cancelar'
    };

    // Personalizar según la acción
    if (nuevoEstado === 'Confirmada') {
        confirmOptions.title = '¿Confirmar esta cita?';
        confirmOptions.text = 'Se notificará al paciente (próximamente).';
        confirmOptions.icon = 'info';
        confirmOptions.confirmButtonColor = '#10B981'; // Verde
    } else if (nuevoEstado.includes('Cancelada')) {
        confirmOptions.title = '¿Cancelar esta cita?';
        confirmOptions.text = 'Esta acción no se puede deshacer.';
        confirmOptions.icon = 'warning';
        confirmOptions.confirmButtonColor = '#EF4444'; // Rojo
    } else if (nuevoEstado === 'Completada') {
        confirmOptions.title = '¿Marcar como completada?';
        confirmOptions.text = 'La cita se registrará como atendida.';
        confirmOptions.icon = 'success'; // Usar icono de éxito
        confirmOptions.confirmButtonColor = '#4f46e5'; // Indigo
    } else if (nuevoEstado === 'No Asistió') {
         confirmOptions.title = '¿Marcar como "No Asistió"?';
         confirmOptions.text = 'Se registrará la inasistencia del paciente.';
         confirmOptions.icon = 'warning';
         confirmOptions.confirmButtonColor = '#f59e0b'; // Ambar/Amarillo
    }

    // --- Mostrar Confirmación ---
    const result = await Swal.fire(confirmOptions);
    if (!result.isConfirmed) {
        console.log('[Acción Cita] Cambio de estado cancelado por el usuario.');
        return; // Salir si el usuario cancela
    }

    // --- Ejecutar Cambio ---
    console.log('[Acción Cita] Confirmado. Ejecutando cambio de estado...');
    Swal.fire({ // Mostrar indicador de carga
        title: 'Actualizando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const formData = new FormData();
        formData.append('idCita', idCita);
        formData.append('nuevoEstado', nuevoEstado);

        const data = await fetchData('Citas/cambiar_estado_cita.php', { method: 'POST', body: formData });

        if (data?.success) {
            Swal.fire({
                title: '¡Actualizado!',
                text: data.message || `Estado cambiado a "${nuevoEstado}".`,
                icon: 'success',
                timer: 2000, // Cerrar automáticamente
                timerProgressBar: true
            });
            // Recargar la lista correspondiente
            if (currentPath.includes('perfil-doctores.php')) {
                cargarCitasMedico();
                cargarPacientesHoy(); // Recargar pacientes del día también
            } else if (currentPath.includes('perfil-usuario.php')) {
                cargarCitasUsuario();
            }
        } else {
            // Si fetchData no lanzó error pero success es false
             Swal.fire('Error', data?.message || 'No se pudo actualizar el estado.', 'error');
        }
    } catch (error) {
        // Error ya notificado por fetchData, Swal.fire de error se muestra allí
        console.error('[Acción Cita] Error al cambiar estado:', error);
        // Cerrar el Swal de carga si sigue abierto
        if (Swal.isLoading()) { Swal.close(); }
        // La notificación de error ya se mostró en fetchData
    }
}

/**
 * Adjunta un listener de eventos delegado a un contenedor de citas
 * para manejar clics en botones de acción (confirmar, cancelar, etc.).
 * @param {string} containerSelector - Selector CSS del elemento UL que contiene las citas.
 */
function attachCitaActionListeners(containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) {
        console.warn(`[Listeners] Contenedor de citas no encontrado: ${containerSelector}`);
        return;
    }

    // Limpiar listener previo para evitar duplicados si se llama varias veces
    const previousListener = container.handleCitaClick;
    if (previousListener) {
        container.removeEventListener('click', previousListener);
        console.log(`[Listeners] Listener previo removido de ${containerSelector}`);
    }

    // Crear el nuevo listener
    const handleCitaActionClick = (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button || button.disabled) return; // Ignorar si no es un botón de acción o está deshabilitado

        const action = button.dataset.action;
        const idCita = button.dataset.id;
        console.log(`%c[Click Acción Cita] Contenedor=${containerSelector}, Acción=${action}, ID=${idCita}`, 'color: darkcyan;');

        if (!idCita) {
            console.error("[Click Acción Cita] Botón sin atributo data-id válido!");
            return;
        }

        // Deshabilitar botón temporalmente para evitar clics múltiples
        // excepto para 'cargar-notas' que es una acción de UI inmediata.
        if(action !== 'cargar-notas') button.disabled = true;

        // Función para rehabilitar el botón después de la acción asíncrona
        const reEnableButton = () => { if(action !== 'cargar-notas') button.disabled = false; };

        // Ejecutar la acción correspondiente
        switch (action) {
            case 'confirmar':
                cambiarEstadoCita(idCita, 'Confirmada').finally(reEnableButton);
                break;
            case 'cancelar-doctor':
                cambiarEstadoCita(idCita, 'Cancelada Doctor').finally(reEnableButton);
                break;
            case 'cancelar-paciente':
                cambiarEstadoCita(idCita, 'Cancelada Paciente').finally(reEnableButton);
                break;
            case 'completar':
                cambiarEstadoCita(idCita, 'Completada').finally(reEnableButton);
                break;
             case 'no-asistio':
                 cambiarEstadoCita(idCita, 'No Asistió').finally(reEnableButton);
                 break;
            case 'cargar-notas':
                // Guardar ID globalmente (o en un data attribute del form de notas)
                window.selectedCitaIdForNotes = idCita;
                console.log(`[Acción Cita] Cita ${idCita} seleccionada para notas.`);

                 // Resaltar visualmente la cita seleccionada y quitar resaltado previo
                 container.querySelectorAll('li.cita-seleccionada-para-notas').forEach(li => {
                    li.classList.remove('cita-seleccionada-para-notas', 'bg-blue-100', 'dark:bg-blue-900/50', 'ring-2', 'ring-blue-500');
                 });
                 const targetLi = button.closest('li[data-cita-id]');
                 if(targetLi){
                     targetLi.classList.add('cita-seleccionada-para-notas', 'bg-blue-100', 'dark:bg-blue-900/50', 'ring-2', 'ring-blue-500');
                 }

                // Notificación Toast sutil
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: `Cita #${idCita} seleccionada`,
                    text: 'Puede añadir notas médicas ahora.',
                    showConfirmButton: false,
                    timer: 2500, // Más tiempo para leer
                    timerProgressBar: true
                });

                // Enfocar el textarea de notas
                document.querySelector('#consulta-notes-form textarea')?.focus();
                break;
            default:
                console.warn(`[Click Acción Cita] Acción desconocida: ${action}`);
                showNotification(`Acción no implementada: ${action}`, 'warning');
                reEnableButton(); // Rehabilitar si la acción no se reconoció
                break;
        }
    };

    // Adjuntar el nuevo listener
    container.addEventListener('click', handleCitaActionClick);
    // Guardar referencia al listener en el propio elemento para poder quitarlo después
    container.handleCitaClick = handleCitaActionClick;
    console.log(`[Listeners] Listener delegado adjuntado a ${containerSelector}`);
}


// ========================================================================
// == 6. EJECUCIÓN PRINCIPAL Y ROUTING POR PÁGINA ========================
// ========================================================================

document.addEventListener('DOMContentLoaded', function () {
    console.log('%c[MediAgenda] DOM Cargado. Inicializando scripts.js...', 'color: green; font-weight: bold;');

    initializeCommonUI(); // Inicializar Dark Mode, Menú, etc.

    // --- Lógica Específica por Página ---
    const currentPath = window.location.pathname;
    console.log(`[Routing] Path actual: ${currentPath}`);

    // --- Página: Registro / Login (`registro.php`) ---
    if (currentPath.includes('registro.php')) {
        console.log('[Routing] Ejecutando lógica para registro.php');

        // --- Manejo de Pestañas (Tabs) ---
        const tabs = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        if (tabs.length > 0 && tabContents.length > 0) {
            const showTab = (targetId) => {
                let found = false;
                tabContents.forEach(content => {
                    if (content.id === targetId) {
                        content.style.display = 'block'; // Mostrar tab activa
                         // Opcional: añadir animación fadeIn
                         // content.classList.add('animate-fade-in', 'visible');
                        found = true;
                    } else {
                        content.style.display = 'none'; // Ocultar otras
                         // content.classList.remove('animate-fade-in', 'visible');
                    }
                });
                // Actualizar estilo de los botones de tab
                tabs.forEach(button => {
                    const isActive = button.getAttribute('data-target') === targetId;
                    button.classList.toggle('text-blue-600', isActive);
                    button.classList.toggle('dark:text-blue-400', isActive);
                    button.classList.toggle('border-blue-500', isActive);
                    button.classList.toggle('font-semibold', isActive); // Resaltar activo
                    button.classList.toggle('text-gray-600', !isActive);
                    button.classList.toggle('dark:text-gray-400', !isActive);
                    button.classList.toggle('border-transparent', !isActive);
                    button.classList.toggle('font-medium', !isActive);
                });
                if(found) console.log(`[UI Tabs] Mostrando tab: ${targetId}`);
                return found;
            };

            // Añadir listeners a los botones
            tabs.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = button.getAttribute('data-target');
                    if (target) {
                        showTab(target);
                        // Actualizar hash en URL sin recargar (opcional)
                        // history.pushState(null, null, `#${target}`);
                    }
                });
            });

            // Mostrar tab inicial (login por defecto o según hash)
            let initialTab = 'login';
            if (window.location.hash && document.getElementById(window.location.hash.substring(1))) {
                initialTab = window.location.hash.substring(1);
            }
            // Si la tab inicial no existe, mostrar la primera
            if (!showTab(initialTab) && tabContents.length > 0) {
                showTab(tabContents[0].id);
            }
        } else { console.warn('[Routing] Elementos de tabs no encontrados en registro.php.'); }

        // --- Manejadores de Formularios ---
        handleFormSubmit('#login-form', 'Auth/login.php', (data) => {
             if (data?.success && data.rol) {
                 // Redirección basada en rol
                 const redirectMap = {
                     'paciente': '/perfil-usuario.php',
                     'medico': '/perfil-doctores.php',
                     'admin': '/panel-admin-sistema.php',
                 };
                 const redirectUrl = redirectMap[data.rol.toLowerCase()] || '/index.php'; // Default a index si rol raro
                 if (!redirectMap[data.rol.toLowerCase()]) {
                     showNotification("Rol desconocido. Redirigiendo al inicio.", 'warning');
                 }
                 // Pequeño delay para que el usuario vea el mensaje de éxito (opcional)
                 // showNotification(data.message || "Inicio de sesión exitoso.", 'success');
                 // setTimeout(() => { window.location.href = redirectUrl; }, 500);
                 window.location.href = redirectUrl; // Redirección inmediata
             } else {
                  // Mensaje de error ya mostrado por fetchData o el callback de error
                  console.error("[Login] Falló el inicio de sesión:", data?.message);
             }
         });

        handleFormSubmit('#patient-register-form', 'Auth/registrar_paciente.php', (data, form) => {
             // Éxito: mostrar mensaje y cambiar a la pestaña de login
             showNotification(data.message || "Registro de paciente exitoso. Ahora puedes iniciar sesión.", 'success');
             form.reset();
             const showTabLogin = document.querySelector('.tab-button[data-target="login"]');
             if(showTabLogin) showTabLogin.click(); // Simular click para cambiar tab
         });

        handleFormSubmit('#doctor-register-form', 'Auth/registrar_medico.php', (data, form) => {
            showNotification(data.message || "Registro de médico exitoso. Ahora puedes iniciar sesión.", 'success');
            form.reset();
            const showTabLogin = document.querySelector('.tab-button[data-target="login"]');
            if(showTabLogin) showTabLogin.click();
         });

        // Listener para el formulario de Olvidó Contraseña (manejado por handleFormSubmit)
        handleFormSubmit('#forgot-password-form', 'Auth/solicitar_reset.php', (data, form) => {
            // La respuesta del backend siempre es {success: true} por seguridad,
            // mostramos un mensaje genérico informando al usuario.
             showNotification(
                 'Si existe una cuenta asociada a ese correo, recibirás un enlace para restablecer tu contraseña en breve.',
                 'info', // Usar 'info'
                 'Solicitud Enviada'
             );
            form.reset();
             // Cerrar la modal (si la lógica de cierre está aquí y no localmente)
             // closeForgotModal(); // O closeForgotModalAnimate();
             // Nota: Si la lógica de cierre animado está en registro.php, no se necesita nada aquí.
        });

    }

    // --- Página: Panel Paciente (`perfil-usuario.php`) ---
    else if (currentPath.includes('perfil-usuario.php')) {
        console.log('[Routing] Ejecutando lógica para perfil-usuario.php');
        cargarDatosPerfilUsuario(); // Cargar datos del formulario de perfil
        cargarCitasUsuario();      // Cargar lista de citas
        cargarHistorialUsuario();  // Cargar historial médico
        // cargarListaMedicos() se llama automáticamente al abrir el modal

        // --- Manejadores de Formularios ---
        handleFormSubmit('#update-profile-form', 'Perfil/actualizar_perfil.php', (data) => {
             showNotification(data.message || "Perfil actualizado correctamente.", 'success');
             // Opcional: Recargar datos del perfil si algo cambió visualmente fuera del form
             // cargarDatosPerfilUsuario();
         });
        handleFormSubmit('#schedule-appointment-form', 'Citas/programar_cita.php', (data, form) => {
             showNotification(data.message || "Cita programada con éxito.", 'success');
             form.reset();
             closeScheduleModal(); // Cerrar modal
             cargarCitasUsuario(); // Refrescar lista de citas
         });

        // --- Listeners para Modal Agendar Cita ---
        const btnShowSchedule = document.getElementById('btn-show-schedule');
        const linkShowSchedule = document.querySelector('a[href="#schedule"]'); // Si existe enlace en menú
        const scheduleModal = document.getElementById('schedule-modal');
        const btnCloseSchedule = document.getElementById('close-schedule-modal');

        const openModalHandler = (e) => { e.preventDefault(); openScheduleModal(); };
        if (btnShowSchedule) btnShowSchedule.addEventListener('click', openModalHandler);
        if (linkShowSchedule) linkShowSchedule.addEventListener('click', openModalHandler);
        if (btnCloseSchedule) btnCloseSchedule.addEventListener('click', closeScheduleModal);
        if (scheduleModal) {
            // Cerrar al hacer clic en el overlay
            scheduleModal.addEventListener('click', (e) => { if (e.target === scheduleModal) closeScheduleModal(); });
        }

    }

    // --- Página: Panel Médico (`perfil-doctores.php`) ---
    else if (currentPath.includes('perfil-doctores.php')) {
        console.log('[Routing] Ejecutando lógica para perfil-doctores.php');
        cargarDatosPerfilMedico(); // Cargar datos del formulario de perfil
        cargarCitasMedico();       // Cargar lista de citas
        cargarPacientesHoy();      // Cargar pacientes del día

        // --- Manejadores de Formularios ---
        handleFormSubmit('#doctor-profile-form', 'Perfil/actualizar_perfil.php', (data) => {
            showNotification(data.message || "Perfil actualizado correctamente.", 'success');
        });

        // Listener para el formulario de notas de consulta
        const notesForm = document.getElementById('consulta-notes-form');
        if(notesForm) {
            notesForm.addEventListener('submit', guardarNotasConsulta);
            console.log('[Panel Médico] Listener añadido a #consulta-notes-form');
        } else { console.warn('[Panel Médico] Formulario #consulta-notes-form no encontrado.'); }

    }

    // --- Página: Panel Admin (`panel-admin-sistema.php`) ---
    // La lógica específica del panel admin se encuentra en `panel-admin.js`,
    // que ya se carga en esa página. No se necesita lógica adicional aquí en `scripts.js`.
    else if (currentPath.includes('panel-admin-sistema.php')) {
         console.log('[Routing] Lógica principal para panel-admin-sistema.php se encuentra en panel-admin.js.');
    }

    // --- Otras Páginas (ej. index.php) ---
    else {
        console.log('[Routing] Sin lógica JS específica para ejecutar en esta página.');
    }

    console.log('%c[MediAgenda] Inicialización de scripts.js completada.', 'color: green; font-weight: bold;');
}); // Fin de DOMContentLoaded