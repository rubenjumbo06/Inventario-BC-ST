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
            --verdes:rgb(149, 185, 59);
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
                <a href="indexus.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Inicio
                </a>            
                <!-- Herramientas -->
                <a href="herramientas.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m6.364-1.364-1.414 1.414M21 12h-2m1.364 6.364-1.414-1.414M12 21v-2m-6.364 1.364 1.414-1.414M3 12h2m1.364-6.364 1.414 1.414M16.95 7.05a7 7 0 1 1-9.9 9.9"/>
						</svg>
						Herramientas
					</a>
					<!-- Activos -->
                    <a href="activos.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h11M9 21V10m4 11V10m4-4H7m10 0h4m-6 0v4H9V6m4 0V2"/>
						</svg>
						Activos
					</a>
					<!-- Consumibles -->
                    <a href="consumibles.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="group-hover:hidden h-6 w-6 mr-2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M4 9V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3m-16 0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9m-16 0h16"/>
						</svg>
						Consumibles
					</a>
					<!-- Perfil de Usuario -->
                    <a href="perfilus.php" class="flex items-center px-6 py-4 text-gray-100 hover:bg-[var(--verdes)] group">
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