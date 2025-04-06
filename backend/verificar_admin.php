<?php
// --- mediagenda-backend/verificar_admin.php ---
// Este script se incluye al inicio de los scripts de admin

// Asegurarse que la sesión está iniciada (puede que ya lo esté)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado y si su rol es 'admin'
if (!isset($_SESSION['idUsuario']) || !isset($_SESSION['rolUsuario']) || strtolower($_SESSION['rolUsuario']) !== 'admin') {
    // Si no es admin, enviar error y detener script
    http_response_code(403); // Forbidden
    // Limpiar cualquier salida anterior si es posible
    if (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json'); // Reasegurar header
    echo json_encode(["success" => false, "message" => "Acceso denegado. Se requiere rol de administrador."]);
    // Cerrar conexión si existe y detener todo
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    exit;
}

// Si llega aquí, el usuario es admin y puede continuar.
?>