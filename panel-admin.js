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

    // --- Función para Renderizar la Tabla de Usuarios --- (Añadidas clases dark y UX mejorada)
    function renderUserTable(usuarios) {
        if (!userTableBody) return;
        userTableBody.innerHTML = '';
        if (!usuarios || usuarios.length === 0) {
            userTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No hay usuarios registrados.</td></tr>';
            return;
        }
        usuarios.forEach(user => {
            const row = userTableBody.insertRow();

            // Clases mejoradas para botones y celdas
            const commonButtonClasses = 'inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2';
            const editButtonClasses = `${commonButtonClasses} text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-600`;
            const deleteButtonClasses = `${commonButtonClasses} text-white bg-red-600 hover:bg-red-700 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-600 ml-2`;
            const cellClasses = 'px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300';
            const actionCellClasses = 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium'; // Flexbox no es ideal aquí, mantenemos simple por ahora

            row.innerHTML = `
                <td class="${cellClasses}">${user.idUsuario}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">${user.nombre}</td>
                <td class="${cellClasses}">${user.email}</td>
                <td class="${cellClasses}">${user.rol}</td>
                <td class="${actionCellClasses}">
                    <button data-action="editar-usuario" data-id="${user.idUsuario}" class="${editButtonClasses}" title="Editar">
                        <i class="bi bi-pencil-fill mr-1"></i> Editar
                    </button>
                    <button data-action="eliminar-usuario" data-id="${user.idUsuario}" class="${deleteButtonClasses}" title="Eliminar">
                        <i class="bi bi-trash-fill mr-1"></i> Eliminar
                    </button>
                </td>
            `;
        });
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

    // --- Función para Eliminar Usuario --- (Ahora usa SweetAlert para confirmar)
    async function eliminarUsuario(idUsuario) {
        console.log(`[Admin Panel] Solicitando eliminar ID: ${idUsuario}`);

        // Usar SweetAlert para confirmación
        Swal.fire({
            title: '¿Estás seguro?',
            text: `Esta acción eliminará al usuario con ID ${idUsuario}. ¡No podrás revertir esto!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Rojo para el botón de confirmar eliminación
            cancelButtonColor: '#6b7280', // Gris para cancelar
            confirmButtonText: 'Sí, ¡eliminar!',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                console.log(`[Admin Panel] Confirmado eliminar ID: ${idUsuario}`);
                // Proceder con la eliminación si se confirma
                try {
                    const fd = new FormData();
                    fd.append('idUsuario', idUsuario);
                    // Mostrar estado de carga (opcional, podrías implementar un spinner global)
                    showNotification('Eliminando usuario...', 'info');

                    const data = await fetchData('admin_eliminar_usuario.php', { method: 'POST', body: fd }); // Usa fetchData global

                    if (data?.success) {
                        Swal.fire(
                            '¡Eliminado!',
                            data.message || 'El usuario ha sido eliminado.',
                            'success'
                        );
                        cargarListaUsuariosAdmin(); // Recargar la lista
                    } else {
                        // Si fetchData no lanza error pero success es false
                        showNotification(data?.message || 'No se pudo eliminar el usuario.', 'error');
                    }
                } catch (error) {
                    // Error ya notificado por fetchData, pero podemos añadir un mensaje específico aquí si queremos
                    console.error('[Admin Panel] Error al eliminar usuario:', error);
                    // No es necesario mostrar otra notificación aquí, fetchData ya lo hizo.
                }
            } else {
                console.log(`[Admin Panel] Cancelada eliminación ID: ${idUsuario}`);
            }
        });
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