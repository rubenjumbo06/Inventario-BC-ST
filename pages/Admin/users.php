<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];

require_once("../../conexion.php");

// Consulta para mostrar solo usuarios con id_status = 1
$sql = "SELECT id_user, nombre, apellidos, username, role, correo, telefono, fecha_creacion, fecha_modificacion 
        FROM tbl_users 
        WHERE id_status = 1";
$result = $conn->query($sql);

// Consulta para obtener roles únicos
$roles_sql = "SELECT DISTINCT role FROM tbl_users WHERE id_status = 1 ORDER BY role";
$roles_result = $conn->query($roles_sql);

// Consulta para el selector de usuarios en el modal
$users_sql = "SELECT id_user, CONCAT(nombre, ' ', apellidos) AS nombre_completo 
              FROM tbl_users 
              WHERE id_status = 1 AND id_user != ?";
$users_stmt = $conn->prepare($users_sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Usuarios</title>
    <link rel="stylesheet" href="../../assets/CSS/tables.css">
    <style>
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: 250px; z-index: 1000; }
        .main-content { margin-left: 250px; padding: 20px; width: calc(100% - 250px); margin-top: 20px; }
        .header { top: 0; left: 250px; position: fixed; width: calc(100% - 250px); height: 64px; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); z-index: 1000; }
        #addBtn { background-color: rgb(3, 70, 141); color: white !important; border: none; padding: 8px 15px; cursor: pointer; border-radius: 5px; font-size: 14px; transition: background-color 0.3s ease; }
        #addBtn:hover { background-color: rgb(3, 24, 46); }
        .excelBtn { background-color: #28a745; color: white !important; border: none; padding: 8px 15px; cursor: pointer; border-radius: 5px; font-size: 14px; transition: background-color 0.3s ease; }
        .excelBtn:hover { background-color: #185732; }
        .pdfBtn { background-color: #dc3545; color: white !important; border: none; padding: 8px 15px; cursor: pointer; border-radius: 5px; font-size: 14px; transition: background-color 0.3s ease; }
        .pdfBtn:hover { background-color: rgb(167, 35, 31); }
        .button-container { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
        .search-container { margin: 20px 0; text-align: center; }
        .search-container input[type="text"] { padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
        .filter-container { position: relative; display: inline-block; }
        .filter-btn { background: none; border: none; cursor: pointer; font-size: 12px; padding: 0 5px; vertical-align: middle; }
        .filter-btn::after { content: '▼'; margin-left: 5px; font-size: 10px; }
        .filter-dropdown { display: none; position: absolute; background-color: white; min-width: 120px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); z-index: 1; border-radius: 5px; max-height: 200px; overflow-y: auto; }
        .filter-dropdown option { padding: 8px 12px; display: block; color: black; text-decoration: none; }
        .filter-dropdown option:hover { background-color: #f1f1f1; }
        .filter-container:hover .filter-dropdown, .filter-dropdown.active { display: block; }
        .icon-btn { background: none; border: none; padding: 8px; cursor: pointer; font-size: 16px; transition: transform 0.2s ease; }
        .edit-icon-btn { color: rgb(243, 126, 2); }
        .edit-icon-btn:hover { color: rgb(163, 87, 5); transform: scale(1.1); }
        .delete-icon-btn { color: #dc3545; }
        .delete-icon-btn:hover { color: #b02a37; transform: scale(1.1); }
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 10% auto; padding: 20px; border-radius: 5px; width: 80%; max-width: 600px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .modal-buttons { margin-top: 20px; display: flex; justify-content: space-between; }
        .confirmBtn { background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .confirmBtn:hover { background-color: rgb(159, 38, 50); }
        .cancelBtn { background-color: rgb(30, 172, 59); color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .cancelBtn:hover { background-color: rgb(23, 99, 23); }
        .reassign-select { width: 100%; padding: 8px; margin-top: 10px; border-radius: 5px; }
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
        <strong><h1 class="title text-shadow">Tabla de Usuarios</h1></strong>
        <div class="button-container">
            <a href="../../Uses/agregarusers.php"><button id="addBtn">Agregar Nuevo</button></a>
            <form id="excelForm" action="../../EXCEL/generate_users_xls.php" method="post">
                <input type="hidden" name="filter_search" id="excel_filter_search" value="">
                <input type="hidden" name="filter_rol" id="excel_filter_rol" value="">
                <button type="submit" class="excelBtn">Descargar Excel</button>
            </form>
            <form id="pdfForm" action="../../PDF/generate_users_pdf.php" method="post">
                <input type="hidden" name="filter_search" id="filter_search" value="">
                <input type="hidden" name="filter_rol" id="filter_rol" value="">
                <button type="submit" class="pdfBtn">Descargar PDF</button>
            </form>
        </div>   
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Buscar por nombre...">
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellidos</th>
                    <th>Usuario</th>
                    <th>Rol
                        <div class="filter-container">
                            <button class="filter-btn">Filtrar</button>
                            <div class="filter-dropdown" data-filter="rol">
                                <option data-value="">Todos</option>
                                <?php while ($rol = $roles_result->fetch_assoc()): ?>
                                    <option data-value="<?php echo htmlspecialchars($rol['role']); ?>">
                                        <?php echo htmlspecialchars($rol['role']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Fecha Creación</th>
                    <th>Fecha Modificación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id_user']; ?></td>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['apellidos']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                    <td><?php echo htmlspecialchars($row['correo']); ?></td>
                    <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                    <td><?php echo $row['fecha_creacion']; ?></td>
                    <td><?php echo $row['fecha_modificacion']; ?></td>
                    <td>
                        <a href="../../Uses/editarusers.php?id_user=<?php echo $row['id_user']; ?>">
                            <button class="edit-icon-btn icon-btn" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                </svg>
                            </button>
                        </a>
                        <button class="delete-icon-btn icon-btn deleteBtn" 
                                data-id="<?php echo $row['id_user']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                data-apellidos="<?php echo htmlspecialchars($row['apellidos']); ?>"
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

        <!-- Modal de eliminación -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h2>Confirmar Eliminación</h2>
                <div id="modalMessage"></div>
                <div id="modalData"></div>
                <div id="reassignContainer" style="display: none;">
                    <p>Selecciona un usuario para reasignar los registros:</p>
                    <select id="reassignUser" class="reassign-select">
                        <option value="">Selecciona un usuario</option>
                        <?php 
                        $users_stmt->bind_param("i", $_SESSION['id_user']);
                        $users_stmt->execute();
                        $users_result = $users_stmt->get_result();
                        while ($user = $users_result->fetch_assoc()): ?>
                            <option value="<?php echo $user['id_user']; ?>">
                                <?php echo htmlspecialchars($user['nombre_completo']); ?>
                            </option>
                        <?php endwhile; $users_stmt->close(); ?>
                    </select>
                </div>
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
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
            document.getElementById("fechaHora").textContent = `Fecha/Hora Ingreso: ${fechaHoraFormateada}`;
        }
        actualizarFechaHora();
        setInterval(actualizarFechaHora, 1000);

        const searchInput = document.getElementById('searchInput');
        const table = document.querySelector('table');
        const rows = table.getElementsByTagName('tr');
        const pdfForm = document.getElementById('pdfForm');
        let filters = { rol: '' };
        const excelForm = document.getElementById('excelForm');

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            document.getElementById('filter_search').value = searchTerm;
            document.getElementById('filter_rol').value = filters.rol;
            document.getElementById('excel_filter_search').value = searchTerm;
            document.getElementById('excel_filter_rol').value = filters.rol;

            for (let i = 1; i < rows.length; i++) {
                const nombre = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const rol = rows[i].getElementsByTagName('td')[4].textContent;
                const matchesSearch = nombre.includes(searchTerm);
                const matchesRol = !filters.rol || rol === filters.rol;
                rows[i].style.display = (matchesSearch && matchesRol) ? '' : 'none';
            }
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

        pdfForm.addEventListener('submit', applyFilters);
        excelForm.addEventListener('submit', applyFilters);

        // Lógica de eliminación
        const modal = document.getElementById('deleteModal');
        const modalMessage = document.getElementById('modalMessage');
        const modalData = document.getElementById('modalData');
        const reassignContainer = document.getElementById('reassignContainer');
        const reassignUser = document.getElementById('reassignUser');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');
        const notification = document.getElementById('notification');
        let currentId = null;

        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentId = this.dataset.id;
                const nombre = this.dataset.nombre;
                const apellidos = this.dataset.apellidos;

                modalData.innerHTML = `
                    <p><strong>ID:</strong> ${currentId}</p>
                    <p><strong>Nombre:</strong> ${nombre}</p>
                    <p><strong>Apellidos:</strong> ${apellidos}</p>
                `;

                fetch('../../Uses/eliminarusers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_user=${encodeURIComponent(currentId)}&check_only=true`
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        showNotification('Error: ' + (data.message || 'Error al verificar relaciones'), true);
                        return;
                    }
                    if (data.related) {
                        modalMessage.innerHTML = `
                            <p>Este usuario tiene registros relacionados en las siguientes tablas:</p>
                            <ul>${data.tables.map(table => `<li>${table}</li>`).join('')}</ul>
                            <p>Por favor, reasigna estos registros a otro usuario antes de ocultarlo.</p>
                        `;
                        reassignContainer.style.display = 'block';
                        reassignUser.querySelectorAll('option').forEach(opt => {
                            if (opt.value === currentId) opt.disabled = true;
                            else opt.disabled = false;
                        });
                    } else {
                        modalMessage.innerHTML = '<p>¿Estás seguro de ocultar este usuario? No tiene registros relacionados.</p>';
                        reassignContainer.style.display = 'none';
                    }
                    modal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al verificar relaciones del usuario.', true);
                });
            });
        });

        cancelDelete.addEventListener('click', function() {
            modal.style.display = 'none';
            reassignContainer.style.display = 'none';
        });

        confirmDelete.addEventListener('click', function() {
            const newUserId = reassignUser.value;
            const body = newUserId ? 
                `id_user=${encodeURIComponent(currentId)}&new_user=${encodeURIComponent(newUserId)}` :
                `id_user=${encodeURIComponent(currentId)}`;

            fetch('../../Uses/eliminarusers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
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
                    reassignContainer.style.display = 'none';
                    showNotification(data.message || 'Usuario eliminado exitosamente.');
                } else {
                    showNotification('Error: ' + (data.message || 'No se pudo procesar la solicitud.'), true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ocurrió un error al intentar eliminar el usuario.', true);
            });
        });

        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                reassignContainer.style.display = 'none';
            }
        });

        // Función para mostrar notificaciones dinámicamente
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