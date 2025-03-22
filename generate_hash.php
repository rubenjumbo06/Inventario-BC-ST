<?php
// Contraseña original
$password = 'miguel2024';

// Generar el hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Mostrar el hash
echo "Hash generado: " . $hash;
?>