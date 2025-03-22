<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Usuario</title>
    <style>
        .info-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 220px;
            max-height: 300px;
            background: #18919A;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 16px;
            display: none;
            z-index: 10;
            border: 3px solid black;
            overflow-y: auto;
            box-sizing: border-box; 
        }

        .button-container:hover .info-box {
            display: block;
        }

        .button-container {
            position: relative;
            display: inline-block;
        }
    </style>
</head>
<body class="bg-[var(--beige)]">

    <?php include '../header.php'; ?>

        <div class="flex justify-between items-center mt-4 px-4">
            <p class="text-white text-sm sm:text-lg text-shadow">
                <strong>User:</strong> <?php echo htmlspecialchars($usuario); ?> 
                <span id="user-role"><?php echo !empty($role) ? "($role)" : ''; ?></span>
            </p>
            <p id="fechaHora" class="text-white text-sm sm:text-lg text-shadow">
                <strong>Fecha/Hora Ingreso:</strong> Cargando...
            </p>
        </div>

    <div class="px-4 sm:px-10 md:px-20 lg:px-60">
        <div class="mt-20 lg:mt-24">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
                <div class="button-container bg-white p-6 rounded-lg shadow-md flex flex-col justify-center text-center">

                    <a href="herramientas.php" class="block w-full h-full text-center cursor-pointer flex flex-col items-center">
                        <img src="../../assets/img/herramientas.png" alt="Perfil Usuario" class="w-16 h-16 object-contain mb-4">
                        <span class="text-[var(--verde-claro)] font-semibold hover:text-[var(--verde-oscuro)] transition">
                            Herramientas
                        </span>

                        <div id="infoBox1" class="info-box">
                            <p>Información de las Herramientas existentes en almacen.</p>
                        </div>
                    </a>

                </div>

                <div class="button-container bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center">
                    <!-- Enlace envolviendo todo el contenido -->
                    <a href="activos.php" class="block w-full h-full text-center cursor-pointer flex flex-col items-center">
                        <img src="../../assets/img/activos.png" alt="Perfil Usuario" class="w-16 h-16 object-contain mb-4">
                        <span class="text-[var(--verde-claro)] font-semibold hover:text-[var(--verde-oscuro)] transition">
                            Activos
                        </span>

                        <!-- Info Box -->
                        <div id="infoBox1" class="info-box">
                            <p>Información de los Activos existentes en almacen.</p>
                        </div>
                    </a>
                </div>

                <div class="button-container bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center">
                    <!-- Enlace envolviendo todo el contenido -->
                    <a href="consumibles.php" class="block w-full h-full text-center cursor-pointer flex flex-col items-center">
                        <img src="../../assets/img/consumibles.png" alt="Perfil Usuario" class="w-16 h-16 object-contain mb-4">
                        <span class="text-[var(--verde-claro)] font-semibold hover:text-[var(--verde-oscuro)] transition">
                            Consumibles
                        </span>

                        <!-- Info Box -->
                        <div id="infoBox1" class="info-box">
                            <p>Información de los Consumibles existentes en almacen.</p>
                        </div>
                    </a>
                </div>

                <div class="button-container bg-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center text-center">
                    <!-- Enlace envolviendo todo el contenido -->
                    <a href="perfilus.php" class="block w-full h-full text-center cursor-pointer flex flex-col items-center">
                        <img src="../../assets/img/perfil.png" alt="Perfil Usuario" class="w-16 h-16 object-contain mb-4">
                        <span class="text-[var(--verde-claro)] font-semibold hover:text-[var(--verde-oscuro)] transition">
                            Perfil de Usuario
                        </span>

                        <!-- Info Box -->
                        <div id="infoBox1" class="info-box">
                            <p>Información Personal del Usuario.</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function actualizarFechaHora() {
                const ahora = new Date();
                const fechaHoraFormateada = ahora.toLocaleString('es-ES', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                const fechaHoraElemento = document.getElementById("fechaHora");
                if (fechaHoraElemento) {
                    fechaHoraElemento.textContent = `Fecha/Hora Ingreso: ${fechaHoraFormateada}`;
                }
            }
            actualizarFechaHora();
            setInterval(actualizarFechaHora, 1000);
        });
    </script>
</body>
</html>