<?php
session_start(); 
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];

require_once("../../conexion.php"); 

// Consulta modificada para incluir el nombre del usuario
$sql = "SELECT e.id_entradas, e.fecha_creacion, e.items, e.titulo, e.body, e.id_user, u.username 
        FROM tbl_reg_entradas e
        LEFT JOIN tbl_users u ON e.id_user = u.id_user";
$result = $conn->query($sql);

// Consulta para obtener usuarios únicos para el filtro
$users_sql = "SELECT DISTINCT u.id_user, u.username FROM tbl_users u 
              INNER JOIN tbl_reg_entradas e ON u.id_user = e.id_user 
              ORDER BY u.username";
$users_result = $conn->query($users_sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Entradas</title>
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        /* Estilos para los campos de búsqueda */
        .search-container {
            margin: 20px 0;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap; /* Permite que se ajusten en pantallas pequeñas */
        }
        .search-container input[type="text"],
        .search-container input[type="date"] {
            padding: 8px;
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .search-label {
            font-size: 20px; /* Similar al tamaño de h1.title */
            font-weight: bold;
            margin-bottom: 10px;
            width: 100%;
            text-align: center;
            color: white; /* Combina con el estilo de la página */
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); /* Igual que text-shadow */
        }
        .date-filter {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .date-filter label {
            color: white; /* Combina con el estilo de la página */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3); /* Sombra más suave para etiquetas */
            font-weight: bold;
            font-size: 14px; /* Tamaño más pequeño pero legible */
        }
        /* Estilos para el filtro desplegable */
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
            <h1 class="title text-shadow">Tabla de Entradas</h1>    
        </strong>
        <div class="button-container">
            <a href="../../EXCEL/generate_ent_xls.php">
                <button class="excelBtn">Descargar Excel</button>
            </a>
            <form action="../../PDF/generate_ent_pdf.php" method="post">
                <button type="submit" class="pdfBtn">Descargar PDF</button>
            </form>
        </div> 
        <div class="search-container">
            <input type="text" id="searchTitulo" placeholder="Buscar por título...">
            <div class="search-label">Filtrar por Fecha</div>
            <div class="date-filter">
                <label>Desde:</label>
                <input type="date" id="searchFechaDesde">
            </div>
            <div class="date-filter">
                <label>Hasta:</label>
                <input type="date" id="searchFechaHasta">
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha Creación</th>
                    <th>Items</th>
                    <th>Título</th>
                    <th>Cuerpo</th>
                    <th>Usuario
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="usuario">
                                <option data-value="">Todos</option>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                    <option data-value="<?php echo htmlspecialchars($user['username']); ?>">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_entradas']; ?></td>
                    <td><?php echo $row['fecha_creacion']; ?></td>
                    <td><?php echo $row['items']; ?></td>
                    <td><?php echo $row['titulo']; ?></td>
                    <td><?php echo $row['body']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td>
                        <a href="../../Uses/editarent.php?id_entradas=<?php echo $row['id_entradas']; ?>">
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
                fechaHoraElemento.textContent = Fecha/Hora Ingreso: ${fechaHoraFormateada};
            }
        }
        actualizarFechaHora();
        setInterval(actualizarFechaHora, 1000);

        // Funcionalidad de búsqueda y filtrado
        const searchTitulo = document.getElementById('searchTitulo');
        const searchFechaDesde = document.getElementById('searchFechaDesde');
        const searchFechaHasta = document.getElementById('searchFechaHasta');
        const table = document.querySelector('table');
        const rows = table.getElementsByTagName('tr');

        let filters = {
            usuario: ''
        };

        function applyFilters() {
            const tituloTerm = searchTitulo.value.toLowerCase();
            const fechaDesde = searchFechaDesde.value; // Formato YYYY-MM-DD
            const fechaHasta = searchFechaHasta.value; // Formato YYYY-MM-DD

            for (let i = 1; i < rows.length; i++) { // Empezamos en 1 para saltar el encabezado
                const titulo = rows[i].getElementsByTagName('td')[3].textContent.toLowerCase(); // Columna "Título"
                const fecha = rows[i].getElementsByTagName('td')[1].textContent.split(' ')[0]; // Columna "Fecha" (solo fecha)
                const usuario = rows[i].getElementsByTagName('td')[5].textContent; // Columna "Usuario"

                const matchesTitulo = !tituloTerm || titulo.includes(tituloTerm);

                let matchesFecha = true;
                if (fechaDesde || fechaHasta) {
                    const fechaRow = new Date(fecha);
                    const desde = fechaDesde ? new Date(fechaDesde) : null;
                    const hasta = fechaHasta ? new Date(fechaHasta) : null;

                    if (desde && hasta) {
                        matchesFecha = fechaRow >= desde && fechaRow <= hasta;
                    } else if (desde) {
                        matchesFecha = fechaRow >= desde;
                    } else if (hasta) {
                        matchesFecha = fechaRow <= hasta;
                    }
                }

                const matchesUsuario = !filters.usuario || usuario === filters.usuario;

                if (matchesTitulo && matchesFecha && matchesUsuario) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        searchTitulo.addEventListener('keyup', applyFilters);
        searchFechaDesde.addEventListener('change', applyFilters);
        searchFechaHasta.addEventListener('change', applyFilters);

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
    });
</script>
</body>
</html>