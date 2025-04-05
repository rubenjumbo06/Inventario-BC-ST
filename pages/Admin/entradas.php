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
        .search-container {
            margin: 20px 0;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
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
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            width: 100%;
            text-align: center;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .date-filter {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .date-filter label {
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            font-weight: bold;
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
        /* Estilos para los botones de acción */
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
        /* Estilos para el modal */
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
                <?php
                    // Formatear la fecha con comillas especiales y hora
                    $fecha = new DateTime($row['fecha_creacion']);
                    $fecha_formateada = $fecha->format('d/m/Y H:i:s');
                ?>
                <tr>
                    <td><?php echo $row['id_entradas']; ?></td>
                    <td><?php echo $fecha_formateada; ?></td>
                    <td><?php echo htmlspecialchars($row['items']); ?></td>
                    <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                    <td><?php echo htmlspecialchars($row['body']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td>
                        <a href="../../Uses/editarent.php?id_entradas=<?php echo $row['id_entradas']; ?>">
                            <button class="edit-icon-btn icon-btn" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                </svg>
                            </button>
                        </a>
                        <button class="delete-icon-btn icon-btn deleteBtn" 
                                data-id="<?php echo $row['id_entradas']; ?>" 
                                data-fecha="<?php echo $fecha_formateada; ?>" 
                                data-items="<?php echo htmlspecialchars($row['items']); ?>" 
                                data-titulo="<?php echo htmlspecialchars($row['titulo']); ?>" 
                                data-body="<?php echo htmlspecialchars($row['body']); ?>" 
                                data-usuario="<?php echo htmlspecialchars($row['username']); ?>"
                                title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/>
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
                <p>¿Estás seguro de que deseas eliminar esta entrada?</p>
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
                const fechaTexto = rows[i].getElementsByTagName('td')[1].textContent.replace(/[“”]/g, ''); // Quitar comillas
                const usuario = rows[i].getElementsByTagName('td')[5].textContent; // Columna "Usuario"

                const matchesTitulo = !tituloTerm || titulo.includes(tituloTerm);

                let matchesFecha = true;
                if (fechaDesde || fechaHasta) {
                    const fechaRowParts = fechaTexto.split(' ')[0].split('/'); // Extraer solo la parte de fecha DD/MM/YYYY
                    const fechaRowFormatted = `${fechaRowParts[2]}-${fechaRowParts[1]}-${fechaRowParts[0]}`; // Convertir a YYYY-MM-DD
                    const fechaRow = new Date(fechaRowFormatted);
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

        // Funcionalidad del modal de eliminación
        const modal = document.getElementById('deleteModal');
        const modalData = document.getElementById('modalData');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');
        let currentId = null;

        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentId = this.dataset.id;
                const fecha = this.dataset.fecha;
                const items = this.dataset.items;
                const titulo = this.dataset.titulo;
                const body = this.dataset.body;
                const usuario = this.dataset.usuario;

                modalData.innerHTML = `
                    <p><strong>ID:</strong> ${currentId}</p>
                    <p><strong>Fecha Creación:</strong> ${fecha}</p>
                    <p><strong>Items:</strong> ${items}</p>
                    <p><strong>Título:</strong> ${titulo}</p>
                    <p><strong>Cuerpo:</strong> ${body}</p>
                    <p><strong>Usuario:</strong> ${usuario}</p>
                `;
                modal.style.display = 'block';
            });
        });

        cancelDelete.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        confirmDelete.addEventListener('click', function() {
            fetch('../../Uses/eliminarent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_entradas=${encodeURIComponent(currentId)}`
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
                } else {
                    alert('Error al eliminar la entrada: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al intentar eliminar la entrada');
            });
        });

        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>