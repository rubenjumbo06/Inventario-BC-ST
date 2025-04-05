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
        LEFT JOIN tbl_estados es ON a.estado_activos = es.id_estado
        WHERE a.id_status = 1";
$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta principal: " . $conn->error);
}

$estados_sql = "SELECT DISTINCT nombre_estado 
                FROM tbl_estados es
                INNER JOIN tbl_activos a ON es.id_estado = a.estado_activos
                WHERE a.id_status = 1 
                ORDER BY nombre_estado";
$estados_result = $conn->query($estados_sql);

$empresas_sql = "SELECT DISTINCT nombre 
                 FROM tbl_empresa e
                 INNER JOIN tbl_activos a ON e.id_empresa = a.id_empresa
                 WHERE a.id_status = 1 
                 ORDER BY nombre";
$empresas_result = $conn->query($empresas_sql);

$ubicaciones_sql = "SELECT DISTINCT ubicacion_activos 
                    FROM tbl_activos 
                    WHERE id_status = 1 
                    ORDER BY ubicacion_activos";
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
            background-color: rgb(3, 24, 46);
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
        }
        .search-container input[type="text"] {
            padding: 8px;
            width: 300px;
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
        .icon-btn {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s ease;
        }
        .edit-icon-btn {
            color: rgb(243, 126, 2);
        }
        .edit-icon-btn:hover {
            color: rgb(163, 87, 5);
            transform: scale(1.1);
        }
        .delete-icon-btn {
            color: #dc3545;
        }
        .delete-icon-btn:hover {
            color: #b02a37;
            transform: scale(1.1);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .modal-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .confirmBtn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .confirmBtn:hover {
            background-color: rgb(159, 38, 50);
        }
        .cancelBtn {
            background-color: rgb(30, 172, 59);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .cancelBtn:hover {
            background-color: rgb(23, 99, 23);
        }
    </style>
</head>
<body class="bg-[var(--beige)]">
<?php include '../header.php'; ?>
<?php include 'sidebarad.php'; ?>
<div class="main-content">
<?php include '../../Uses/msg.php'; ?>
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
                    <td><?php echo htmlspecialchars($row['nombre_activos']); ?></td>
                    <td><?php echo $row['cantidad_activos']; ?></td>
                    <td><?php echo htmlspecialchars($row['estado']); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td>
                    <td><?php echo htmlspecialchars($row['IP']); ?></td>
                    <td><?php echo htmlspecialchars($row['MAC']); ?></td>
                    <td><?php echo htmlspecialchars($row['SN']); ?></td>
                    <td><?php echo htmlspecialchars($row['ubicacion_activos']); ?></td>
                    <td><?php echo $row['fecha_ingreso']; ?></td>
                    <td>
                        <a href="../../Uses/editaract.php?id_activos=<?php echo $row['id_activos']; ?>">
                            <button class="edit-icon-btn icon-btn" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                </svg>
                            </button>
                        </a>
                        <button class="delete-icon-btn icon-btn deleteBtn" 
                                data-id="<?php echo $row['id_activos']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($row['nombre_activos']); ?>" 
                                data-cantidad="<?php echo $row['cantidad_activos']; ?>" 
                                data-estado="<?php echo htmlspecialchars($row['estado']); ?>" 
                                data-empresa="<?php echo htmlspecialchars($row['nombre_empresa']); ?>" 
                                data-ip="<?php echo htmlspecialchars($row['IP']); ?>" 
                                data-mac="<?php echo htmlspecialchars($row['MAC']); ?>" 
                                data-sn="<?php echo htmlspecialchars($row['SN']); ?>" 
                                data-ubicacion="<?php echo htmlspecialchars($row['ubicacion_activos']); ?>" 
                                data-fecha="<?php echo $row['fecha_ingreso']; ?>"
                                title="Eliminar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M5.5 5.5A.5.5 0 0 1 6 5h4a.5.5 0 0 1 0 1H6a.5.5 0 0 1-.5-.5z"/>
                                <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                            </svg>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>      

        <!-- Modal de confirmación -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h2>Confirmar Eliminación</h2>
                <p>¿Estás seguro de que deseas eliminar este activo?</p>
                <div id="modalData"></div>
                <div class="modal-buttons">
                    <button id="confirmDelete" class="confirmBtn">Confirmar</button>
                    <button id="cancelDelete" class="cancelBtn">Cancelar</button>
                </div>
            </div>
        </div>
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

        // Funcionalidad del modal de eliminación
        const modal = document.getElementById('deleteModal');
        const modalData = document.getElementById('modalData');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');
        const notification = document.getElementById('notification');
        let currentId = null;

        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentId = this.dataset.id;
                const nombre = this.dataset.nombre;
                const cantidad = this.dataset.cantidad;
                const estado = this.dataset.estado;
                const empresa = this.dataset.empresa;
                const ip = this.dataset.ip;
                const mac = this.dataset.mac;
                const sn = this.dataset.sn;
                const ubicacion = this.dataset.ubicacion;
                const fecha = this.dataset.fecha;

                modalData.innerHTML = `
                    <p><strong>ID:</strong> ${currentId}</p>
                    <p><strong>Nombre:</strong> ${nombre}</p>
                    <p><strong>Cantidad:</strong> ${cantidad}</p>
                    <p><strong>Estado:</strong> ${estado}</p>
                    <p><strong>Empresa:</strong> ${empresa}</p>
                    <p><strong>IP:</strong> ${ip}</p>
                    <p><strong>MAC:</strong> ${mac}</p>
                    <p><strong>Serie:</strong> ${sn}</p>
                    <p><strong>Ubicación:</strong> ${ubicacion}</p>
                    <p><strong>Fecha Ingreso:</strong> ${fecha}</p>
                `;
                modal.style.display = 'block';
            });
        });

        cancelDelete.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        confirmDelete.addEventListener('click', function() {
            fetch('../../Uses/eliminaract.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_activos=${encodeURIComponent(currentId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const rows = document.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const idCell = row.querySelector('td:first-child');
                        if (idCell && idCell.textContent === currentId) {
                            row.remove();
                        }
                    });
                    modal.style.display = 'none';
                    showNotification('El activo ha sido eliminado correctamente');
                } else {
                    showNotification('Error al ocultar el activo: ' + (data.message || 'Error desconocido'), true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ocurrió un error al intentar ocultar el activo.', true);
            });
        });

        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });

        // Función para mostrar notificaciones dinámicas
        function showNotification(message, isError = false) {
            const notification = document.getElementById('notification');
            const notificationText = notification.querySelector('span:first-child');
            notificationText.textContent = message;
            notification.classList.remove('hidden');
            if (isError) {
                notification.classList.add('error');
            } else {
                notification.classList.remove('error');
            }
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 5000);
        }
    });
</script>
</body>
</html>