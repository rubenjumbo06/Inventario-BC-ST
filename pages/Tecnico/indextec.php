<?php
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'tecnico') {
    header('Location: login.php');
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
        /* Estilos para el modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            color: black;
            margin: 15% auto;
            padding: 30px;
            border: 3px solid black;
            width: 80%;
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            position: relative;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .modal-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .modal-button.ingresar {
            background-color: rgba(58, 165, 37, 0.77);
            color: white;
        }

        .modal-button.ingresar:hover {
            background-color: rgba(26, 67, 18, 0.77);
        }

        .modal-button.cancelar {
            background-color:rgb(202, 45, 34);
            color: white;
        }

        .modal-button.cancelar:hover {
            background-color:rgb(81, 18, 18);
        }

        /* Estilos para los botones del panel */
        .panel-button {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .panel-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .panel-button img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            margin-bottom: 12px;
        }

        .panel-button span {
            color: var(--verde-claro);
            font-weight: 600;
            transition: color 0.2s;
        }

        .panel-button:hover span {
            color: var(--verde-oscuro);
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
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Botón Consumibles -->
                <div class="panel-button" onclick="openModal('Consumibles', 'Información de los Consumibles existentes en almacén.', 'consumibles.php')">
                    <img src="../../assets/img/consumibles.png" alt="Consumibles">
                    <span>Consumibles</span>
                </div>

                <!-- Botón Entradas -->
                <div class="panel-button" onclick="openModal('Entradas', 'Información de las Entradas existentes.', 'entradas.php')">
                    <img src="../../assets/img/entrar.png" alt="Entradas">
                    <span>Entradas</span>
                </div>

                <!-- Botón Salidas -->
                <div class="panel-button" onclick="openModal('Salidas', 'Información de las Salidas existentes.', 'salidas.php')">
                    <img src="../../assets/img/salir.png" alt="Salidas">
                    <span>Salidas</span>
                </div>

                <!-- Botón Registro de Salidas -->
                <div class="panel-button" onclick="openModal('Registro de salidas', 'Información de Herramientas, Consumibles y Activos que salieron en una instalación.', 'reg_salidas.php')">
                    <img src="../../assets/img/salidas.png" alt="Registro de salidas">
                    <span>Registro de salidas</span>
                </div>

                <!-- Botón Registro de Entradas -->
                <div class="panel-button" onclick="openModal('Registro de Entradas', 'Información de las Herramientas, Consumibles y Activos que regresaron en una instalación.', 'reg_entradas.php')">
                    <img src="../../assets/img/entradas.png" alt="Registro de Entradas">
                    <span>Registro de Entradas</span>
                </div>

                <!-- Botón Perfil -->
                <div class="panel-button" onclick="openModal('Perfil de Usuario', 'Información Personal del Usuario.', 'perfiltec.php')">
                    <img src="../../assets/img/perfil.png" alt="Perfil de Usuario">
                    <span>Perfil de Usuario</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div id="infoModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" class="text-2xl font-bold mb-4"></h2>
            <p id="modalDescription" class="mb-6"></p>
            <div class="modal-buttons">
                <button id="modalIngresarBtn" class="modal-button ingresar">Ingresar</button>
                <button onclick="closeModal()" class="modal-button cancelar">Cancelar</button>
            </div>
        </div>
    </div>
    <script>
        // Variables para el modal
        let currentRedirectUrl = '';
        
        // Función para abrir el modal
        function openModal(title, description, redirectUrl) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalDescription').textContent = description;
            currentRedirectUrl = redirectUrl;
            document.getElementById('infoModal').style.display = 'block';
        }
        
        // Función para cerrar el modal
        function closeModal() {
            document.getElementById('infoModal').style.display = 'none';
            currentRedirectUrl = '';
        }
        
        // Configurar el botón de ingresar
        document.getElementById('modalIngresarBtn').addEventListener('click', function() {
            if (currentRedirectUrl) {
                window.location.href = currentRedirectUrl;
            }
        });
        
        // Cerrar modal al hacer clic fuera del contenido
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('infoModal');
            if (event.target === modal) {
                closeModal();
            }
        });

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