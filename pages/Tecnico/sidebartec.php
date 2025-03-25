<?php
if ($_SESSION['role'] !== 'tecnico') {
    header('Location: login.php'); // Redirige si no es admin
    exit;
}
    // Obtener el nombre de la página actual
    $current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Inventario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px; 
            overflow-y: auto;
            z-index: 1000;
        }
        :root {
            --celeste: rgb(14, 57, 60);
            --verde-oscuro: #0D4B56;
            --verde-claro: #22694c;
            --mostaza: yellow;
            --verdes:rgb(113, 140, 43);
            --verde: rgb(151, 187, 60);
            --ver: #0f3d0f;
        }

        .menu-toggle-button {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1200; 
        }
        .main-content {
            padding: 20px;
            width: calc(100% - 250px);
            box-sizing: border-box; 
        }
        .active-menu-item {
            background-color: var(--verdes);
            font-weight: bold;
        }
    </style>
</head>

<body class="bg-gray-100">

    <input type="checkbox" id="menu-toggle" class="hidden peer">
    <div class="menu-toggle-button md:hidden">
        <label for="menu-toggle" class="cursor-pointer p-2 bg-[var(--celeste)] rounded-lg shadow-lg flex items-center justify-center w-12 h-12">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-6 w-6 text-white">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </label>
    </div>

    <!-- Sidebar -->
    <div class="sidebar hidden peer-checked:flex md:flex flex-col">
        <div class="flex items-center justify-between h-16 bg-[var(--ver)] px-4">
            <span class="text-white font-bold uppercase">Menú</span>    
            <label for="menu-toggle" class="md:hidden cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-6 w-6 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </label>
        </div>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <nav class="flex-1 px-2 py-4 bg-[var(--verde)]">
                    <!-- Inicio -->
                    <a href="indextec.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group <?php echo ($current_page == 'index.php') ? 'active-menu-item' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Inicio
                    </a>            
					<!-- Consumibles -->
                    <a href="consumibles.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group <?php echo ($current_page == 'consumibles.php') ? 'active-menu-item' : ''; ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M4 9V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3m-16 0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9m-16 0h16"/>
						</svg>
						Consumibles
					</a>
                    <!-- Registrar Entrada -->
                    <a href="reg_entradas.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group <?php echo ($current_page == 'reg_entradas.php') ? 'active-menu-item' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z" />
                        </svg>
                        Registrar Entrada
                    </a>

                    <!-- Registrar Salida -->
                    <a href="reg_salidas.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group <?php echo ($current_page == 'reg_salidas.php') ? 'active-menu-item' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z" />
                        </svg>
                        Registrar Salida
                    </a>

                    <!-- Tabla Entradas -->
                    <a href="entradas.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group <?php echo ($current_page == 'entradas.php') ? 'active-menu-item' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 0 0 2 2h6M8 7V5a2 2 0 0 1 2-2h4.586a1 1 0 0 1 .707.293l4.414 4.414a1 1 0 0 1 .293.707V17a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2" />
                        </svg>
                        Entradas
                    </a>

                    <!-- Tabla Salidas -->
                    <a href="salidas.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group <?php echo ($current_page == 'salidas.php') ? 'active-menu-item' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                        </svg>
                        Salidas
                    </a>
					<!-- Perfil de Usuario -->
                    <a href="perfiltec.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 14a7 7 0 0 0-7 7m7-7a7 7 0 0 1 7 7m-7-7a5 5 0 1 0-5-5 5 5 0 0 0 5 5Z"/>
						</svg>
						Perfil de Usuario
					</a>
            </nav>
        </div>
    </div>

</body>
</html>