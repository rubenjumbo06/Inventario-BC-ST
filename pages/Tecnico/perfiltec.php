<?php
session_start();
require_once("../../conexion.php");

if (!isset($_SESSION['id_user'])) {
    die("Usuario no autenticado."); 
}
$id_user = intval($_SESSION['id_user']);

$sql = "SELECT nombre, apellidos, role, correo, telefono FROM tbl_users WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("Usuario no encontrado.");
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../assets/CSS/agg.css">
</head>
<body class="bg-[var(--celeste)] min-h-screen flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full p-8 transition-all duration-300 animate-fade-in">
        <div class="flex flex-col md:flex-row">
            <div class="md:w-1/3 text-center mb-8 md:mb-0">
                <img src="../../assets/img/p3.jpeg" alt="Profile Picture" class="rounded-full w-48 h-48 mx-auto mb-2 border-4 border-[#22694c] dark:border-[#22694c] transition-transform duration-300 hover:scale-95">
                <h1 class="text-2xl font-bold text-[#22694c] dark:text-[#22694c] mb-2"><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']); ?></h1>
                <p class="text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($user['role']); ?></p>
                <a href="editprofiletec.php"><button class="mt-4 bg-[#22694c] text-white px-4 py-2 rounded-lg hover:bg-[#7ab351] transition-colors duration-300 cursor-pointer">Editar Perfil</button></a>
            </div>
            <div class="md:w-2/3 md:pl-8">
                <h2 class="text-xl font-semibold text-[#22694c] dark:text-[#22694c] mb-4">Permisos de Usuario</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-6 text-justify">
                    Como <?php echo htmlspecialchars($user['role']); ?> del sistema de gestión de inventarios, tienes acceso a 2 funciones clave del sistema, 
                    lo que te permite gestionar eficientemente los productos y operaciones establecidas. Entre estos se destaca el poder agregar y 
                    editar productos del inventario únicamente de la tabla Consumibles y reporte de la base de datos.
                </p>
                <h2 class="text-xl font-semibold text-[#22694c] dark:text-[#22694c] mb-4">Tablas Disponibles</h2>
                <div class="flex flex-wrap gap-2 mb-6">
                    <span class="bg-green-100 text-[#22694c] px-3 py-1 rounded-full text-sm cursor-pointer">Consumibles</span>
                </div>
                <h2 class="text-xl font-semibold text-[#22694c] dark:text-[#22694c] mb-4">Información</h2>
                <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                    <li class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-[#22694c]" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        <?php echo htmlspecialchars($user['correo']); ?>
                    </li>
                    <li class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-[#22694c]" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        <?php echo htmlspecialchars($user['telefono']); ?>
                    </li>
                    <li class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-[#22694c]" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        Talara, Perú
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <a href="indextec.php" class="fixed bottom-6 right-6 bg-[#22694c] text-white p-3 rounded-full shadow-lg hover:bg-[#7ab351] transition duration-300">
        <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
            <path stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
        </svg>
    </a>
</body>
</html>
<script>
    const skillTags = document.querySelectorAll('.bg-green-100');
    skillTags.forEach(tag => {
        tag.addEventListener('mouseover', () => {
            tag.classList.remove('bg-green-100', 'text-[#22694c]');
            tag.classList.add('bg-[#1b563d]', 'text-white');
        });
        tag.addEventListener('mouseout', () => {
            tag.classList.remove('bg-[#1b563d]', 'text-white');
            tag.classList.add('bg-green-100', 'text-[#22694c]');
        });
    });
</script>