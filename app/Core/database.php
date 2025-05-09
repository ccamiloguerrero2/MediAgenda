<?php
/**
 * Establecimiento y Configuración de la Conexión a la Base de Datos
 *
 * Este script se encarga de conectar con la base de datos MySQL/MariaDB
 * utilizando las credenciales definidas. 
 *
 * Si la conexión falla, registra un error crítico y envía una respuesta
 * JSON de error 503 (Service Unavailable), deteniendo la ejecución de
 * cualquier script que lo incluya.
 *
 * Si la conexión es exitosa, la variable $conexion estará disponible
 * para ser utilizada por el script que incluyó este archivo.
 *
 * @package MediAgenda\App\Core
 */

// --- Modo Estricto ---
declare(strict_types=1);

// --- Configuración de la Base de Datos ---
// --- Variables de Conexión ---
$db_host = "localhost";       // Servidor de la base de datos (usualmente localhost)
$db_user = "root";            // Usuario de la base de datos
$db_pass = "";                // Contraseña del usuario (vacía por defecto en XAMPP)
$db_name = "mediagenda_db";   // Nombre de la base de datos
$db_port = 3306;              // Puerto MySQL (3306 es el predeterminado)
$db_charset = "utf8mb4";      // Charset recomendado para compatibilidad con emojis, etc.

// --- Establecer Conexión ---
// Se utiliza '@' para suprimir el warning de conexión nativo de PHP,
// ya que manejaremos el error nosotros mismos de forma controlada.
$conexion = @mysqli_connect(
    $db_host,
    $db_user,
    $db_pass,
    $db_name,
    (int)$db_port // Asegurarse que el puerto sea integer si se usa
);

// --- Verificar Conexión ---
if (!$conexion) {
    // Registrar el error real y detallado en los logs del servidor.
    $error_message = "Fallo CRÍTICO de conexión a BD: " . mysqli_connect_error() . " (Código: " . mysqli_connect_errno() . ")";
    error_log($error_message);

    // Enviar respuesta JSON de error genérico al cliente.
    // Usar 503 Service Unavailable es apropiado cuando la BD no está disponible.
    http_response_code(503);
    // Asegurarse de que el header sea JSON, por si acaso.
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        "success" => false,
        "message" => "Error interno del servidor: No se puede conectar a la base de datos. Por favor, intente más tarde."
    ]);

    // Detener la ejecución del script que incluyó este archivo.
    exit;
}

// --- Establecer Charset de la Conexión ---
// Es crucial para evitar problemas con caracteres especiales (acentos, ñ, etc.).
if (!mysqli_set_charset($conexion, $db_charset)) {
    // Loguear el error si falla el set_charset, aunque la conexión sigue activa.
    error_log("Error al establecer el charset de la conexión a {$db_charset}: " . mysqli_error($conexion));
} else {
    // Log opcional para confirmar conexión y charset exitosos.
    // error_log("Conexión a BD '{$db_name}' establecida correctamente con charset {$db_charset}.");
}

// --- Conexión Lista ---
// La variable $conexion está ahora lista para ser usada por el script
?> 