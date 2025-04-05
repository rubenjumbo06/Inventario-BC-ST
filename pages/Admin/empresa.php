<?php
session_start(); 
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];

require_once("../../conexion.php"); 

// Consulta para mostrar solo registros con id_status = 1
$sql = "SELECT id_empresa, nombre, ruc, servicio_empresa 
        FROM tbl_empresa 
        WHERE id_status = 1";
$result = $conn->query($sql);

// Consulta para el selector de empresas en el modal
$empresas_sql = "SELECT id_empresa, nombre FROM tbl_empresa WHERE id_status = 1";
$empresas_result = $conn->query($empresas_sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Empresa</title>
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
        .search-container { margin: 20px 0; text-align: center; display: flex; justify-content: center; gap: 10px; }
        .search-container input[type="text"] { padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
        .icon-btn { background: none; border: none; padding: 8px; cursor: pointer; font-size: 16px; transition: transform 0.2s ease; }
        .edit-icon-btn { color: rgb(243, 126, 2); }
        .edit-icon-btn:hover { color: rgb(163, 87, 5); transform: scale(1.1); }
        .delete-icon-btn { color: #dc3545; }
        .delete-icon-btn:hover { color: #b02a37; transform: scale(1.1); }
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 15% auto; padding: 20px; border-radius: 5px; width: 80%; max-width: 500px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
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
        <strong><h1 class="title text-shadow">Tabla de Empresa</h1></strong>
        <div class="button-container">
            <a href="../../Uses/agregaremp.php"><button id="addBtn">Agregar Nuevo</button></a>
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
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['ruc']); ?></td>
                    <td><?php echo htmlspecialchars($row['servicio_empresa']); ?></td>
                    <td>
                        <a href="../../Uses/editaremp.php?id_empresa=<?php echo $row['id_empresa']; ?>">
                            <button class="edit-icon-btn icon-btn" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                </svg>
                            </button>
                        </a>
                        <button class="delete-icon-btn icon-btn deleteBtn" 
                                data-id="<?php echo $row['id_empresa']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>" 
                                data-ruc="<?php echo htmlspecialchars($row['ruc']); ?>" 
                                data-servicio="<?php echo htmlspecialchars($row['servicio_empresa']); ?>"
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
                    <p>Selecciona una empresa para reasignar los registros:</p>
                    <select id="reassignEmpresa" class="reassign-select">
                        <option value="">Selecciona una empresa</option>
                        <?php while ($empresa = $empresas_result->fetch_assoc()): ?>
                            <option value="<?php echo $empresa['id_empresa']; ?>">
                                <?php echo htmlspecialchars($empresa['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
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

        const searchNombre = document.getElementById('searchNombre');
        const searchRuc = document.getElementById('searchRuc');
        const table = document.querySelector('table');
        const rows = table.getElementsByTagName('tr');
        const pdfForm = document.getElementById('pdfForm');
        const excelForm = document.getElementById('excelForm');

        function applyFilters() {
            const nombreTerm = searchNombre.value.toLowerCase();
            const rucTerm = searchRuc.value.toLowerCase();
            document.getElementById('filter_nombre').value = nombreTerm;
            document.getElementById('filter_ruc').value = rucTerm;
            document.getElementById('excel_filter_nombre').value = nombreTerm;
            document.getElementById('excel_filter_ruc').value = rucTerm;

            for (let i = 1; i < rows.length; i++) {
                const nombreEmpresa = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                const rucEmpresa = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                const matchesNombre = !nombreTerm || nombreEmpresa.includes(nombreTerm);
                const matchesRuc = !rucTerm || rucEmpresa.includes(rucTerm);
                rows[i].style.display = (matchesNombre && matchesRuc) ? '' : 'none';
            }
        }

        searchNombre.addEventListener('keyup', applyFilters);
        searchRuc.addEventListener('keyup', applyFilters);
        pdfForm.addEventListener('submit', applyFilters);
        excelForm.addEventListener('submit', applyFilters);

        // Lógica de eliminación
        const modal = document.getElementById('deleteModal');
        const modalMessage = document.getElementById('modalMessage');
        const modalData = document.getElementById('modalData');
        const reassignContainer = document.getElementById('reassignContainer');
        const reassignEmpresa = document.getElementById('reassignEmpresa');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');
        const notification = document.getElementById('notification');
        let currentId = null;

        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentId = this.dataset.id;
                const nombre = this.dataset.nombre;
                const ruc = this.dataset.ruc;
                const servicio = this.dataset.servicio;

                modalData.innerHTML = `
                    <p><strong>ID:</strong> ${currentId}</p>
                    <p><strong>Nombre:</strong> ${nombre}</p>
                    <p><strong>RUC:</strong> ${ruc}</p>
                    <p><strong>Servicio:</strong> ${servicio}</p>
                `;

                fetch('../../Uses/eliminar_emp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_empresa=${encodeURIComponent(currentId)}&check_only=true`
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        showNotification('Error: ' + (data.message || 'Error al verificar relaciones'), true);
                        return;
                    }
                    if (data.related) {
                        modalMessage.innerHTML = `
                            <p>Esta empresa tiene registros relacionados en las siguientes tablas:</p>
                            <ul>${data.tables.map(table => `<li>${table}</li>`).join('')}</ul>
                            <p>Por favor, reasigna estos registros a otra empresa antes de ocultarla.</p>
                        `;
                        reassignContainer.style.display = 'block';
                        reassignEmpresa.querySelectorAll('option').forEach(opt => {
                            if (opt.value === currentId) opt.disabled = true;
                            else opt.disabled = false;
                        });
                    } else {
                        modalMessage.innerHTML = '<p>¿Estás seguro de ocultar esta empresa? No tiene registros relacionados.</p>';
                        reassignContainer.style.display = 'none';
                    }
                    modal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al verificar relaciones de la empresa.', true);
                });
            });
        });

        cancelDelete.addEventListener('click', function() {
            modal.style.display = 'none';
            reassignContainer.style.display = 'none';
        });

        confirmDelete.addEventListener('click', function() {
            const newEmpresaId = reassignEmpresa.value;
            const body = newEmpresaId ? 
                `id_empresa=${encodeURIComponent(currentId)}&new_empresa=${encodeURIComponent(newEmpresaId)}` :
                `id_empresa=${encodeURIComponent(currentId)}`;

            fetch('../../Uses/eliminar_emp.php', {
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
                    showNotification(data.message || 'Empresa eliminada correctamente');
                } else {
                    showNotification('Error: ' + (data.message || 'No se pudo procesar la solicitud'), true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ocurrió un error al intentar ocultar la empresa.', true);
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