# MediAgenda

Sistema web para la gestión de citas médicas online. Diseñado para conectar pacientes, médicos y administradores, facilitando la programación y el seguimiento de consultas médicas.

**Nota:** Este proyecto está configurado para ejecución en un entorno de desarrollo local (localhost) y utiliza una estructura de carpetas específica con separación entre el código público y el de la aplicación.

## Características Principales

*   **Gestión de Usuarios:**
    *   Registro y Login diferenciado por roles (Paciente, Médico, Administrador).
    *   Gestión de perfiles de usuario (actualización de datos básicos y específicos del rol).
    *   Flujo seguro de reseteo de contraseña mediante token enviado por email (conceptual).
*   **Gestión de Citas:**
    *   **Pacientes:** Pueden agendar nuevas citas seleccionando médico, fecha y hora; ver sus citas programadas y cancelarlas.
    *   **Médicos:** Pueden ver sus citas agendadas, confirmar citas, cancelarlas, marcarlas como completadas y ver la lista de pacientes del día.
    *   **Médicos:** Pueden añadir notas de diagnóstico/tratamiento asociadas a una cita.
*   **Panel de Administración:**
    *   Gestión completa de usuarios (CRUD: Crear, Leer, Actualizar, Eliminar) por parte del administrador.
*   **Interfaz:**
    *   Diseño responsive utilizando Tailwind CSS.
    *   Modo oscuro persistente (localStorage).
    *   Notificaciones interactivas con SweetAlert2.

## Stack Tecnológico

*   **Backend:** PHP 8.x (con extensión MySQLi)
*   **Base de Datos:** MySQL / MariaDB
*   **Frontend:**
    *   HTML5
    *   CSS3 (Tailwind CSS v3)
    *   JavaScript (Vanilla JS, ES6+, Fetch API)
*   **UI Libs (JS):** SweetAlert2
*   **Entorno de Desarrollo:** XAMPP (o similar: LAMP, MAMP)
*   **Build Frontend (CSS):** Node.js + npm (para compilar Tailwind)

## Estructura del Proyecto

El proyecto sigue una estructura organizada para separar responsabilidades:

*   **`/public/`**: Directorio raíz público (DocumentRoot). Contiene los archivos accesibles directamente por el navegador:
    *   `index.php`, `registro.php`, `perfil-*.php`, `panel-admin-sistema.php`: Vistas principales.
    *   `*.html`: Páginas estáticas.
    *   `/js/`: Archivos JavaScript del cliente (`scripts.js`, `panel-admin.js`). **Estos son los archivos a editar.**
    *   `/dist/`: Archivos CSS compilados (`output.css`).
    *   `/img/`: Imágenes.
    *   `/css/`: Otros CSS (`bitnami.css`).
*   **`/app/`**: Núcleo de la aplicación (código PHP no público):
    *   `/Api/`: Endpoints de la API interna (scripts PHP llamados por Fetch). Organizados por funcionalidad (Admin, Auth, Citas, Perfil, General).
    *   `/Core/`: Clases/Funciones base (conexión BD, utilidades de sesión, middlewares de autenticación/autorización).
    *   `/Views/Layout/`: Componentes PHP reutilizables de la interfaz (header, footer, menu).
*   **`/resources/`**: Archivos fuente para el proceso de build (actualmente solo CSS):
    *   `/css/input.css`: Archivo fuente de Tailwind CSS.
*   **`/sql/`**: Archivos de esquema y datos de la base de datos.
*   **Archivos Raíz:** Configuración del proyecto (`package.json`, `tailwind.config.js`, etc.), `.gitignore`.

## Prerrequisitos

*   Un entorno de servidor web local como [XAMPP](https://www.apachefriends.org/), LAMP o MAMP que incluya:
    *   Apache (o Nginx)
    *   MySQL (o MariaDB)
    *   PHP (versión 8.0 o superior recomendada)
*   [Node.js](https://nodejs.org/) y npm (para compilar Tailwind CSS)

## Instalación y Configuración

1.  **Clonar/Descargar:** Obtén el código fuente del proyecto en tu directorio de trabajo (p.ej., `C:\xampp\htdocs\MediAgenda`).
2.  **Base de Datos:**
    *   Abre phpMyAdmin (o tu cliente MySQL preferido).
    *   Crea una nueva base de datos llamada `mediagenda_db`.
    *   Selecciona la base de datos `mediagenda_db` e importa el archivo `sql/mediagenda_db.sql`.
    *   Verifica las credenciales de conexión en `app/Core/database.php`. Por defecto, usa `root` sin contraseña. Ajusta si tu configuración MySQL es diferente.
3.  **Configuración del Servidor Web (Apache/XAMPP):**
    *   **IMPORTANTE:** Configura tu servidor web (Apache) para que el **`DocumentRoot`** apunte al directorio `/public` dentro de tu proyecto.
    *   **Ejemplo (Apache - httpd-vhosts.conf):**
        ```apache
        <VirtualHost *:80>
            DocumentRoot "C:/xampp/htdocs/MediAgenda/public"
            ServerName mediagenda.local # O el nombre que prefieras
            <Directory "C:/xampp/htdocs/MediAgenda/public">
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All # Necesario para .htaccess
                Require all granted
            </Directory>
            ErrorLog "logs/mediagenda-error.log"
            CustomLog "logs/mediagenda-access.log" common
        </VirtualHost>
        ```
    *   Asegúrate de descomentar la línea `Include conf/extra/httpd-vhosts.conf` en tu `httpd.conf` principal.
    *   Edita tu archivo `hosts` (ej. `C:\Windows\System32\drivers\etc\hosts`) para añadir `127.0.0.1 mediagenda.local`.
    *   **Reinicia Apache.**
4.  **Dependencias Node.js:**
    *   Abre una terminal o símbolo del sistema en la raíz del proyecto (`C:\xampp\htdocs\MediAgenda`).
    *   Ejecuta `npm install` para instalar las dependencias de desarrollo (Tailwind, etc.).
5.  **Compilar CSS:**
    *   Ejecuta `npm run build` para compilar `resources/css/input.css` y generar `public/dist/output.css`.

## Ejecución

1.  Asegúrate de que Apache y MySQL estén ejecutándose (desde el panel de control de XAMPP).
2.  Abre tu navegador web y ve a la URL que configuraste (ej. `http://mediagenda.local`). Deberías ver la página principal (`index.php`).

## Desarrollo (Compilación Tailwind en Tiempo Real)

*   Para compilar automáticamente los cambios en el CSS mientras desarrollas, ejecuta en la terminal (desde la raíz del proyecto):
    ```bash
    npm run dev
    ```
*   Edita tus archivos JavaScript directamente en `public/js/`.
*   Edita tu CSS fuente (si necesitas añadir CSS personalizado o configurar Tailwind) en `resources/css/input.css`.

## Próximos Pasos / Mejoras Potenciales

*   Implementar completamente la visualización del historial médico para pacientes.
*   Desarrollar el sistema de notificaciones en la aplicación (BD + Backend + Frontend).
*   Refactorizar el backend PHP hacia una estructura más orientada a objetos o usando un micro-framework.
*   Reforzar la seguridad (protección XSS consistente, añadir tokens CSRF).
*   Mejorar la validación de datos en el backend.
*   Añadir pruebas unitarias y de integración.
*   Configurar el envío real de correos electrónicos para el reseteo de contraseña.