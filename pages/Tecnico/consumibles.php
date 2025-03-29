<?php
session_start(); 
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];

require_once("../../conexion.php"); 
$sql = "SELECT id_consumibles, nombre_consumibles, cantidad_consumibles, id_empresa, estado_consumibles, utilidad_consumibles, fecha_ingreso, id_user FROM tbl_consumibles";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Activos</title>
    <link rel="stylesheet" href="../../assets/CSS/tables.css">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            margin-top: 20px;
        }
        .header {
            top: 0;
            left: 250px;
            position: fixed;
            width: calc(100% - 250px);
            height: 64px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000; 
        }
        #addBtn {
            background-color: rgb(3, 70, 141);
            color: white !important;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        #addBtn:hover {
            background-color:rgb(3, 24, 46);
        }
        /* Estilos para el botón de Excel */
        .excelBtn {
            background-color: #28a745 ;
            color: white !important;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .excelBtn:hover {
            background-color: #185732; /* Verde más oscuro al pasar el mouse */
        }

        /* Estilos para el botón de PDF */
        .pdfBtn {
            background-color: #dc3545;
            color: white !important;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .pdfBtn:hover {
            background-color:rgb(167, 35, 31); /* Rojo más oscuro al pasar el mouse */
        }

        /* Contenedor de botones */
        .button-container {
            display: flex; /* Activa Flexbox */
            justify-content: center; /* Centra los botones horizontalmente */
            gap: 10px; /* Espacio entre los botones */
            margin-top: 20px; /* Margen superior */
        }
    </style>
</head>
<body class="bg-[var(--beige)]">
<?php include '../header.php'; ?>
<?php include 'sidebartec.php'; ?>
<div class="main-content">
<div class="flex justify-between items-center mt-4 px-4">
        <p class="text-white text-sm sm:text-lg text-shadow">
            <strong>User:</strong> <?php echo htmlspecialchars($usuario); ?> 
            <span id="user-role"><?php echo !empty($role) ? "($role)" : ''; ?></span>
        </p>
        <p id="fechaHora" class="text-white text-sm sm:text-lg text-shadow">
            <strong>Fecha/Hora Ingreso:</strong> Cargando...
        </p>
    </div>
    <main class="container">
        <strong>
        <h1 class="title text-shadow">Inventario de Consumibles</h1>    
        </strong>
        <div class="button-container">
            <!-- Botón Agregar -->
            <a href="../../Uses/agregarcon.php">
                <button id="addBtn">Agregar Nuevo</button>
            </a>

            <!-- Botón Excel -->
            <a href="../../EXCEL/generate_con_xls.php">
                <button class="excelBtn">Descargar Excel</button>
            </a>

            <!-- Botón PDF -->
            <form action="../../PDF/generate_con_pdf.php" method="post">
                <button type="submit" class="pdfBtn">Descargar PDF</button>
            </form>
        </div> 
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Empresa</th>
                    <th>Estado</th>
                    <th>Utilidad</th>
                    <th>Fecha Ingreso</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_consumibles']; ?></td>
                    <td><?php echo $row['nombre_consumibles']; ?></td>
                    <td><?php echo $row['cantidad_consumibles']; ?></td>
                    <td><?php echo $row['id_empresa']; ?></td>
                    <td><?php echo $row['estado_consumibles']; ?></td>
                    <td><?php echo $row['utilidad_consumibles']; ?></td>
                    <td><?php echo $row['fecha_ingreso']; ?></td>
                    <td><?php echo $row['id_user']; ?></td>
                    <td>
                        <a href="../../Uses/editarcon.php?id_consumibles=<?php echo $row['id_consumibles']; ?>">
                            <button class="editBtn">Editar</button>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
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
