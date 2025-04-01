<?php
session_start(); 
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];

require_once("../../conexion.php"); 

// Consulta principal con JOINs correctos
$sql = "SELECT h.id_herramientas, h.nombre_herramientas, h.cantidad_herramientas, 
        h.id_empresa, e.nombre, 
        h.estado_herramientas, es.nombre_estado, 
        h.utilidad_herramientas, u.nombre_utilidad, 
        h.ubicacion_herramientas, h.fecha_ingreso 
        FROM tbl_herramientas h
        LEFT JOIN tbl_empresa e ON h.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON h.estado_herramientas = es.id_estado
        LEFT JOIN tbl_utilidad u ON h.utilidad_herramientas = u.id_utilidad";
$result = $conn->query($sql);

// Consultas para filtros desplegables
$empresas_sql = "SELECT DISTINCT e.id_empresa, e.nombre 
                 FROM tbl_empresa e 
                 INNER JOIN tbl_herramientas h ON e.id_empresa = h.id_empresa 
                 ORDER BY e.nombre";
$empresas_result = $conn->query($empresas_sql);

$estados_sql = "SELECT DISTINCT es.id_estado, es.nombre_estado 
                FROM tbl_estados es 
                INNER JOIN tbl_herramientas h ON es.id_estado = h.estado_herramientas 
                ORDER BY es.nombre_estado";
$estados_result = $conn->query($estados_sql);

$utilidades_sql = "SELECT DISTINCT u.id_utilidad, u.nombre_utilidad 
                   FROM tbl_utilidad u 
                   INNER JOIN tbl_herramientas h ON u.id_utilidad = h.utilidad_herramientas 
                   ORDER BY u.nombre_utilidad";
$utilidades_result = $conn->query($utilidades_sql);

$ubicaciones_sql = "SELECT DISTINCT ubicacion_herramientas 
                    FROM tbl_herramientas 
                    ORDER BY ubicacion_herramientas";
$ubicaciones_result = $conn->query($ubicaciones_sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Herramientas</title>
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
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
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
<?php include 'sidebarus.php'; ?>

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
            <h1 class="title text-shadow">Inventario de Herramientas</h1>    
        </strong>
        <div class="button-container">
            <form id="excelForm" action="../../EXCEL/generate_herra_xls.php" method="post">
                <input type="hidden" name="empresa" id="excelEmpresaFilter" value="">
                <input type="hidden" name="estado" id="excelEstadoFilter" value="">
                <input type="hidden" name="utilidad" id="excelUtilidadFilter" value="">
                <input type="hidden" name="ubicacion" id="excelUbicacionFilter" value="">
                <input type="hidden" name="nombre_search" id="excelNombreSearchFilter" value="">
                <button type="submit" class="excelBtn">Descargar Excel</button>
            </form>
            <form id="pdfForm" action="../../PDF/generate_herra_pdf.php" method="post">
                <input type="hidden" name="empresa" id="empresaFilter">
                <input type="hidden" name="estado" id="estadoFilter">
                <input type="hidden" name="utilidad" id="utilidadFilter">
                <input type="hidden" name="ubicacion" id="ubicacionFilter">
                <input type="hidden" name="nombre_search" id="nombreSearchFilter">
                <button type="submit" class="pdfBtn">Descargar PDF</button>
            </form>
        </div> 
        <div class="search-container">
            <input type="text" id="searchNombre" placeholder="Buscar por nombre de herramienta...">
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
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
                    <th>Utilidad
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="utilidad">
                                <option data-value="">Todas</option>
                                <?php while ($utilidad = $utilidades_result->fetch_assoc()): ?>
                                    <option data-value="<?php echo htmlspecialchars($utilidad['nombre_utilidad']); ?>">
                                        <?php echo htmlspecialchars($utilidad['nombre_utilidad']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </th>
                    <th>Ubicación
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="ubicacion">
                                <option data-value="">Todas</option>
                                <?php while ($ubicacion = $ubicaciones_result->fetch_assoc()): ?>
                                    <option data-value="<?php echo htmlspecialchars($ubicacion['ubicacion_herramientas']); ?>">
                                        <?php echo htmlspecialchars($ubicacion['ubicacion_herramientas']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </th>
                    <th>Fecha Ingreso</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_herramientas']; ?></td>
                    <td><?php echo $row['nombre_herramientas']; ?></td>
                    <td><?php echo $row['cantidad_herramientas']; ?></td>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['nombre_estado']; ?></td>
                    <td><?php echo $row['nombre_utilidad']; ?></td>
                    <td><?php echo $row['ubicacion_herramientas']; ?></td>
                    <td><?php echo $row['fecha_ingreso']; ?></td>
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

        // Funcionalidad de búsqueda y filtrado
        const searchNombre = document.getElementById('searchNombre');
        const table = document.querySelector('table');
        const rows = table.getElementsByTagName('tr');
        const pdfForm = document.getElementById('pdfForm');
        const excelForm = document.getElementById('excelForm');

        let filters = {
            empresa: '',
            estado: '',
            utilidad: '',
            ubicacion: ''
        };

        function applyFilters() {
            const nombreTerm = searchNombre.value.toLowerCase();

            // Actualizar los campos ocultos para ambos formularios
            document.getElementById('nombreSearchFilter').value = nombreTerm;
            document.getElementById('excelNombreSearchFilter').value = nombreTerm;
            document.getElementById('empresaFilter').value = filters.empresa;
            document.getElementById('excelEmpresaFilter').value = filters.empresa;
            document.getElementById('estadoFilter').value = filters.estado;
            document.getElementById('excelEstadoFilter').value = filters.estado;
            document.getElementById('utilidadFilter').value = filters.utilidad;
            document.getElementById('excelUtilidadFilter').value = filters.utilidad;
            document.getElementById('ubicacionFilter').value = filters.ubicacion;
            document.getElementById('excelUbicacionFilter').value = filters.ubicacion;

            for (let i = 1; i < rows.length; i++) {
                const nombre = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const empresa = rows[i].getElementsByTagName('td')[3].textContent;
                const estado = rows[i].getElementsByTagName('td')[4].textContent;
                const utilidad = rows[i].getElementsByTagName('td')[5].textContent;
                const ubicacion = rows[i].getElementsByTagName('td')[6].textContent;

                const matchesNombre = !nombreTerm || nombre.includes(nombreTerm);
                const matchesEmpresa = !filters.empresa || empresa === filters.empresa;
                const matchesEstado = !filters.estado || estado === filters.estado;
                const matchesUtilidad = !filters.utilidad || utilidad === filters.utilidad;
                const matchesUbicacion = !filters.ubicacion || ubicacion === filters.ubicacion;

                if (matchesNombre && matchesEmpresa && matchesEstado && matchesUtilidad && matchesUbicacion) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        searchNombre.addEventListener('keyup', applyFilters);

        // Manejo del filtro desplegable
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

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.filter-container')) {
                document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
        // Actualizar los filtros al enviar los formularios
        pdfForm.addEventListener('submit', function(e) {
            applyFilters(); // Asegurarse de que los campos ocultos estén actualizados antes de enviar
        });

        excelForm.addEventListener('submit', function(e) {
            applyFilters(); // Asegurarse de que los campos ocultos estén actualizados antes de enviar
        });
    });
</script>
</body>
</html>