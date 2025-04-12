// --- MediAgenda panel-admin.js (Lógica Panel Admin) ---

document.addEventListener('DOMContentLoaded', function () {
    console.log('[MediAgenda] Scripts inicializados (panel-admin.js).');

    // ========================================================================
    // == 1. CONFIGURACIÓN GLOBAL Y VARIABLES (Admin) ========================
    // ========================================================================
    // backendUrl ahora es global, definida en scripts.js
    const body = document.body; // Para posible modo oscuro si se necesita
    // notificationArea ahora es global, gestionada en scripts.js

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
    // == 2. FUNCIONES AUXILIARES DUPLICADAS (ELIMINADAS) ===================
    // ========================================================================
    // Las funciones showNotification, fetchData, populateForm, setLoadingState
    // ahora se usan desde las globales definidas en scripts.js


    // ========================================================================
    // == 3. LÓGICA ESPECÍFICA DEL PANEL ADMIN ===============================
    // ========================================================================

    // --- Función para Renderizar la Tabla de Usuarios --- (Sin cambios, no usaba helpers directamente)
    function renderUserTable(usuarios) {
        if (!userTableBody) return; userTableBody.innerHTML = '';
        if (!usuarios || usuarios.length === 0) { userTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No hay usuarios.</td></tr>'; return; }
        usuarios.forEach(user => { const row = userTableBody.insertRow(); row.innerHTML = `<td class="px-6 py-4 ...">${user.idUsuario}</td><td class="px-6 py-4 ...">${user.nombre}</td><td class="px-6 py-4 ...">${user.email}</td><td class="px-6 py-4 ...">${user.rol}</td><td class="px-6 py-4 ..."><button data-action="editar-usuario" data-id="${user.idUsuario}" class="text-indigo-600 ...">Editar</button><button data-action="eliminar-usuario" data-id="${user.idUsuario}" class="text-red-600 ... ml-2">Eliminar</button></td>`; }); // Clases abreviadas
    }

    // --- Función para Cargar la Lista de Usuarios --- (Ahora usa fetchData global)
    async function cargarListaUsuariosAdmin() {
        console.log("[Admin Panel] Cargando lista de usuarios...");
        if(userListLoading) userListLoading.style.display = 'block'; if(userTable) userTable.classList.add('hidden');
        try {
            const data = await fetchData('admin_obtener_usuarios.php'); // Usa fetchData global
            if (data?.success && data.usuarios) { renderUserTable(data.usuarios); if(userTable) userTable.classList.remove('hidden'); }
            else { if (userTableBody) userTableBody.innerHTML = `<tr><td colspan="5" class="err">Error: ${data?.message || '?'}</td></tr>`; if(userTable) userTable.classList.remove('hidden'); }
        } catch (error) { /* Error ya notificado por fetchData */ if (userTableBody) userTableBody.innerHTML = `<tr><td colspan="5" class="err">Error comunicación.</td></tr>`; if(userTable) userTable.classList.remove('hidden'); }
        finally { if(userListLoading) userListLoading.style.display = 'none'; }
    }

    // --- Función para Abrir Formulario de Edición --- (Usa fetchData y populateForm globales)
    async function abrirFormularioEdicionUsuario(idUsuario) {
        console.log(`[Admin Panel] Abriendo form para editar ID: ${idUsuario}`);
        if (!userForm || !userFormContainer) return;
        userForm.reset(); userIdInput.value = idUsuario; userFormTitle.textContent = `Editar Usuario #${idUsuario}`;
        const btnGuardar = userForm.querySelector('button[type="submit"]'); if(btnGuardar) btnGuardar.textContent = 'Actualizar Usuario';
        if(userPasswordInput) { userPasswordInput.placeholder = 'Dejar vacío para no cambiar'; userPasswordInput.required = false; }
        userFormContainer.style.display = 'block'; userForm.classList.add('opacity-50');
        try {
            const dataAll = await fetchData('admin_obtener_usuarios.php'); // Usa fetchData global
            let userData = null;
            if(dataAll?.success && dataAll.usuarios) userData = dataAll.usuarios.find(u => u.idUsuario == idUsuario);
            if (userData) { populateForm(userForm, userData); } // Usa populateForm global
            else { showNotification(`Error: Datos no encontrados para ID ${idUsuario}.`, 'error'); userFormContainer.style.display = 'none'; } // Usa showNotification global
        } catch (error) { /* Error ya notificado por fetchData */ userFormContainer.style.display = 'none'; }
         finally { userForm.classList.remove('opacity-50'); }
    }

    // --- Función para Abrir Formulario de Creación --- (Sin cambios funcionales, solo usa helpers globales implícitamente si se añadieran)
    function abrirFormularioCreacionUsuario() {
        console.log(`[Admin Panel] Abriendo form para crear.`);
        if (!userForm || !userFormContainer) return;
        userForm.reset(); userIdInput.value = ''; userFormTitle.textContent = 'Crear Nuevo Usuario';
        const btnGuardar = userForm.querySelector('button[type="submit"]'); if(btnGuardar) btnGuardar.textContent = 'Guardar Usuario';
        if(userPasswordInput) { userPasswordInput.placeholder = 'Mínimo 6 caracteres'; userPasswordInput.required = true; }
        userFormContainer.style.display = 'block'; userForm.querySelector('[name="nombre"]')?.focus();
    }

    // --- Función para Eliminar Usuario --- (Usa fetchData y showNotification globales)
    async function eliminarUsuario(idUsuario) {
        console.log(`[Admin Panel] Solicitando eliminar ID: ${idUsuario}`);
        if (!confirm(`¿Seguro de eliminar al usuario ID ${idUsuario}?`)) return;
        try {
            const fd = new FormData(); fd.append('idUsuario', idUsuario);
            const data = await fetchData('admin_eliminar_usuario.php', { method: 'POST', body: fd }); // Usa fetchData global
            if (data?.success) { showNotification(data.message || 'Usuario eliminado.', 'success'); cargarListaUsuariosAdmin(); } // Usa showNotification global
        } catch (error) { /* Handled by fetchData */ }
    }

    // --- Listener Delegado para Acciones en Tabla Usuarios --- (Sin cambios, las acciones llamadas ahora usan helpers globales)
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

    // --- Listener para el Formulario de Crear/Editar Usuario --- (Usa fetchData, showNotification, setLoadingState globales)
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
            else if (!esEdicion && (formData.get('password')?.length ?? 0) < 6) { showNotification('Contraseña requerida (min 6 car.) al crear.', 'error'); return; } // Usa showNotification global
            setLoadingState(userForm, true, 'Guardando...'); // Usa setLoadingState global
            try {
                const data = await fetchData(scriptPHP, { method: 'POST', body: formData }); // Usa fetchData global
                if (data?.success) { showNotification(data.message || `Usuario ${esEdicion?'act.':'creado'}.`, 'success'); userForm.reset(); userFormContainer.style.display = 'none'; cargarListaUsuariosAdmin(); } // Usa showNotification global
            } catch (error) { /* Handled by fetchData */ }
            finally { setLoadingState(userForm, false, originalTextAdmin); } // Usa setLoadingState global
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