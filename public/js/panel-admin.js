// public/js/panel-admin.js
// ========================================================================
// == MediAgenda - Lógica del Panel de Administración =====================
// ========================================================================
// Maneja el CRUD (Crear, Leer, Actualizar, Eliminar) de usuarios
// desde el panel de administración. Depende de las funciones globales
// definidas en `scripts.js` (fetchData, showNotification, etc.).

// --- Modo Estricto ---
"use strict";

document.addEventListener('DOMContentLoaded', function () {
    console.log('%c[Panel Admin] DOM Cargado. Inicializando panel-admin.js...', 'color: purple; font-weight: bold;');

    // ========================================================================
    // == 1. SELECTORES DE ELEMENTOS DOM (Específicos del Panel Admin) =======
    // ========================================================================
    const userListLoading = document.getElementById('admin-user-list-loading');
    const userTable = document.getElementById('admin-user-table');
    const userTableBody = document.getElementById('admin-user-list-body');
    const userListEmpty = document.getElementById('admin-user-list-empty'); // Div para mensaje de tabla vacía

    const userFormContainer = document.getElementById('admin-user-form-container');
    const userForm = document.getElementById('admin-user-form');
    const userFormTitle = document.getElementById('admin-user-form-title');
    const userIdInput = document.getElementById('admin-user-id'); // Campo hidden ID
    const userPasswordInput = document.getElementById('admin-user-password'); // Campo password
    const passwordRequiredIndicator = document.getElementById('password-required-indicator'); // Asterisco de requerido
    const passwordHelpText = document.getElementById('password-help-text'); // Texto ayuda password

    const btnMostrarCrear = document.getElementById('btn-mostrar-crear-usuario');
    const btnCancelarEdicion = document.getElementById('btn-cancelar-edicion-usuario');
    const btnGuardarUsuario = document.getElementById('btn-guardar-usuario'); // Botón submit

    // Verificar si todos los elementos esenciales existen
    if (!userTableBody || !userForm || !btnMostrarCrear || !btnCancelarEdicion) {
        console.error("[Panel Admin] Error crítico: Faltan elementos esenciales del DOM para el panel de administración.");
        // Opcional: Mostrar un mensaje de error al usuario en la página
        const mainContainer = document.querySelector('.container');
        if (mainContainer) {
            mainContainer.innerHTML = '<p class="text-center text-red-500 font-bold p-8">Error al cargar la interfaz de administración. Contacte al soporte.</p>';
        }
        return; // Detener la ejecución si faltan elementos clave
    }

    // ========================================================================
    // == 2. FUNCIONES AUXILIARES (ESPECÍFICAS DEL PANEL ADMIN) =============
    // ========================================================================

    /**
     * Muestra u oculta el indicador de requerido y ajusta el texto de ayuda
     * para el campo de contraseña según si es modo creación o edición.
     * @param {boolean} isCreating - True si es el formulario de creación.
     */
    function ajustarInterfazPassword(isCreating) {
        if (userPasswordInput) {
            userPasswordInput.required = isCreating; // Requerido solo al crear
            userPasswordInput.placeholder = isCreating ? 'Mínimo 6 caracteres' : 'Dejar vacío para no cambiar';
        }
        if (passwordRequiredIndicator) {
            passwordRequiredIndicator.style.display = isCreating ? 'inline' : 'none'; // Mostrar/ocultar asterisco
        }
        if (passwordHelpText) {
            passwordHelpText.textContent = isCreating
                ? 'Obligatoria al crear (mínimo 6 caracteres).'
                : 'Dejar vacío para no cambiar la contraseña actual.';
        }
    }

    /**
     * Limpia y resetea el formulario de usuario, ocultándolo.
     */
    function resetYocultarFormularioUsuario() {
        if (userForm) {
             userForm.reset();
             // Limpiar mensajes de error explícitamente
             userForm.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
             userForm.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
        }
        if (userFormContainer) userFormContainer.style.display = 'none';
        userIdInput.value = ''; // Asegurarse que el ID oculto esté vacío
        console.log('[Panel Admin] Formulario de usuario reseteado y oculto.');
    }


    // ========================================================================
    // == 3. LÓGICA CRUD DE USUARIOS =========================================
    // ========================================================================

    /**
     * Renderiza las filas de la tabla de usuarios.
     * @param {Array<object>} usuarios - Array de objetos de usuario.
     */
    function renderUserTable(usuarios) {
        if (!userTableBody || !userTable || !userListEmpty) return;
        userTableBody.innerHTML = ''; // Limpiar tabla

        if (!usuarios || usuarios.length === 0) {
            userTable.classList.add('hidden'); // Ocultar tabla
            userListEmpty.classList.remove('hidden'); // Mostrar mensaje vacío
            console.log('[Panel Admin] No hay usuarios para mostrar.');
        } else {
            userTable.classList.remove('hidden'); // Mostrar tabla
            userListEmpty.classList.add('hidden'); // Ocultar mensaje vacío
            console.log(`[Panel Admin] Renderizando ${usuarios.length} usuarios.`);

            usuarios.forEach(user => {
                const row = userTableBody.insertRow();
                row.classList.add('hover:bg-gray-50', 'dark:hover:bg-gray-700/50'); // Efecto hover sutil

                // Clases comunes para botones de acción
                const commonButtonClasses = 'inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150 ease-in-out';
                const editButtonClasses = `${commonButtonClasses} text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-400`;
                const deleteButtonClasses = `${commonButtonClasses} text-white bg-red-600 hover:bg-red-700 focus:ring-red-500 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-400 ml-2`;
                // Clases para celdas
                const cellClasses = 'px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300';
                const nameCellClasses = 'px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100';
                const actionCellClasses = 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium';

                row.innerHTML = `
                    <td class="${cellClasses}">${user.idUsuario}</td>
                    <td class="${nameCellClasses}">${user.nombre || 'N/A'}</td>
                    <td class="${cellClasses}">${user.email || 'N/A'}</td>
                    <td class="${cellClasses}">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.rol === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : (user.rol === 'medico' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200')}">
                            ${user.rol || '?'}
                        </span>
                    </td>
                    <td class="${actionCellClasses}">
                        <button data-action="editar-usuario" data-id="${user.idUsuario}" class="${editButtonClasses}" title="Editar Usuario ${user.idUsuario}">
                            <i class="bi bi-pencil-fill mr-1"></i> Editar
                        </button>
                        <button data-action="eliminar-usuario" data-id="${user.idUsuario}" class="${deleteButtonClasses}" title="Eliminar Usuario ${user.idUsuario}">
                            <i class="bi bi-trash-fill mr-1"></i> Eliminar
                        </button>
                    </td>
                `;
            });
        }
    }

    /**
     * Carga la lista de usuarios desde el backend y la renderiza.
     */
    async function cargarListaUsuariosAdmin() {
        console.log("[Panel Admin] Cargando lista de usuarios...");
        if (userListLoading) userListLoading.style.display = 'block'; // Mostrar indicador de carga
        if (userTable) userTable.classList.add('hidden'); // Ocultar tabla mientras carga
        if (userListEmpty) userListEmpty.classList.add('hidden');

        try {
            // Llama al endpoint usando la función global fetchData de scripts.js
            const data = await fetchData('Admin/admin_obtener_usuarios.php'); // Ruta actualizada

            if (data?.success && Array.isArray(data.usuarios)) {
                renderUserTable(data.usuarios); // Renderiza la tabla si hay éxito
            } else {
                // Mostrar error si la respuesta no es exitosa o no tiene el formato esperado
                showNotification(data?.message || 'No se pudo cargar la lista de usuarios.', 'error');
                renderUserTable([]); // Renderizar tabla vacía (mostrará el mensaje 'No hay usuarios')
                if (userTable) userTable.classList.remove('hidden'); // Asegurarse que la tabla (con mensaje vacío) sea visible
            }
        } catch (error) {
            // Error de comunicación (ya notificado por fetchData)
            console.error("[Panel Admin] Error de fetch al cargar usuarios:", error);
            renderUserTable([]); // Renderizar tabla vacía
            if (userTable) userTable.classList.remove('hidden'); // Asegurarse que la tabla (con mensaje vacío) sea visible
        } finally {
            if (userListLoading) userListLoading.style.display = 'none'; // Ocultar indicador de carga
        }
    }

    /**
     * Prepara y muestra el formulario para editar un usuario existente.
     * @param {number|string} idUsuario - El ID del usuario a editar.
     */
    async function abrirFormularioEdicionUsuario(idUsuario) {
        console.log(`[Panel Admin] Abriendo formulario para editar usuario ID: ${idUsuario}`);
        if (!userForm || !userFormContainer || !userIdInput || !userFormTitle || !btnGuardarUsuario) return;

        resetYocultarFormularioUsuario(); // Limpia y oculta por si estaba abierto

        // Configurar formulario para modo EDICIÓN
        userIdInput.value = idUsuario;
        userFormTitle.textContent = `Editar Usuario #${idUsuario}`;
        btnGuardarUsuario.querySelector('.button-text').textContent = 'Actualizar Usuario'; // Cambiar texto del botón
        ajustarInterfazPassword(false); // Contraseña no es requerida en edición

        // Mostrar contenedor y un estado de 'cargando' visual
        userFormContainer.style.display = 'block';
        userForm.classList.add('opacity-50', 'pointer-events-none'); // Efecto visual de carga

        // Scroll suave hacia el formulario (opcional)
        userFormContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });


        try {
            // Obtener *todos* los usuarios y encontrar el específico
            // Alternativa: Crear un endpoint `admin_obtener_usuario.php?id=X` sería más eficiente
            const dataAll = await fetchData('Admin/admin_obtener_usuarios.php');
            let userData = null;

            if (dataAll?.success && Array.isArray(dataAll.usuarios)) {
                // Usar == para comparación flexible si idUsuario puede ser string
                userData = dataAll.usuarios.find(u => u.idUsuario == idUsuario);
            }

            if (userData) {
                populateForm(userForm, userData); // Rellenar formulario con datos existentes
                console.log(`[Panel Admin] Datos cargados para editar usuario ID: ${idUsuario}`, userData);
            } else {
                throw new Error(`Datos no encontrados para el usuario ID ${idUsuario}.`);
            }
        } catch (error) {
            console.error("[Panel Admin] Error al cargar datos para edición:", error);
            showNotification(error.message || 'Error al cargar los datos del usuario.', 'error');
            resetYocultarFormularioUsuario(); // Ocultar form si falla la carga
        } finally {
             // Quitar estado visual de carga
            userForm.classList.remove('opacity-50', 'pointer-events-none');
        }
    }

    /**
     * Prepara y muestra el formulario para crear un nuevo usuario.
     */
    function abrirFormularioCreacionUsuario() {
        console.log(`[Panel Admin] Abriendo formulario para crear nuevo usuario.`);
        if (!userForm || !userFormContainer || !userIdInput || !userFormTitle || !btnGuardarUsuario) return;

        resetYocultarFormularioUsuario(); // Limpia y oculta por si estaba abierto

        // Configurar formulario para modo CREACIÓN
        userIdInput.value = ''; // Asegurar que ID esté vacío
        userFormTitle.textContent = 'Crear Nuevo Usuario';
        btnGuardarUsuario.querySelector('.button-text').textContent = 'Guardar Usuario'; // Texto por defecto
        ajustarInterfazPassword(true); // Contraseña es requerida al crear

        // Mostrar formulario y enfocar primer campo
        userFormContainer.style.display = 'block';
        userForm.querySelector('[name="nombre"]')?.focus();

         // Scroll suave hacia el formulario (opcional)
         userFormContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Solicita confirmación y elimina un usuario.
     * @param {number|string} idUsuario - El ID del usuario a eliminar.
     */
    async function eliminarUsuario(idUsuario) {
        console.log(`[Panel Admin] Solicitando eliminar usuario ID: ${idUsuario}`);

        // Usar SweetAlert para confirmación (desde scripts.js)
        Swal.fire({
            title: '¿Estás seguro?',
            text: `Esta acción eliminará permanentemente al usuario con ID ${idUsuario}. ¡No podrás revertirlo!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Rojo
            cancelButtonColor: '#6b7280', // Gris
            confirmButtonText: 'Sí, ¡eliminar!',
            cancelButtonText: 'Cancelar',
            // Añadir clases dark mode si es necesario
            customClass: {
                 // popup: 'dark:bg-gray-800 dark:text-white',
                 // confirmButton: '...',
                 // cancelButton: '...',
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                console.log(`[Panel Admin] Confirmado eliminar ID: ${idUsuario}. Ejecutando...`);
                 // Mostrar indicador de carga (opcional pero recomendado)
                 Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

                try {
                    const formData = new FormData();
                    formData.append('idUsuario', idUsuario);

                    // Llama al endpoint usando la función global fetchData
                    const data = await fetchData('Admin/admin_eliminar_usuario.php', { method: 'POST', body: formData });

                    if (data?.success) {
                        Swal.fire( // Mostrar éxito
                            '¡Eliminado!',
                            data.message || 'El usuario ha sido eliminado.',
                            'success'
                        );
                        cargarListaUsuariosAdmin(); // Recargar la tabla para reflejar el cambio
                    } else {
                        // Si fetchData no lanzó error, pero success=false
                        Swal.fire( // Mostrar error específico del backend
                            'Error',
                             data?.message || 'No se pudo eliminar el usuario.',
                            'error'
                        );
                    }
                } catch (error) {
                    // Error de comunicación (ya notificado por fetchData)
                    console.error('[Panel Admin] Error en fetch al eliminar usuario:', error);
                    // El Swal de error se muestra desde fetchData, no es necesario duplicar
                    // Solo asegurarnos de cerrar el Swal de carga si sigue abierto
                    if (Swal.isLoading()) Swal.close();
                }
            } else {
                console.log(`[Panel Admin] Eliminación cancelada por el usuario ID: ${idUsuario}`);
            }
        });
    }


    // ========================================================================
    // == 4. MANEJADORES DE EVENTOS ==========================================
    // ========================================================================

    // --- Listener para el botón "Crear Nuevo Usuario" ---
    btnMostrarCrear.addEventListener('click', abrirFormularioCreacionUsuario);

    // --- Listener para el botón "Cancelar" del formulario ---
    btnCancelarEdicion.addEventListener('click', resetYocultarFormularioUsuario);

    // --- Listener Delegado para Acciones en la Tabla (Editar/Eliminar) ---
    function attachAdminUserActionListeners() {
        if (!userTable) return;

        // Limpiar listener previo si existe
        const previousListener = userTable._handleAdminUserClick;
        if (previousListener) userTable.removeEventListener('click', previousListener);

        // Crear y añadir nuevo listener
        const handleAdminUserClick = (event) => {
            const button = event.target.closest('button[data-action]');
            if (!button) return; // Ignorar clics fuera de los botones de acción

            const action = button.dataset.action;
            const idUsuario = button.dataset.id;

            if (!idUsuario) {
                console.error("[Panel Admin] Botón de acción sin data-id.");
                return;
            }

            console.log(`%c[Click Tabla Admin] Acción=${action}, ID=${idUsuario}`, 'color: purple;');

            if (action === 'editar-usuario') {
                abrirFormularioEdicionUsuario(idUsuario);
            } else if (action === 'eliminar-usuario') {
                eliminarUsuario(idUsuario);
            }
        };

        userTable.addEventListener('click', handleAdminUserClick);
        userTable._handleAdminUserClick = handleAdminUserClick; // Guardar referencia para limpieza futura
        console.log('[Panel Admin] Listener delegado adjuntado a #admin-user-table');
    }

    // --- Listener para el Envío del Formulario Crear/Editar Usuario ---
    if (userForm) {
        userForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const idUser = userIdInput.value;
            const esEdicion = idUser && idUser !== '';
            const endpoint = esEdicion ? 'Admin/admin_actualizar_usuario.php' : 'Admin/admin_crear_usuario.php';
            const successMessage = esEdicion ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.';
            const submitButtonText = esEdicion ? 'Actualizar Usuario' : 'Guardar Usuario';

            console.log(`%c[Submit Admin Form] ${esEdicion ? 'EDITAR' : 'CREAR'} -> ${endpoint}`, 'color:orange; font-weight:bold;');

            const formData = new FormData(userForm);
            const passwordValue = formData.get('password');

             // --- Validación Específica del Formulario ---
             let formIsValid = true;
             userForm.querySelectorAll('.error-message').forEach(el => el.classList.add('hidden'));
             userForm.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));

             // Validar campos requeridos (nombre, email, rol son required en HTML)
              userForm.querySelectorAll('[required]').forEach(input => {
                 if (!input.value.trim()) {
                     formIsValid = false;
                     const errorElement = input.closest('div')?.querySelector('.error-message');
                     input.classList.add('border-red-500');
                     if(errorElement) { errorElement.textContent = 'Campo obligatorio.'; errorElement.classList.remove('hidden'); }
                 }
             });

            // Validar contraseña: requerida al crear, opcional pero min 6 chars si se ingresa en editar
            if (!esEdicion && (!passwordValue || passwordValue.length < 6)) {
                formIsValid = false;
                 const errorElement = userPasswordInput.closest('div')?.querySelector('.error-message');
                 userPasswordInput.classList.add('border-red-500');
                 if(errorElement) { errorElement.textContent = 'Contraseña obligatoria (mínimo 6 caracteres).'; errorElement.classList.remove('hidden'); }
            } else if (esEdicion && passwordValue && passwordValue.length < 6) {
                 formIsValid = false;
                 const errorElement = userPasswordInput.closest('div')?.querySelector('.error-message');
                 userPasswordInput.classList.add('border-red-500');
                 if(errorElement) { errorElement.textContent = 'La nueva contraseña debe tener al menos 6 caracteres.'; errorElement.classList.remove('hidden'); }
            }

            if (!formIsValid) {
                showNotification("Por favor, corrija los errores en el formulario.", "warning");
                return;
            }
            // --- Fin Validación ---


            // Si es edición y la contraseña está vacía, no la enviamos para no sobreescribir
            if (esEdicion && !passwordValue) {
                formData.delete('password');
                console.log('[Submit Admin Form] Contraseña vacía en edición, no se enviará.');
            }

            setLoadingState(userForm, true); // Usar helper global

            try {
                const data = await fetchData(endpoint, { method: 'POST', body: formData }); // Usar helper global

                if (data?.success) {
                    showNotification(data.message || successMessage, 'success'); // Usar helper global
                    resetYocultarFormularioUsuario(); // Limpiar y ocultar form
                    cargarListaUsuariosAdmin(); // Recargar tabla
                } else {
                     // Error específico del backend ya mostrado por fetchData
                     console.warn('[Submit Admin Form] Error devuelto por el backend:', data?.message);
                     // Opcional: podrías intentar resaltar campos específicos si el backend devuelve errores por campo
                }
            } catch (error) {
                // Error de comunicación ya mostrado por fetchData
                console.error('[Submit Admin Form] Error de fetch:', error);
            } finally {
                setLoadingState(userForm, false); // Usar helper global
                 // Restaurar texto del botón
                 btnGuardarUsuario.querySelector('.button-text').textContent = submitButtonText;
            }
        });
        console.log('[Panel Admin] Listener de submit añadido a #admin-user-form');
    }


    // ========================================================================
    // == 5. EJECUCIÓN INICIAL ===============================================
    // ========================================================================

    resetYocultarFormularioUsuario(); // Asegurarse que el form esté oculto al inicio
    cargarListaUsuariosAdmin(); // Cargar la tabla de usuarios al iniciar
    attachAdminUserActionListeners(); // Activar botones de Editar/Eliminar en la tabla

    console.log('%c[Panel Admin] Inicialización de panel-admin.js completada.', 'color: purple; font-weight: bold;');

}); // Fin de DOMContentLoaded