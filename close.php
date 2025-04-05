<?php
session_start();

// Evitar caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_destroy(); // Cierra la sesión completamente
header("Location: login.php"); // Redirige al login
exit;
?>