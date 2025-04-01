<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];

require_once("../../conexion.php");

$sql = "SELECT a.id_activos, a.nombre_activos, a.cantidad_activos, es.nombre_estado AS estado, e.nombre AS nombre_empresa, 
        a.IP, a.MAC, a.SN, a.ubicacion_activos, a.fecha_ingreso 
        FROM tbl_activos a
        LEFT JOIN tbl_empresa e ON a.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON a.estado_activos = es.id_estado";
$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta principal: " . $conn->error);
}

$estados_sql = "SELECT DISTINCT nombre_estado FROM tbl_estados ORDER BY nombre_estado";
$estados_result = $conn->query($estados_sql);

$empresas_sql = "SELECT DISTINCT nombre FROM tbl_empresa ORDER BY nombre";
$empresas_result = $conn->query($empresas_sql);

$ubicaciones_sql = "SELECT DISTINCT ubicacion_activos FROM tbl_activos ORDER BY ubicacion_activos";
$ubicaciones_result = $conn->query($ubicaciones_sql);
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
        .excelBtn, .pdfBtn {
            color: white !important;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .excelBtn {
            background-color: #28a745;
        }

        .excelBtn:hover {
            background-color: #185732;
        }

        .pdfBtn {
            background-color: #dc3545;
        }

        .pdfBtn:hover {
            background-color:rgb(167, 35, 31);
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
        }

        .search-container input[type="text"] {
            padding: 8px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        /* Estilos para los filtros personalizados */
        .filter-container {
            position: relative;
            display: inline-block;
        }

        .filter-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 12px;
            padding: 0 5px;
            vertical-align: middle;
        }

        .filter-btn::after {
            content: '▼';
            margin-left: 5px;
            font-size: 10px;
        }

        .filter-dropdown {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 120px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            max-height: 200px;
            overflow-y: auto;
        }

        .filter-dropdown option {
            padding: 8px 12px;
            display: block;
            color: black;
            text-decoration: none;
        }

        .filter-dropdown option:hover {
            background-color: #f1f1f1;
        }

        .filter-container:hover .filter-dropdown,
        .filter-dropdown.active {
            display: block;
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
            <h1 class="title text-shadow">Inventario de Activos</h1>
        </strong>
        <div class="button-container">
            <a href="../../Uses/agregaract.php">
                <button id="addBtn">Agregar Nuevo</button>
            </a>
            <form id="excelForm" action="../../EXCEL/generate_act_xls.php" method="post">
                <input type="hidden" name="filter_estado" id="excel_filter_estado" value="">
                <input type="hidden" name="filter_empresa" id="excel_filter_empresa" value="">
                <input type="hidden" name="filter_ubicacion" id="excel_filter_ubicacion" value="">
                <input type="hidden" name="filter_search" id="excel_filter_search" value="">
                <button type="submit" class="excelBtn">Descargar Excel</button>
            </form>
            <form id="pdfForm" action="../../PDF/generate_act_pdf.php" method="post">
                <input type="hidden" name="filter_estado" id="filter_estado" value="">
                <input type="hidden" name="filter_empresa" id="filter_empresa" value="">
                <input type="hidden" name="filter_ubicacion" id="filter_ubicacion" value="">
                <input type="hidden" name="filter_search" id="filter_search" value="">
                <button type="submit" class="pdfBtn">Descargar PDF</button>
            </form>
        </div> 
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Buscar por nombre del activo...">
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Estado
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="estado">
                                <option data-value="">Todos</option>
                                <?php while ($estado = $estados_result->fetch_assoc()): ?>
                                    <option data-value="<?php echo htmlspecialchars($estado['nombre_estado']); ?>">
                                        <?php echo htmlspecialchars($estado['nombre_estado']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </th>
                    <th>Empresa
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="empresa">
                                <option data-value="">Todas</option>
                                <?php while ($empresa = $empresas_result->fetch_assoc()): ?>
                                    <option data-value="<?php echo htmlspecialchars($empresa['nombre']); ?>">
                                        <?php echo htmlspecialchars($empresa['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </th>
                    <th>IP</th>
                    <th>MAC</th>
                    <th>Serie</th>
                    <th>Ubicación
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="ubicacion">
                                <option data-value="">Todas</option>
                                <?php while ($ubicacion = $ubicaciones_result->fetch_assoc()): ?>
                                    <option data-value="<?php echo htmlspecialchars($ubicacion['ubicacion_activos']); ?>">
                                        <?php echo htmlspecialchars($ubicacion['ubicacion_activos']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </th>
                    <th>Ingreso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_activos']; ?></td>
                    <td><?php echo $row['nombre_activos']; ?></td>
                    <td><?php echo $row['cantidad_activos']; ?></td>
                    <td><?php echo $row['estado']; ?></td>
                    <td><?php echo $row['nombre_empresa']; ?></td>
                    <td><?php echo $row['IP']; ?></td>
                    <td><?php echo $row['MAC']; ?></td>
                    <td><?php echo $row['SN']; ?></td>
                    <td><?php echo $row['ubicacion_activos']; ?></td>
                    <td><?php echo $row['fecha_ingreso']; ?></td>
                    <td>
                        <a href="../../Uses/editaract.php?id_activos=<?php echo $row['id_activos']; ?>">
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

        const searchInput = document.getElementById('searchInput');
        const table = document.querySelector('table');
        const rows = table.getElementsByTagName('tr');

        let filters = {
            estado: '',
            empresa: '',
            ubicacion: '',
            search: ''
        };

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            filters.search = searchTerm;

            for (let i = 1; i < rows.length; i++) {
                const nombreActivo = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const estado = rows[i].getElementsByTagName('td')[3].textContent;
                const empresa = rows[i].getElementsByTagName('td')[4].textContent;
                const ubicacion = rows[i].getElementsByTagName('td')[8].textContent;

                const matchesSearch = nombreActivo.includes(searchTerm);
                const matchesEstado = !filters.estado || estado === filters.estado;
                const matchesEmpresa = !filters.empresa || empresa === filters.empresa;
                const matchesUbicacion = !filters.ubicacion || ubicacion === filters.ubicacion;

                if (matchesSearch && matchesEstado && matchesEmpresa && matchesUbicacion) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }

            // Actualizar los campos ocultos de ambos formularios
            document.getElementById('filter_estado').value = filters.estado;
            document.getElementById('filter_empresa').value = filters.empresa;
            document.getElementById('filter_ubicacion').value = filters.ubicacion;
            document.getElementById('filter_search').value = filters.search;
            
            document.getElementById('excel_filter_estado').value = filters.estado;
            document.getElementById('excel_filter_empresa').value = filters.empresa;
            document.getElementById('excel_filter_ubicacion').value = filters.ubicacion;
            document.getElementById('excel_filter_search').value = filters.search;
        }

        searchInput.addEventListener('keyup', applyFilters);

        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const dropdown = this.nextElementSibling;
                dropdown.classList.toggle('active');
            });
        });

        document.querySelectorAll('.filter-dropdown option').forEach(option => {
            option.addEventListener('click', function() {
                const filterType = this.parentElement.dataset.filter;
                const value = this.dataset.value;
                filters[filterType] = value === '' ? '' : value;
                applyFilters();
                this.parentElement.classList.remove('active');
                const btn = this.parentElement.previousElementSibling;
                btn.textContent = value === '' ? 'Filtrar' : value;
            });
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.filter-container')) {
                document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
    });
</script>
</body>
</html>