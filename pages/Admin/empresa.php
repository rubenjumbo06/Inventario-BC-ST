<?php
session_start(); 
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];

require_once("../../conexion.php"); 

$sql = "SELECT id_empresa, nombre, ruc, servicio_empresa FROM tbl_empresa";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Empresa</title>
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
            background-color: rgb(3, 24, 46);
        }
        .excelBtn {
            background-color: #28a745;
            color: white !important;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .excelBtn:hover {
            background-color: #185732;
        }
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
            background-color: rgb(167, 35, 31);
        }
        .button-container {
            display: flex; 
            justify-content: center; 
            gap: 10px;
            margin-top: 20px;
        }
        .search-container {
            margin: 20px 0;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .search-container input[type="text"] {
            padding: 8px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body class="bg-[var(--beige)]">
<?php include '../header.php'; ?>
<?php include 'sidebarad.php'; ?>
<div class="main-content">
    <div class="flex justify-between items-center mt-4 px-4">
        <p class="text-white text-sm sm:text-lg text-shadow">
            <strong>User:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?> 
            <span id="user-role"><?php echo !empty($role) ? "($role)" : ''; ?></span>
        </p>
        <p id="fechaHora" class="text-white text-sm sm:text-lg text-shadow">
            <strong>Fecha/Hora Ingreso:</strong> Cargando...
        </p>
    </div>

    <main class="container">
        <strong>
            <h1 class="title text-shadow">Tabla de Empresa</h1>    
        </strong>
        <div class="button-container">
            <a href="../../Uses/agregaremp.php">
                <button id="addBtn">Agregar Nuevo</button>
            </a>
            <form id="excelForm" action="../../EXCEL/generate_emp_xls.php" method="post">
                <input type="hidden" name="filter_nombre" id="excel_filter_nombre" value="">
                <input type="hidden" name="filter_ruc" id="excel_filter_ruc" value="">
                <button type="submit" class="excelBtn">Descargar Excel</button>
            </form>
            <form id="pdfForm" action="../../PDF/generate_emp_pdf.php" method="post">
                <input type="hidden" name="filter_nombre" id="filter_nombre" value="">
                <input type="hidden" name="filter_ruc" id="filter_ruc" value="">
                <button type="submit" class="pdfBtn">Descargar PDF</button>
            </form>
        </div>   
        <div class="search-container">
            <input type="text" id="searchNombre" placeholder="Buscar por nombre de la empresa...">
            <input type="text" id="searchRuc" placeholder="Buscar por RUC...">
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>RUC</th>
                    <th>Servicio Empresa</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_empresa']; ?></td>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['ruc']; ?></td>
                    <td><?php echo $row['servicio_empresa']; ?></td>
                    <td>
                        <a href="../../Uses/editaremp.php?id_empresa=<?php echo $row['id_empresa']; ?>">
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

        // Funcionalidad de búsqueda
        const searchNombre = document.getElementById('searchNombre');
        const searchRuc = document.getElementById('searchRuc');
        const table = document.querySelector('table');
        const rows = table.getElementsByTagName('tr');
        const pdfForm = document.getElementById('pdfForm');
        const excelForm = document.getElementById('excelForm');

        function applyFilters() {
            const nombreTerm = searchNombre.value.toLowerCase();
            const rucTerm = searchRuc.value.toLowerCase();

            // Actualizar los campos ocultos para ambos formularios
            document.getElementById('filter_nombre').value = nombreTerm;
            document.getElementById('filter_ruc').value = rucTerm;
            document.getElementById('excel_filter_nombre').value = nombreTerm;
            document.getElementById('excel_filter_ruc').value = rucTerm;

            for (let i = 1; i < rows.length; i++) {
                const nombreEmpresa = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const rucEmpresa = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();

                const matchesNombre = !nombreTerm || nombreEmpresa.includes(nombreTerm);
                const matchesRuc = !rucTerm || rucEmpresa.includes(rucTerm);

                if (matchesNombre && matchesRuc) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        searchNombre.addEventListener('keyup', applyFilters);
        searchRuc.addEventListener('keyup', applyFilters);

        // Actualizar los filtros al enviar el formulario
        pdfForm.addEventListener('submit', function() {
            applyFilters(); // Asegurarse de que los campos ocultos estén actualizados antes de enviar
        });
        excelForm.addEventListener('submit', function() {
            applyFilters();
        });
    });
</script>
</body>
</html>