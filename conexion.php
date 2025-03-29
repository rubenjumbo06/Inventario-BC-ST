<?php
// Datos de conexión a la base de datos
$host = "localhost";         // Servidor de la base de datos
$usuario = "root";           // Usuario de MariaDB
$contraseña = "123456";            // Contraseña (vacía por defecto)
$nombreBaseDatos = "bd_inventarios"; // Nombre de la base de datos
$puerto = 3307;              // Puerto de MariaDB

// Crear la conexión usando mysqli con el puerto especificado
$conn = new mysqli($host, $usuario, $contraseña, $nombreBaseDatos, $puerto);

// Verificar si hay errores en la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Opcional: Establecer el conjunto de caracteres UTF-8
$conn->set_charset("utf8");
?>
