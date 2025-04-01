<?php
session_start(); 
date_default_timezone_set('America/Lima'); // Ajusta esto a tu zona horaria
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'tecnico') {
    header('Location: login.php');
    exit;
}
$usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario no definido'; 
$role = $_SESSION['role']; 

include '../../conexion.php';
$conexion = $conn;

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Consultar herramientas que están en campo
$sql_h = "SELECT id_herramientas, nombre_herramientas, ubicacion_herramientas FROM tbl_herramientas WHERE ubicacion_herramientas = 'En campo'";
$resultado_h = $conexion->query($sql_h);
if (!$resultado_h) {
    die("Error en consulta de herramientas: " . $conexion->error);
}

// Consultar activos que están en instalación
$sql_act = "SELECT id_activos, nombre_activos, ubicacion_activos FROM tbl_activos WHERE ubicacion_activos = 'En instalación'";
$resultado_act = $conexion->query($sql_act);
if (!$resultado_act) {
    die("Error en consulta de activos: " . $conexion->error);
}

// Consultar consumibles que están en campo
$sql_con = "SELECT id_consumibles, nombre_consumibles, cantidad_consumibles FROM tbl_consumibles WHERE ubicacion_consumibles = 'En campo'";
$resultado_con = $conexion->query($sql_con);
if (!$resultado_con) {
    die("Error en consulta de consumibles: " . $conexion->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    
    // Validación del título (solo letras, números, espacios y algunos símbolos básicos)
    $regex = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s.,;:%¿?¡!()-]+$/';
    if (!preg_match($regex, $titulo)) {
        echo "<script>alert('El título solo puede contener letras, números, espacios y signos básicos de puntuación'); window.location='reg_entradas.php';</script>";
        exit;
    }

    $id_user = $_SESSION['id_user'];
    $selectedItems = json_decode($_POST['body'], true);
    $totalItems = 0;
    $body = "";
    $fecha_creacion = date('Y-m-d H:i:s');

    // Procesar herramientas
    if (!empty($selectedItems['herramientas'])) {
        $herramientas = array_map(function ($nombre) {
            return htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); // Escapar comillas para evitar problemas
        }, $selectedItems['herramientas']);
        $body .= "Herramientas: (" . implode(", ", $herramientas) . "), ";
        $totalItems += count($selectedItems['herramientas']);

        foreach ($selectedItems['herramientas'] as $id => $nombre) {
            $stmt = $conn->prepare("UPDATE tbl_herramientas SET ubicacion_herramientas = 'En almacen' WHERE id_herramientas = ? AND ubicacion_herramientas = 'En campo'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Procesar activos
    if (!empty($selectedItems['activos'])) {
        $activos = array_map(function ($nombre) {
            return $nombre;
        }, $selectedItems['activos']);
        $body .= "Activos: (" . implode(", ", $activos) . "), ";
        $totalItems += count($selectedItems['activos']);

        foreach ($selectedItems['activos'] as $id => $nombre) {
            $stmt = $conn->prepare("UPDATE tbl_activos SET ubicacion_activos = 'En almacen' WHERE id_activos = ? AND ubicacion_activos = 'En instalación'");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                echo "<script>alert('Error al actualizar activo ID $id: " . $stmt->error . "'); window.location='reg_entradas.php';</script>";
                exit;
            }
            $stmt->close();
        }
    }

    // Procesar consumibles
if (!empty($selectedItems['consumibles'])) {
    $consumibles = [];
    foreach ($selectedItems['consumibles'] as $id => $data) {
        $nombre = $data['nombre'];
        $cantidadSeleccionada = (int)$data['cantidad'];

        // Verificar cantidad en "En campo" y obtener datos adicionales
        $check_stmt = $conn->prepare("SELECT cantidad_consumibles, id_empresa, estado_consumibles, utilidad_consumibles, id_user FROM tbl_consumibles WHERE id_consumibles = ? AND ubicacion_consumibles = 'En campo'");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows == 0) {
            echo "<script>alert('Error: El consumible $nombre no está en campo'); window.location='reg_entradas.php';</script>";
            exit;
        }
        $row = $check_result->fetch_assoc();
        $cantidad_en_campo = (int)($row['cantidad_consumibles'] ?? 0);
        $id_empresa = $row['id_empresa'];
        $estado_consumibles = $row['estado_consumibles'];
        $utilidad_consumibles = $row['utilidad_consumibles'];
        $id_user_consumible = $row['id_user'];
        $check_stmt->close();

        if ($cantidadSeleccionada > $cantidad_en_campo) {
            echo "<script>alert('Error: No puedes devolver más ($cantidadSeleccionada) de $nombre de lo que está en campo ($cantidad_en_campo)'); window.location='reg_entradas.php';</script>";
            exit;
        }

        $consumibles[] = "$nombre($cantidadSeleccionada)";
        $totalItems += $cantidadSeleccionada;

        // Buscar registro en "En almacén" para sumar la cantidad devuelta
        $check_almacen_stmt = $conn->prepare("SELECT id_consumibles, cantidad_consumibles FROM tbl_consumibles WHERE nombre_consumibles = ? AND ubicacion_consumibles = 'En almacen' AND id_empresa = ?");
        $check_almacen_stmt->bind_param("si", $nombre, $id_empresa);
        $check_almacen_stmt->execute();
        $almacen_result = $check_almacen_stmt->get_result();
        if ($almacen_result->num_rows > 0) {
            // Si existe en almacén, sumar la cantidad devuelta
            $almacen_row = $almacen_result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE tbl_consumibles SET cantidad_consumibles = cantidad_consumibles + ? WHERE id_consumibles = ? AND ubicacion_consumibles = 'En almacen'");
            $stmt->bind_param("ii", $cantidadSeleccionada, $almacen_row['id_consumibles']);
        } else {
            // Si no existe en almacén, crear un nuevo registro con todos los campos obligatorios
            $stmt = $conn->prepare("INSERT INTO tbl_consumibles (nombre_consumibles, cantidad_consumibles, id_empresa, estado_consumibles, utilidad_consumibles, id_user, ubicacion_consumibles) VALUES (?, ?, ?, ?, ?, ?, 'En almacen')");
            $stmt->bind_param("siiiii", $nombre, $cantidadSeleccionada, $id_empresa, $estado_consumibles, $utilidad_consumibles, $id_user_consumible);
        }
        if (!$stmt->execute()) {
            echo "<script>alert('Error al actualizar/insertar consumible $nombre en almacén: " . $stmt->error . "'); window.location='reg_entradas.php';</script>";
            exit;
        }
        $stmt->close();
        $check_almacen_stmt->close();

        // Restar la cantidad devuelta del registro en "En campo"
        $update_campo_stmt = $conn->prepare("UPDATE tbl_consumibles SET cantidad_consumibles = cantidad_consumibles - ? WHERE id_consumibles = ? AND ubicacion_consumibles = 'En campo'");
        $update_campo_stmt->bind_param("ii", $cantidadSeleccionada, $id);
        if (!$update_campo_stmt->execute()) {
            echo "<script>alert('Error al actualizar consumible ID $id en campo: " . $update_campo_stmt->error . "'); window.location='reg_entradas.php';</script>";
            exit;
        }
        $update_campo_stmt->close();

        // Si la cantidad en "En campo" llega a 0, eliminar el registro
        $delete_stmt = $conn->prepare("DELETE FROM tbl_consumibles WHERE id_consumibles = ? AND cantidad_consumibles = 0 AND ubicacion_consumibles = 'En campo'");
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
    $body .= "Consumibles: [" . implode(", ", $consumibles) . "]";
}

    // Insertar en tbl_reg_entradas
    $stmt = $conn->prepare("INSERT INTO tbl_reg_entradas (fecha_creacion, items, titulo, body, id_user) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $fecha_creacion, $totalItems, $titulo, $body, $id_user);
    
    if ($stmt->execute()) {
        echo "<script>alert('Entrada registrada exitosamente'); window.location='reg_entradas.php';</script>";
    } else {
        echo "<script>alert('Error al registrar la entrada: " . $stmt->error . "'); window.location='reg_entradas.php';</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Entradas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --verde-oscuro: #1C4029;
            --verde-claro: #53A670;
            --beige: #D9D2B0;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: var(--beige);
            background-image: url('../img/fond.jpg'); 
            background-size: cover;
            background-position: center; 
            background-repeat: no-repeat; 
            background-attachment: fixed;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        .text-shadow {
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        .custom-input, .custom-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--verde-oscuro);
            border-radius: 8px;
            background-color: white;
            color: #1f2937;
            transition: border-color 0.3s ease;
        }
        .custom-input:focus, .custom-select:focus {
            outline: none;
            border-color: var(--verde-claro);
            box-shadow: 0 0 0 2px rgba(83, 166, 112, 0.2);
        }
        .custom-checkbox {
            accent-color: var(--verde-claro);
        }
        .error-text {
            color: #ef4444;
            font-size: 0.875rem;
        }
        .border-error {
            border-color: #ef4444 !important;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
    let selectedItems = {
        herramientas: {},
        activos: {},
        consumibles: {}
    };

    window.agregarElemento = function (tipo, id, nombre, cantidad = null) {
        let container = selectedItems[tipo];
        if (cantidad !== null) {
            if (cantidad > 0) {
                container[id] = { nombre: nombre, cantidad: cantidad };
            } else {
                delete container[id];
            }
        } else {
            if (container[id]) {
                delete container[id];
            } else {
                // Guardar el nombre tal cual, sin escapar innecesariamente
                container[id] = nombre;
            }
        }
        actualizarResumen();
    };

    window.validarCantidad = function (id) {
        let cantidadElemento = document.getElementById(`cantidad-${id}`);
        let errorElemento = document.getElementById(`error-${id}`);
        
        if (!cantidadElemento || !errorElemento) return;
        
        cantidadElemento.value = cantidadElemento.value.replace(/[^0-9]/g, '');
        let cantidadIngresada = parseInt(cantidadElemento.value) || 0;
        let cantidadDisponible = parseInt(cantidadElemento.getAttribute('data-max')) || 0;

        if (cantidadIngresada < 0) {
            cantidadIngresada = 0;
            errorElemento.textContent = "La cantidad no puede ser negativa";
        } else if (cantidadIngresada > cantidadDisponible) {
            cantidadIngresada = cantidadDisponible;
            errorElemento.textContent = `No puedes devolver más de ${cantidadDisponible}`;
        } else {
            errorElemento.textContent = "";
        }
        cantidadElemento.value = cantidadIngresada;

        let nombreElemento = cantidadElemento.closest('li')?.querySelector('span');
        let nombre = nombreElemento ? nombreElemento.textContent.split('(')[0].trim() : 'Desconocido';

        if (cantidadIngresada > 0) {
            selectedItems.consumibles[id] = { nombre: nombre, cantidad: cantidadIngresada };
        } else {
            delete selectedItems.consumibles[id];
        }
        actualizarResumen();
    };

    function actualizarResumen() {
        let resumen = [];
        let totalItems = 0;

        if (Object.keys(selectedItems.herramientas).length > 0) {
            let herramientas = Object.values(selectedItems.herramientas);
            resumen.push(`Herramientas: (${herramientas.join(", ")})`);
            totalItems += herramientas.length;
        }

        if (Object.keys(selectedItems.activos).length > 0) {
            let activos = Object.values(selectedItems.activos);
            resumen.push(`Activos: (${activos.join(", ")})`);
            totalItems += activos.length;
        }

        if (Object.keys(selectedItems.consumibles).length > 0) {
            let consumibles = Object.values(selectedItems.consumibles).map(item => `${item.nombre}(${item.cantidad})`);
            resumen.push(`Consumibles: [${consumibles.join(", ")}]`);
            totalItems += Object.values(selectedItems.consumibles).reduce((sum, item) => sum + item.cantidad, 0);
        }

        document.getElementById('selectedList').innerHTML = resumen.join(", ");
        document.getElementById('bodyField').value = JSON.stringify(selectedItems);
        document.getElementById('totalItems').textContent = totalItems;
    }

    // Agregar eventos a los checkboxes de herramientas
    document.querySelectorAll('.herramienta-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            agregarElemento('herramientas', id, nombre);
        });
    });

            function buscarHerramientas() {
                let input = document.getElementById('searchHerramientas').value.toLowerCase();
                document.querySelectorAll('.herramienta').forEach(herramienta => {
                    let texto = herramienta.textContent.toLowerCase();
                    herramienta.style.display = texto.includes(input) ? 'block' : 'none';
                });
            }

            function buscarActivos() {
                let input = document.getElementById('searchActivos').value.toLowerCase();
                document.querySelectorAll('.activo').forEach(activo => {
                    let texto = activo.textContent.toLowerCase();
                    activo.style.display = texto.includes(input) ? 'block' : 'none';
                });
            }

            document.getElementById("searchHerramientas").addEventListener("input", buscarHerramientas);
            document.getElementById("searchActivos").addEventListener("input", buscarActivos);

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
                document.getElementById("fechaHora").textContent = `Fecha/Hora: ${fechaHoraFormateada}`;
            }
            actualizarFechaHora();
            setInterval(actualizarFechaHora, 1000);

            window.abrirModal = function(modalId) {
                document.getElementById(modalId).style.display = 'flex';
            }

            window.cerrarModal = function(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            }
        });
    </script>
</head>
<body class="min-h-screen p-6">
    <?php include '../header.php'; ?>
    <?php include 'sidebartec.php'; ?>

    <div class="main-content">
        <div class="flex justify-between items-center mt-4 px-4 animate-fade-in">
            <p class="text-white text-sm sm:text-lg text-shadow">
                <strong>Usuario:</strong> <?php echo htmlspecialchars($usuario); ?> 
                <span id="user-role"><?php echo !empty($role) ? "($role)" : ''; ?></span>
            </p>
            <p id="fechaHora" class="text-white text-sm sm:text-lg text-shadow">
                <strong>Fecha/Hora:</strong> Cargando...
            </p>
        </div>

        <div class="p-6 animate-fade-in">
            <div class="flex justify-around mb-4">
                <button onclick="abrirModal('modalHerramientas')" 
                        class="rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                    Herramientas
                </button>
                <button onclick="abrirModal('modalActivos')" 
                        class="rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                    Activos
                </button>
                <button onclick="abrirModal('modalConsumibles')" 
                        class="rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                    Consumibles
                </button>
            </div>

            <div class="bg-white p-4 rounded-lg shadow-lg mb-6">
                <h3 class="text-lg font-bold text-[var(--verde-oscuro)] text-shadow">Resumen de Selección</h3>
                <p id="selectedList" class="text-[var(--verde-oscuro)] mb-2"></p>
                <p class="text-[var(--verde-oscuro)]"><strong>Total de Items:</strong> <span id="totalItems">0</span></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg">
                <form method="POST" class="space-y-6" onsubmit="return validarFormulario()">
                    <input type="hidden" id="bodyField" name="body">
                    
                    <div class="relative">
                        <input type="text" id="titulo" name="titulo" required 
                               class="custom-input peer" 
                               placeholder="Título">
                        <label for="titulo" 
                               class="absolute text-[14px] leading-[150%] text-[var(--verde-oscuro)] peer-focus:text-[var(--verde-claro)] duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                            Título
                        </label>
                    </div>

                    <div class="relative">
                        <input type="text" id="id_user" name="id_user" 
                               value="<?php echo htmlspecialchars($usuario); ?>" 
                               class="custom-input peer" 
                               readonly 
                               placeholder="Usuario">
                        <label for="id_user" 
                               class="absolute text-[14px] leading-[150%] text-[var(--verde-oscuro)] peer-focus:text-[var(--verde-claro)] duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                            Usuario
                        </label>
                    </div>

                    <button type="submit" class="w-full rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                        Registrar Entrada
                    </button>
                </form>
            </div>
        </div>

        <div id="modalHerramientas" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="cerrarModal('modalHerramientas')">×</span>
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Herramientas (En campo)</h3>
                <input type="text" id="searchHerramientas" placeholder="Buscar herramienta..." class="custom-input mb-4">
                <ul id="listaHerramientas" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_h->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay herramientas en campo disponibles.</li>
                    <?php else: ?>
                        <?php while ($fila = $resultado_h->fetch_assoc()): ?>
                    <li class="herramienta mb-2">
                        <label class="flex items-center text-[var(--verde-oscuro)]">
                            <input type="checkbox" 
                                data-id="<?php echo $fila['id_herramientas']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($fila['nombre_herramientas'], ENT_QUOTES); ?>" 
                                class="custom-checkbox mr-2 herramienta-checkbox">
                            <span><?php echo htmlspecialchars($fila['nombre_herramientas']); ?></span>
                        </label>
                    </li>
                <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="modalActivos" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="cerrarModal('modalActivos')">×</span>
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Activos (En instalación)</h3>
                <input type="text" id="searchActivos" placeholder="Buscar activo..." class="custom-input mb-4">
                <ul id="listaActivos" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_act->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay activos en instalación disponibles.</li>
                    <?php else: ?>
                        <?php while ($fila = $resultado_act->fetch_assoc()): ?>
                            <li class="activo mb-2">
                                <label class="flex items-center text-[var(--verde-oscuro)]">
                                    <input type="checkbox" onchange="agregarElemento('activos', <?php echo $fila['id_activos']; ?>, '<?php echo addslashes($fila['nombre_activos']); ?>')" class="custom-checkbox mr-2">
                                    <span><?php echo htmlspecialchars($fila['nombre_activos']); ?></span>
                                </label>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="modalConsumibles" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="cerrarModal('modalConsumibles')">×</span>
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Consumibles (En campo)</h3>
                <ul id="listaConsumibles" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_con->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay consumibles en campo disponibles.</li>
                    <?php else: ?>
                        <?php while ($fila = $resultado_con->fetch_assoc()): ?>
                            <li data-id="<?php echo $fila['id_consumibles']; ?>" class="flex flex-col p-2 border-b mb-4">
                                <span class="text-[var(--verde-oscuro)]">
                                    <?php echo htmlspecialchars($fila['nombre_consumibles']); ?> 
                                    <strong>(En campo: <?php echo $fila['cantidad_consumibles']; ?>)</strong>
                                </span>
                                <div class="flex items-center mt-2">
                                    <input type="number" id="cantidad-<?php echo $fila['id_consumibles']; ?>" 
                                        class="custom-input w-24 mr-2" 
                                        min="0" 
                                        data-max="<?php echo $fila['cantidad_consumibles']; ?>" 
                                        value="0" 
                                        oninput="validarCantidad(<?php echo $fila['id_consumibles']; ?>)">
                                </div>
                                <span id="error-<?php echo $fila['id_consumibles']; ?>" class="error-text"></span>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conexion->close(); ?>