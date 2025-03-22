<?php
// Datos de conexión a la base de datos
$host = "localhost";        // Host de la base de datos (generalmente localhost)
$usuario = "root";          // Nombre de usuario de MySQL (por defecto root)
$contraseña = "";           // Contraseña de MySQL (por defecto vacía)
$nombreBaseDatos = "bd_invent"; // Nombre de tu base de datos

// Crear la conexión usando mysqli
$conn = new mysqli($host, $usuario, $contraseña, $nombreBaseDatos);

// Verificar si hay errores en la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Opcional: Establecer el conjunto de caracteres UTF-8
$conn->set_charset("utf8");

