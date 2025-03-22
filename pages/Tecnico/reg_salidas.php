<?php
session_start(); 
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'tecnico') {
    header('Location: login.php'); // Ajustado a la misma ruta que reg_entradas.php
    exit;
}
$usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario no definido'; 
$role = $_SESSION['role']; 

include '../../conexion.php'; // Ajustado a dos niveles arriba como reg_entradas.php
$conexion = $conn;

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error()); // Añadida verificación como en reg_entradas.php
}

// Consultar herramientas en almacén
$sql_h = "SELECT id_herramientas, nombre_herramientas, ubicacion_herramientas FROM tbl_herramientas WHERE ubicacion_herramientas = 'En almacen'";
$resultado_h = $conexion->query($sql_h);

// Consultar activos en almacén
$sql_act = "SELECT id_activos, nombre_activos, ubicacion_activos FROM tbl_activos WHERE ubicacion_activos = 'En almacen'";
$resultado_act = $conexion->query($sql_act);

// Consultar consumibles
$sql_con = "SELECT id_consumibles, nombre_consumibles, cantidad_consumibles FROM tbl_consumibles";
$resultado_con = $conexion->query($sql_con);

// Obtener lista de usuarios
$usuarios = $conn->query("SELECT id_user, nombre FROM tbl_users");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $destino = $_POST['destino'];
    $id_user = $_POST['id_user'];
    $selectedItems = json_decode($_POST['body'], true);
    $totalItems = 0;
    $body = "";

    // Procesar herramientas
    if (!empty($selectedItems['herramientas'])) {
        $herramientas = array_map(function ($nombre) {
            return $nombre;
        }, $selectedItems['herramientas']);
        $body .= "Herramientas: (" . implode(", ", $herramientas) . "), ";
        $totalItems += count($selectedItems['herramientas']);

        foreach ($selectedItems['herramientas'] as $id => $nombre) {
            $stmt = $conn->prepare("UPDATE tbl_herramientas SET ubicacion_herramientas = 'En campo' WHERE id_herramientas = ?");
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
            $stmt = $conn->prepare("UPDATE tbl_activos SET ubicacion_activos = 'En instalacion' WHERE id_activos = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Procesar consumibles
    if (!empty($selectedItems['consumibles'])) {
        $consumibles = [];
        foreach ($selectedItems['consumibles'] as $id => $data) {
            $consumibles[] = $data['nombre'] . "(" . $data['cantidad'] . ")";
            $totalItems += $data['cantidad'];

            $cantidadSeleccionada = $data['cantidad'];
            $stmt = $conn->prepare("UPDATE tbl_consumibles SET cantidad_consumibles = cantidad_consumibles - ? WHERE id_consumibles = ?");
            $stmt->bind_param("ii", $cantidadSeleccionada, $id);
            $stmt->execute();
            $stmt->close();
        }
        $body .= "Consumibles: [" . implode(", ", $consumibles) . "]";
    }

    // Guardar en tbl_reg_salidas
    $stmt = $conn->prepare("INSERT INTO tbl_reg_salidas (titulo, destino, id_user, body, items) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisi", $titulo, $destino, $id_user, $body, $totalItems);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Salida registrada exitosamente'); window.location='reg_salidas.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Salidas</title>
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
                    container[id] = { nombre, cantidad };
                } else {
                    delete container[id];
                }
            } else {
                if (container[id]) {
                    delete container[id];
                } else {
                    container[id] = nombre;
                }
            }
            actualizarResumen();
        };

        window.validarCantidad = function (id) {
            let cantidadElemento = document.getElementById(`cantidad-${id}`);
            let errorElemento = document.getElementById(`error-${id}`);
            
            if (!cantidadElemento || !errorElemento) return;
            
            let cantidadDisponible = parseInt(cantidadElemento.max) || 0;
            let cantidadIngresada = parseInt(cantidadElemento.value) || 0;

            if (cantidadIngresada < 0) {
                cantidadIngresada = 0;
                errorElemento.textContent = "La cantidad no puede ser negativa";
            } else if (cantidadIngresada > cantidadDisponible) {
                cantidadIngresada = cantidadDisponible;
                errorElemento.textContent = `No puedes seleccionar más de ${cantidadDisponible}`;
            } else {
                errorElemento.textContent = "";
            }
            cantidadElemento.value = cantidadIngresada;

            let nombreElemento = cantidadElemento.closest('li')?.querySelector('span');
            let nombre = nombreElemento ? nombreElemento.textContent.split('(')[0].trim() : 'Desconocido';

            if (cantidadIngresada > 0) {
                selectedItems.consumibles[id] = { nombre, cantidad: cantidadIngresada };
            } else {
                delete selectedItems.consumibles[id];
            }
            actualizarResumen();
        };

        function actualizarResumen() {
            let resumen = [];
            let totalItems = 0;

            // Herramientas
            if (Object.keys(selectedItems.herramientas).length > 0) {
                let herramientas = Object.values(selectedItems.herramientas);
                resumen.push(`Herramientas: (${herramientas.join(", ")})`);
                totalItems += herramientas.length;
            }

            // Activos
            if (Object.keys(selectedItems.activos).length > 0) {
                let activos = Object.values(selectedItems.activos);
                resumen.push(`Activos: (${activos.join(", ")})`);
                totalItems += activos.length;
            }

            // Consumibles
            if (Object.keys(selectedItems.consumibles).length > 0) {
                let consumibles = Object.values(selectedItems.consumibles).map(item => `${item.nombre}(${item.cantidad})`);
                resumen.push(`Consumibles: [${consumibles.join(", ")}]`);
                totalItems += Object.values(selectedItems.consumibles).reduce((sum, item) => sum + item.cantidad, 0);
            }

            // Actualizar el DOM
            document.getElementById('selectedList').innerHTML = resumen.map(item => `<li class="bg-[var(--beige)] p-2 rounded-md mb-2 text-[var(--verde-oscuro)]">${item}</li>`).join('');
            document.getElementById('bodyField').value = JSON.stringify(selectedItems);
            document.getElementById('totalItems').textContent = totalItems;
        }

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
            const fechaHoraElemento = document.getElementById("fechaHora");
            if (fechaHoraElemento) {
                fechaHoraElemento.textContent = `Fecha/Hora Ingreso: ${fechaHoraFormateada}`;
            }
        }
        actualizarFechaHora();
        setInterval(actualizarFechaHora, 1000);
    });
    </script>
</head>
<body class="min-h-screen p-6">
    <?php include '../header.php'; ?> <!-- Ajustado como reg_entradas.php -->
    <?php include 'sidebartec.php'; ?> <!-- Añadido como en reg_entradas.php -->

    <div class="main-content"> <!-- Añadido contenedor como en reg_entradas.php -->
        <div class="flex justify-between items-center mt-4 px-4 animate-fade-in">
            <p class="text-white text-sm sm:text-lg text-shadow"> <!-- Color ajustado como en reg_entradas.php -->
                <strong>User:</strong> <?php echo htmlspecialchars($usuario); ?> 
                <span id="user-role"><?php echo !empty($role) ? "($role)" : ''; ?></span>
            </p>
            <p id="fechaHora" class="text-white text-sm sm:text-lg text-shadow">
                <strong>Fecha/Hora Ingreso:</strong> Cargando...
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6 animate-fade-in">
            <!-- Herramientas -->
            <div class="col-span-1 bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Herramientas (En almacén)</h3>
                <input type="text" id="searchHerramientas" placeholder="Buscar herramienta..." class="custom-input mb-4">
                <ul id="listaHerramientas" class="max-h-64 overflow-y-auto">
                    <?php while ($fila = $resultado_h->fetch_assoc()): ?>
                        <?php if ($fila['ubicacion_herramientas'] == 'En almacen'): ?>
                            <li class="herramienta mb-2">
                                <label class="flex items-center text-[var(--verde-oscuro)]">
                                    <input type="checkbox" onchange="agregarElemento('herramientas', <?php echo $fila['id_herramientas']; ?>, '<?php echo addslashes($fila['nombre_herramientas']); ?>')" class="custom-checkbox mr-2">
                                    <span><?php echo htmlspecialchars($fila['nombre_herramientas']); ?></span>
                                </label>
                            </li>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Activos -->
            <div class="col-span-1 bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Activos (En almacén)</h3>
                <input type="text" id="searchActivos" placeholder="Buscar activo..." class="custom-input mb-4">
                <ul id="listaActivos" class="max-h-64 overflow-y-auto">
                    <?php while ($fila = $resultado_act->fetch_assoc()): ?>
                        <?php if ($fila['ubicacion_activos'] == 'En almacen'): ?>
                            <li class="activo mb-2">
                                <label class="flex items-center text-[var(--verde-oscuro)]">
                                    <input type="checkbox" onchange="agregarElemento('activos', <?php echo $fila['id_activos']; ?>, '<?php echo addslashes($fila['nombre_activos']); ?>')" class="custom-checkbox mr-2">
                                    <span><?php echo htmlspecialchars($fila['nombre_activos']); ?></span>
                                </label>
                            </li>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Consumibles -->
            <div class="col-span-1 bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Consumibles</h3>
                <ul id="listaConsumibles" class="max-h-64 overflow-y-auto">
                    <?php while ($fila = $resultado_con->fetch_assoc()): ?>
                        <li data-id="<?php echo $fila['id_consumibles']; ?>" data-cantidad="<?php echo $fila['cantidad_consumibles']; ?>" class="flex flex-col p-2 border-b mb-4">
                            <span class="text-[var(--verde-oscuro)]">
                                <?php echo htmlspecialchars($fila['nombre_consumibles']); ?> 
                                <strong>(Disponibles: <?php echo $fila['cantidad_consumibles']; ?>)</strong>
                            </span>
                            <div class="flex items-center mt-2">
                                <input type="number" id="cantidad-<?php echo $fila['id_consumibles']; ?>" 
                                    class="custom-input w-24 mr-2" 
                                    min="0" max="<?php echo $fila['cantidad_consumibles']; ?>" 
                                    value="0" 
                                    oninput="validarCantidad(<?php echo $fila['id_consumibles']; ?>)">
                            </div>
                            <span id="error-<?php echo $fila['id_consumibles']; ?>" class="error-text"></span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Resumen de selección -->
            <div class="col-span-1 bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Resumen de Selección</h3>
                <ul id="selectedList" class="mb-4"></ul>
                <p class="text-[var(--verde-oscuro)]"><strong>Total de Items:</strong> <span id="totalItems">0</span></p>
                <form method="POST" class="space-y-6 mt-4">
                    <input type="hidden" id="bodyField" name="body">
                    
                    <div id="input" class="relative">
                        <input type="text" id="titulo" name="titulo" required class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]" placeholder="Título" required>
                        <label for="titulo" 
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Título
                    </label>
                    </div>
                    
                    <div id="input" class="relative">
                        <input type="text" id="destino" name="destino" required class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]" placeholder="Destino" required>
                        <label for="destino" 
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Destino</label>
                    </div>

                    <div class="relative">
                        <select id="id_user" name="id_user" required class="custom-select peer">
                            <option value="">Seleccione un usuario</option>
                            <?php while ($row = $usuarios->fetch_assoc()): ?>
                                <option value="<?php echo $row['id_user']; ?>"><?php echo $row['nombre']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <label for="id_user" class="absolute text-sm text-[var(--verde-oscuro)] duration-300 transform -translate-y-6 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:text-[var(--verde-claro)] peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-0 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-6 left-1">Usuario</label>
                    </div>
                    <button type="submit" class="w-full rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                        Registrar Salida
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conexion->close(); ?>