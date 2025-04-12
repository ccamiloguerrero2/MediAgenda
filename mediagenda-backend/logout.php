<?php
// --- mediagenda-backend/logout.php ---
session_start(); // Reanudar la sesión existente para poder destruirla

// Destruir todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
// Nota: ¡Esto destruirá la sesión, y no la información de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

// Redirigir al usuario a la página principal.
// La ruta '../index.php' es correcta según tu estructura de carpetas.
header('Location: ../index.php?logout=success'); // Ajusta la ruta '../' si es necesario
exit(); // Asegura que el script se detenga después de la redirección
?>  