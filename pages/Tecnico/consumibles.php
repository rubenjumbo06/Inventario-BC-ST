<?php
// Configuración segura de sesión
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];

require_once("../../conexion.php");

// Primero guardamos los resultados de las consultas de filtro en arrays
$empresas = [];
$empresas_sql = "SELECT DISTINCT e.id_empresa, e.nombre 
                 FROM tbl_empresa e 
                 INNER JOIN tbl_consumibles c ON e.id_empresa = c.id_empresa 
                 ORDER BY e.nombre";
$empresas_result = $conn->query($empresas_sql);
while ($empresa = $empresas_result->fetch_assoc()) {
    $empresas[] = $empresa;
}

$estados = [];
$estados_sql = "SELECT DISTINCT es.id_estado, es.nombre_estado 
                FROM tbl_estados es 
                INNER JOIN tbl_consumibles c ON es.id_estado = c.estado_consumibles 
                ORDER BY es.nombre_estado";
$estados_result = $conn->query($estados_sql);
while ($estado = $estados_result->fetch_assoc()) {
    $estados[] = $estado;
}

$utilidades = [];
$utilidades_sql = "SELECT DISTINCT u.id_utilidad, u.nombre_utilidad 
                   FROM tbl_utilidad u 
                   INNER JOIN tbl_consumibles c ON u.id_utilidad = c.utilidad_consumibles 
                   ORDER BY u.nombre_utilidad";
$utilidades_result = $conn->query($utilidades_sql);
while ($utilidad = $utilidades_result->fetch_assoc()) {
    $utilidades[] = $utilidad;
}

$usuarios = [];
$usuarios_sql = "SELECT DISTINCT us.id_user, us.nombre 
                 FROM tbl_users us 
                 INNER JOIN tbl_consumibles c ON us.id_user = c.id_user 
                 ORDER BY us.nombre";
$usuarios_result = $conn->query($usuarios_sql);
while ($usuario = $usuarios_result->fetch_assoc()) {
    $usuarios[] = $usuario;
}

// Consulta principal con JOINs correctos
$sql = "SELECT c.id_consumibles, c.nombre_consumibles, c.cantidad_consumibles, 
        e.nombre AS nombre_empresa, es.nombre_estado, u.nombre_utilidad, c.fecha_ingreso, us.nombre AS nombre_usuario
        FROM tbl_consumibles c
        LEFT JOIN tbl_empresa e ON c.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON c.estado_consumibles = es.id_estado
        LEFT JOIN tbl_utilidad u ON c.utilidad_consumibles = u.id_utilidad
        LEFT JOIN tbl_users us ON c.id_user = us.id_user";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Consumibles</title>
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
<?php include 'sidebartec.php'; ?>
<div class="main-content">
    <div class="flex justify-between items-center mt-4 px-4">
        <p class="text-white text-sm sm:text-lg text-shadow">
            <strong>User:</strong> <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?> 
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
            <a href="../../EXCEL/generate_con_xls.php">
                <button class="excelBtn">Descargar Excel</button>
            </a>
            <form action="../../PDF/generate_con_pdf.php" method="post">
                <button type="submit" class="pdfBtn">Descargar PDF</button>
            </form>
        </div>   
        <div class="search-container">
            <input type="text" id="searchNombre" placeholder="Buscar por nombre de consumible...">
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
                                <?php foreach ($empresas as $empresa): ?>
                                    <option data-value="<?php echo htmlspecialchars($empresa['nombre']); ?>">
                                        <?php echo htmlspecialchars($empresa['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </th>
                    <th>Estado
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="estado">
                                <option data-value="">Todos</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option data-value="<?php echo htmlspecialchars($estado['nombre_estado']); ?>">
                                        <?php echo htmlspecialchars($estado['nombre_estado']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </th>
                    <th>Utilidad
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="utilidad">
                                <option data-value="">Todas</option>
                                <?php foreach ($utilidades as $utilidad): ?>
                                    <option data-value="<?php echo htmlspecialchars($utilidad['nombre_utilidad']); ?>">
                                        <?php echo htmlspecialchars($utilidad['nombre_utilidad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </th>
                    <th>Fecha Ingreso</th>
                    <th>Usuario
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="usuario">
                                <option data-value="">Todos</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option data-value="<?php echo htmlspecialchars($usuario['nombre']); ?>">
                                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_consumibles']; ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_consumibles']); ?></td>
                    <td><?php echo $row['cantidad_consumibles']; ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_estado']); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_utilidad']); ?></td>
                    <td><?php echo $row['fecha_ingreso']; ?></td>
                    <td><?php echo htmlspecialchars($row['nombre_usuario']); ?></td>
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

        let filters = {
            empresa: '',
            estado: '',
            utilidad: '',
            usuario: ''
        };

        function applyFilters() {
            const nombreTerm = searchNombre.value.toLowerCase();

            for (let i = 1; i < rows.length; i++) {
                const nombre = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const empresa = rows[i].getElementsByTagName('td')[3].textContent;
                const estado = rows[i].getElementsByTagName('td')[4].textContent;
                const utilidad = rows[i].getElementsByTagName('td')[5].textContent;
                const usuario = rows[i].getElementsByTagName('td')[7].textContent;

                const matchesNombre = !nombreTerm || nombre.includes(nombreTerm);
                const matchesEmpresa = !filters.empresa || empresa === filters.empresa;
                const matchesEstado = !filters.estado || estado === filters.estado;
                const matchesUtilidad = !filters.utilidad || utilidad === filters.utilidad;
                const matchesUsuario = !filters.usuario || usuario === filters.usuario;

                if (matchesNombre && matchesEmpresa && matchesEstado && matchesUtilidad && matchesUsuario) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        searchNombre.addEventListener('keyup', applyFilters);

        // Manejo del filtro desplegable
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const dropdown = this.nextElementSibling;
                dropdown.classList.toggle('active');
            });
        });

        document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                if (e.target.tagName === 'OPTION') {
                    const filterType = this.dataset.filter;
                    const value = e.target.dataset.value;
                    filters[filterType] = value === '' ? '' : value;
                    applyFilters();
                    const btn = this.previousElementSibling;
                    btn.textContent = value === '' ? 'Filtrar' : e.target.textContent;
                }
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