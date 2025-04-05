<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'tecnico') {
    header('Location: /Inventario/login.php');
    exit;
}
// Recuperar datos de sesión para evitar el error
$user = $_SESSION['username'] ?? 'Usuario Desconocido'; 
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Técnico</title>
    <style>
        .card {
            position: relative;
            width: 350px;
            aspect-ratio: 16/9;
            background-color: rgb(255, 255, 255);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            perspective: 1000px;
            box-shadow: 0 0 0 5px #ffffff80;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }

        .card img {
            width: 64px;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(255, 255, 255, 0.9);
        }

        .card__content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 20px;
            box-sizing: border-box;
            background-color: #f2f2f2;
            transform: rotateX(-90deg);
            transform-origin: bottom;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card:hover .card__content {
            transform: rotateX(0deg);
        }

        .card__title {
            margin: 0;
            font-size: 20px;
            color: #333;
            font-weight: 700;
        }

        .card:hover img {
            scale: 0;
        }

        .card__description {
            margin: 10px 0 10px;
            font-size: 12px;
            color: #777;
            line-height: 1.4;
        }

        .card__button {
            padding: 10px 15px;
            border-radius: 8px;
            background: rgb(67, 198, 24);
            border: none;
            color: white;
            cursor: pointer;
            margin: 5px;
            transition: background-color 0.3s;
        }

        .card__button:hover {
            background: rgb(41, 125, 14);
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 20px;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        @media (min-width: 640px) {
            .grid-container {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .grid-container {
                grid-template-columns: repeat(3, 350px);
                gap: 45px;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body class="bg-[var(--beige)]">

    <?php include '../header.php'; ?>

    <div class="flex justify-between items-center mt-4 px-4">
        <p class="text-white text-sm sm:text-lg text-shadow">
            <strong>User:</strong> <?php echo htmlspecialchars($user); ?> 
            <span id="user-role"><?php echo !empty($role) ? "($role)" : ''; ?></span>
        </p>
        <p id="fechaHora" class="text-white text-sm sm:text-lg text-shadow">
            <strong>Fecha/Hora Ingreso:</strong> Cargando...
        </p>
    </div>

    <div class="px-4 sm:px-10 md:px-20 lg:px-20">
        <div class="mt-10">
            <div class="grid-container">
                <!-- Card Consumibles -->
                <div class="card">
                    <img src="../../assets/img/consumibles.png" alt="Consumibles">
                    <div class="card__content">
                        <p class="card__title">Consumibles</p>
                        <p class="card__description">Información de los Consumibles existentes en almacén.</p>
                        <button class="card__button" onclick="window.location.href='consumibles.php'">Ingresar</button>
                    </div>
                </div>

                <!-- Card Entradas -->
                <div class="card">
                    <img src="../../assets/img/entrar.png" alt="Entradas">
                    <div class="card__content">
                        <p class="card__title">Entradas</p>
                        <p class="card__description">Información de las Entradas existentes.</p>
                        <button class="card__button" onclick="window.location.href='entradas.php'">Ingresar</button>
                    </div>
                </div>

                <!-- Card Salidas -->
                <div class="card">
                    <img src="../../assets/img/salir.png" alt="Salidas">
                    <div class="card__content">
                        <p class="card__title">Salidas</p>
                        <p class="card__description">Información de las Salidas existentes.</p>
                        <button class="card__button" onclick="window.location.href='salidas.php'">Ingresar</button>
                    </div>
                </div>

                <!-- Card Registro de Salidas -->
                <div class="card">
                    <img src="../../assets/img/salidas.png" alt="Registro de salidas">
                    <div class="card__content">
                        <p class="card__title">Registro de Salidas</p>
                        <p class="card__description">Información de Herramientas, Consumibles y Activos que salieron en una instalación.</p>
                        <button class="card__button" onclick="window.location.href='reg_salidas.php'">Ingresar</button>
                    </div>
                </div>

                <!-- Card Registro de Entradas -->
                <div class="card">
                    <img src="../../assets/img/entradas.png" alt="Registro de Entradas">
                    <div class="card__content">
                        <p class="card__title">Registro de Entradas</p>
                        <p class="card__description">Información de las Herramientas, Consumibles y Activos que regresaron en una instalación.</p>
                        <button class="card__button" onclick="window.location.href='reg_entradas.php'">Ingresar</button>
                    </div>
                </div>

                <!-- Card Perfil -->
                <div class="card">
                    <img src="../../assets/img/perfil.png" alt="Perfil de Usuario">
                    <div class="card__content">
                        <p class="card__title">Perfil de Usuario</p>
                        <p class="card__description">Información Personal del Usuario.</p>
                        <button class="card__button" onclick="window.location.href='perfiltec.php'">Ingresar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para actualizar fecha y hora
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
        
        // Inicializar fecha/hora y actualizar cada segundo
        actualizarFechaHora();
        setInterval(actualizarFechaHora, 1000);
    </script>
</body>
</html>