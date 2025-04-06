<?php
// --- mediagenda-backend/obtener_perfil.php ---

error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al usuario
ini_set('log_errors', 1);     // Registrar errores en el log de PHP

session_start();
header('Content-Type: application/json');
include 'conexion.php'; // Asegura que $conexion esté disponible

// 1. Verificar Sesión
if (!isset($_SESSION['idUsuario'])) {
    http_response_code(401); // No autorizado
    echo json_encode(["success" => false, "message" => "Sesión no iniciada."]);
    exit;
}

$idUsuario = $_SESSION['idUsuario'];
// Obtener rol de la sesión (asegúrate de que login.php lo guarda)
$rolUsuario = $_SESSION['rolUsuario'] ?? null;

if (!$rolUsuario) {
     http_response_code(500);
     error_log("Error crítico: Rol de usuario no encontrado en la sesión para idUsuario: " . $idUsuario);
     echo json_encode(["success" => false, "message" => "Error interno: Rol de usuario no definido."]);
     exit;
}

$perfilData = null;
$sql = "";
$stmt = null;

// 2. Preparar Consulta SQL según el Rol
try {
    if ($rolUsuario === 'paciente') {
        // Obtener datos de Usuario y Paciente
        $sql = "SELECT u.nombre, u.email, p.telefono, p.direccion
                FROM Usuario u
                LEFT JOIN Paciente p ON u.idUsuario = p.idUsuario
                WHERE u.idUsuario = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt === false) throw new Exception("Error al preparar consulta paciente: " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt, "i", $idUsuario);

    } elseif ($rolUsuario === 'medico') {
        // Obtener datos de Usuario y Medico
        $sql = "SELECT u.nombre, u.email, m.especialidad, m.horario
                FROM Usuario u
                LEFT JOIN Medico m ON u.idUsuario = m.idUsuario
                WHERE u.idUsuario = ?";
        $stmt = mysqli_prepare($conexion, $sql);
         if ($stmt === false) throw new Exception("Error al preparar consulta médico: " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmt, "i", $idUsuario);

    } else {
        // Para otros roles (admin, recepcionista), solo devolver datos básicos de Usuario
        // O puedes añadir joins a otras tablas si existen perfiles específicos para ellos
         $sql = "SELECT nombre, email, rol FROM Usuario WHERE idUsuario = ?";
         $stmt = mysqli_prepare($conexion, $sql);
         if ($stmt === false) throw new Exception("Error al preparar consulta genérica: " . mysqli_error($conexion));
         mysqli_stmt_bind_param($stmt, "i", $idUsuario);
    }

    // 3. Ejecutar Consulta y Obtener Datos
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $perfilData = mysqli_fetch_assoc($resultado);

    if ($perfilData) {
        // 4. Devolver Datos Exitosamente
        echo json_encode(["success" => true, "perfil" => $perfilData]);
    } else {
        // No se encontró perfil, lo cual es raro si hay sesión
        http_response_code(404);
        error_log("Perfil no encontrado en BD para idUsuario en sesión: " . $idUsuario);
        echo json_encode(["success" => false, "message" => "Perfil no encontrado."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en obtener_perfil.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error del servidor al obtener el perfil."]);
} finally {
    // 5. Limpiar
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);
}

exit; // Terminar script
?>