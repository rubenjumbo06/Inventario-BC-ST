<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario = $_SESSION['username'] ?? 'Invitado';
$role = $_SESSION['role'] ?? '';

$paginasTablas = ['activos', 'estados', 'consumibles', 'herramientas', 'empresa', 'utilidad', 'tecnico', 'users', 'salidas', 'entradas', 'reg_entradas', 'reg_salidas'];

$archivoActual = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);

$isTablePage = in_array($archivoActual, $paginasTablas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Inicio</title>
    <script src="https://cdn.tailwindcss.com"></script> 
    <style>
        :root {
            --celeste: #00797E;
            --verde-oscuro: #0D4B56;
            --verde-claro: #22694c;
            --beige: #D8E6B5;
            --mostaza: yellow;
        }
        .text-shadow {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
        }
        .header {
            width: 100%;
            height: 64px;
            background-color: white;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            transition: all 0.3s ease-in-out;
            position: relative;
        }
        <?php if ($isTablePage): ?>
        .header {
            left: 250px;
            position: fixed;
            width: calc(100% - 250px);
            top: 0;
            z-index: 1050;
        }
        .main-content {
            margin-left: 230px;
        }
        <?php endif; ?>
    </style>
</head>
<body class="bg-gray-200 min-h-screen flex flex-col">
    <div class="main-content w-full">
        <header class="bg-[var(--verde-claro)] text-white p-4 sm:p-6 flex justify-between items-center <?php echo $isTablePage ? 'fixed top-0 left-[250px] right-0 z-50' : ''; ?>">
            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-shadow">
                Inventario <span class="text-[var(--mostaza)]">BARUC - STARNET</span>
            </h2>
            <a href="/Inventario/logout.php">
                <button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-800 transition cursor-pointer text-sm sm:text-base">
                    Log Out
                </button>
            </a>
        </header>
    </div>
</body>
</html>
