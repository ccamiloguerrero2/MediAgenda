// --- MediAgenda panel-admin.js (Lógica Panel Admin) ---

document.addEventListener('DOMContentLoaded', function () {
    console.log('[MediAgenda] Scripts inicializados (panel-admin.js).');

    // ========================================================================
    // == 1. CONFIGURACIÓN GLOBAL Y VARIABLES (Admin) ========================
    // ========================================================================
    const backendUrl = 'mediagenda-backend/'; // Asegúrate que coincida
    const body = document.body; // Para posible modo oscuro si se necesita
    let notificationArea = document.getElementById('notification-area'); // Buscar el área global

    // --- Selectores de Elementos del Panel Admin ---
    const userListLoading = document.getElementById('admin-user-list-loading');
    const userTable = document.getElementById('admin-user-table');
    const userTableBody = document.getElementById('admin-user-list-body');
    const userFormContainer = document.getElementById('admin-user-form-container');
    const userForm = document.getElementById('admin-user-form');
    const userFormTitle = document.getElementById('admin-user-form-title');
    const userIdInput = document.getElementById('admin-user-id'); // Campo hidden
    const userPasswordInput = document.getElementById('admin-user-password'); // Campo password
    const btnMostrarCrear = document.getElementById('btn-mostrar-crear-usuario');
    const btnCancelarEdicion = document.getElementById('btn-cancelar-edicion-usuario');
    // No necesitamos btnGuardarUsuario aquí, lo maneja el form submit


    // ========================================================================
    // == 2. FUNCIONES AUXILIARES DUPLICADAS (Necesarias aquí) ===============
    // ========================================================================

    // --- Función para Mostrar Notificaciones ---
    // (Copia exacta de la función en scripts.js)
    function showNotification(message, type = 'info') {
        if (!notificationArea) { // Intentar encontrarla de nuevo si falló al inicio
             notificationArea = document.getElementById('notification-area');
             if (!notificationArea) { console.error("Área de notificación no encontrada."); alert(message); return; }
        }
        const notification = document.createElement('div'); notification.textContent = message;
        let baseClasses = 'px-4 py-3 rounded-md shadow-lg text-sm font-medium animate-fade-in'; let typeClasses = '';
        switch (type) { /* ... casos success, error, warning, default ... */
             case 'success': typeClasses = 'bg-green-100 border border-green-300 text-green-800 dark:bg-green-900 dark:text-green-300 dark:border-green-700'; break;
            case 'error':   typeClasses = 'bg-red-100 border border-red-300 text-red-800 dark:bg-red-900 dark:text-red-300 dark:border-red-700'; break;
            case 'warning': typeClasses = 'bg-yellow-100 border border-yellow-300 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 dark:border-yellow-700'; break;
            default:        typeClasses = 'bg-blue-100 border border-blue-300 text-blue-800 dark:bg-blue-900 dark:text-blue-300 dark:border-blue-700';
         }
        notification.className = `${baseClasses} ${typeClasses}`; notificationArea.appendChild(notification);
        setTimeout(() => { notification.style.transition = 'opacity 0.5s ease-out'; notification.style.opacity = '0'; setTimeout(() => notification.remove(), 500); }, 5000);
    }

    // --- Función Auxiliar para Fetch ---
    // (Copia exacta de la función en scripts.js)
    async function fetchData(url, options = {}) {
        try {
            const response = await fetch(backendUrl + url, options);
            if (!response.ok) { let eD={m:`HTTP ${response.status}`,c:response.status};try{const eJ=await response.json();eD.m=eJ.message||eD.m;}catch(e){} const err=new Error(eD.m);err.code=eD.c;console.error(`[Fetch] Error ${err.code||''}: ${err.message} en ${url}`);throw err;}
            if(response.status===204) return null; return await response.json();
        } catch (error) {
            if(!error.code&&!(error instanceof SyntaxError)){console.error('[Fetch] Error Red:',error);showNotification('Error comunicación.','error');throw new Error('Error comunicación.');}
            else if(error instanceof SyntaxError){console.error('[Fetch] JSON inválido:',error);showNotification('Respuesta inválida.','error');throw new Error('Respuesta inválida.');}
            else{showNotification(`Error: ${error.message||'?'}`, 'error');throw error;}
        }
    }

    // --- Helper para Rellenar Formularios ---
    // (Copia exacta de la función en scripts.js)
    function populateForm(form, data) { if (!form || !data) return; for (const k in data) { const f = form.querySelector(`[name="${k}"]`); if (f) f.value = data[k] ?? ''; } }

    // --- Helper para Estado de Carga de Botones ---
    // (Copia exacta de la función en scripts.js)
    function setLoadingState(form, isLoading, loadingText = 'Cargando...') { if (!form) return; const b = form.querySelector('button[type="submit"]'); if (!b) return; if (isLoading) { b.dataset.originalText = b.textContent; b.disabled = true; b.textContent = loadingText; } else { b.disabled = false; b.textContent = b.dataset.originalText || 'Enviar'; } }


    // ========================================================================
    // == 3. LÓGICA ESPECÍFICA DEL PANEL ADMIN ===============================
    // ========================================================================

    // --- Función para Renderizar la Tabla de Usuarios ---
    function renderUserTable(usuarios) {
        if (!userTableBody) return; userTableBody.innerHTML = '';
        if (!usuarios || usuarios.length === 0) { userTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No hay usuarios.</td></tr>'; return; }
        usuarios.forEach(user => { const row = userTableBody.insertRow(); row.innerHTML = `<td class="px-6 py-4 ...">${user.idUsuario}</td><td class="px-6 py-4 ...">${user.nombre}</td><td class="px-6 py-4 ...">${user.email}</td><td class="px-6 py-4 ...">${user.rol}</td><td class="px-6 py-4 ..."><button data-action="editar-usuario" data-id="${user.idUsuario}" class="text-indigo-600 ...">Editar</button><button data-action="eliminar-usuario" data-id="${user.idUsuario}" class="text-red-600 ... ml-2">Eliminar</button></td>`; }); // Clases abreviadas
    }

    // --- Función para Cargar la Lista de Usuarios ---
    async function cargarListaUsuariosAdmin() {
        console.log("[Admin Panel] Cargando lista de usuarios...");
        if(userListLoading) userListLoading.style.display = 'block'; if(userTable) userTable.classList.add('hidden');
        try {
            const data = await fetchData('admin_obtener_usuarios.php');
            if (data?.success && data.usuarios) { renderUserTable(data.usuarios); if(userTable) userTable.classList.remove('hidden'); }
            else { if (userTableBody) userTableBody.innerHTML = `<tr><td colspan="5" class="err">Error: ${data?.message || '?'}</td></tr>`; if(userTable) userTable.classList.remove('hidden'); }
        } catch (error) { if (userTableBody) userTableBody.innerHTML = `<tr><td colspan="5" class="err">Error comunicación.</td></tr>`; if(userTable) userTable.classList.remove('hidden'); }
        finally { if(userListLoading) userListLoading.style.display = 'none'; }
    }

    // --- Función para Abrir Formulario de Edición ---
    async function abrirFormularioEdicionUsuario(idUsuario) {
        console.log(`[Admin Panel] Abriendo form para editar ID: ${idUsuario}`);
        if (!userForm || !userFormContainer) return;
        userForm.reset(); userIdInput.value = idUsuario; userFormTitle.textContent = `Editar Usuario #${idUsuario}`;
        const btnGuardar = userForm.querySelector('button[type="submit"]'); if(btnGuardar) btnGuardar.textContent = 'Actualizar Usuario';
        if(userPasswordInput) { userPasswordInput.placeholder = 'Dejar vacío para no cambiar'; userPasswordInput.required = false; }
        userFormContainer.style.display = 'block'; userForm.classList.add('opacity-50');
        try {
            const dataAll = await fetchData('admin_obtener_usuarios.php'); let userData = null;
            if(dataAll?.success && dataAll.usuarios) userData = dataAll.usuarios.find(u => u.idUsuario == idUsuario);
            if (userData) { populateForm(userForm, userData); }
            else { showNotification(`Error: Datos no encontrados para ID ${idUsuario}.`, 'error'); userFormContainer.style.display = 'none'; }
        } catch (error) { userFormContainer.style.display = 'none'; } finally { userForm.classList.remove('opacity-50'); }
    }

    // --- Función para Abrir Formulario de Creación ---
    function abrirFormularioCreacionUsuario() {
        console.log(`[Admin Panel] Abriendo form para crear.`);
        if (!userForm || !userFormContainer) return;
        userForm.reset(); userIdInput.value = ''; userFormTitle.textContent = 'Crear Nuevo Usuario';
        const btnGuardar = userForm.querySelector('button[type="submit"]'); if(btnGuardar) btnGuardar.textContent = 'Guardar Usuario';
        if(userPasswordInput) { userPasswordInput.placeholder = 'Mínimo 6 caracteres'; userPasswordInput.required = true; }
        userFormContainer.style.display = 'block'; userForm.querySelector('[name="nombre"]')?.focus();
    }

    // --- Función para Eliminar Usuario ---
    async function eliminarUsuario(idUsuario) {
        console.log(`[Admin Panel] Solicitando eliminar ID: ${idUsuario}`);
        if (!confirm(`¿Seguro de eliminar al usuario ID ${idUsuario}?`)) return;
        try {
            const fd = new FormData(); fd.append('idUsuario', idUsuario);
            const data = await fetchData('admin_eliminar_usuario.php', { method: 'POST', body: fd });
            if (data?.success) { showNotification(data.message || 'Usuario eliminado.', 'success'); cargarListaUsuariosAdmin(); }
        } catch (error) { /* Handled by fetchData */ }
    }

    // --- Listener Delegado para Acciones en Tabla Usuarios ---
    function attachAdminUserActionListeners() {
        const container = userTable; if (!container) return;
        const currentListener = container._handleAdminUserClick; // Guardar referencia
        if (currentListener) container.removeEventListener('click', currentListener);

        const handleAdminUserClick = (event) => {
            const button = event.target.closest('button[data-action]'); if (!button) return;
            const action = button.dataset.action; const idUsuario = button.dataset.id;
            console.log(`%c[Click Admin User] Acción=${action}, ID=${idUsuario}`, 'color: purple;');
            if (!idUsuario) return;
            if (action === 'editar-usuario') abrirFormularioEdicionUsuario(idUsuario);
            else if (action === 'eliminar-usuario') eliminarUsuario(idUsuario);
        };
        container.addEventListener('click', handleAdminUserClick);
        container._handleAdminUserClick = handleAdminUserClick; // Guardar referencia nueva
        console.log('[Listeners] Listener delegado adjuntado a #admin-user-table');
    }

    // --- Listener para el Formulario de Crear/Editar Usuario ---
    if (userForm) {
        const submitBtnAdmin = userForm.querySelector('button[type="submit"]');
        const originalTextAdmin = submitBtnAdmin ? submitBtnAdmin.textContent : 'Guardar';
        userForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const idUser = userIdInput.value; const esEdicion = idUser && idUser !== '';
            const scriptPHP = esEdicion ? 'admin_actualizar_usuario.php' : 'admin_crear_usuario.php';
            console.log(`%c[Submit Admin] Form ${esEdicion?'EDITAR':'CREAR'} -> ${scriptPHP}`, 'color:orange;');
            const formData = new FormData(userForm);
            if(esEdicion && formData.get('password')===''){ formData.delete('password'); } // No enviar pass vacía en edición
            else if (!esEdicion && (formData.get('password')?.length ?? 0) < 6) { showNotification('Contraseña requerida (min 6 car.) al crear.', 'error'); return; }
            setLoadingState(userForm, true, 'Guardando...');
            try {
                const data = await fetchData(scriptPHP, { method: 'POST', body: formData });
                if (data?.success) { showNotification(data.message || `Usuario ${esEdicion?'act.':'creado'}.`, 'success'); userForm.reset(); userFormContainer.style.display = 'none'; cargarListaUsuariosAdmin(); }
            } catch (error) { /* Handled */ }
            finally { setLoadingState(userForm, false, originalTextAdmin); }
        });
    }

    // ========================================================================
    // == 4. EJECUCIÓN INICIAL DEL PANEL ADMIN ===============================
    // ========================================================================
    if (userFormContainer) userFormContainer.style.display = 'none'; // Ocultar form
    cargarListaUsuariosAdmin(); // Cargar la tabla
    attachAdminUserActionListeners(); // Poner listeners en la tabla

    // Listeners botones del form
    if(btnMostrarCrear) btnMostrarCrear.addEventListener('click', abrirFormularioCreacionUsuario);
    if(btnCancelarEdicion) btnCancelarEdicion.addEventListener('click', () => { if(userFormContainer) userFormContainer.style.display = 'none'; userForm?.reset(); });

    console.log('[MediAgenda] Inicialización panel-admin.js finalizada.');

}); // Fin de DOMContentLoaded