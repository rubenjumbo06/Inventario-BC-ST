<?php
include '../conexion.php';
session_start();

// Verificar la sesión del administrador
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Inicializar variables del formulario
$nombre_herramientas = $cantidad_herramientas = $id_empresa = $estado_herramientas = $utilidad_herramientas = $ubicacion_herramientas = "";
$mensaje = "";

// Procesar el formulario cuando se envíe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar nombre (solo letras y espacios)
    if (isset($_POST['nombre_herramientas']) && !empty(trim($_POST['nombre_herramientas']))) {
        if (!preg_match('/^[A-Za-zÁ-Úá-úñÑ\s]+$/', $_POST['nombre_herramientas'])) {
            $mensaje = "El nombre solo puede contener letras y espacios";
        } else {
            $nombre_herramientas = htmlspecialchars(trim($_POST['nombre_herramientas']));
        }
    } else {
        $mensaje = "El nombre es obligatorio";
    }

    // Solo continuar si el nombre es válido
    if (empty($mensaje)) {
        // Validar y asignar los otros campos
        $cantidad_herramientas = isset($_POST['cantidad_herramientas']) && $_POST['cantidad_herramientas'] !== '' ? intval($_POST['cantidad_herramientas']) : null;
        $id_empresa = isset($_POST['id_empresa']) && $_POST['id_empresa'] !== '' ? intval($_POST['id_empresa']) : null;
        $estado_herramientas = isset($_POST['estado_herramientas']) && $_POST['estado_herramientas'] !== '' ? intval($_POST['estado_herramientas']) : null;
        $utilidad_herramientas = isset($_POST['utilidad_herramientas']) && $_POST['utilidad_herramientas'] !== '' ? intval($_POST['utilidad_herramientas']) : null;
        $ubicacion_herramientas = isset($_POST['ubicacion_herramientas']) && $_POST['ubicacion_herramientas'] !== '' ? htmlspecialchars($_POST['ubicacion_herramientas']) : null;

        // Verificar que todos los campos tengan valores válidos
        if (!empty($nombre_herramientas) && 
            $cantidad_herramientas !== null && $cantidad_herramientas > 0 &&
            $id_empresa !== null && $id_empresa > 0 &&
            $estado_herramientas !== null && $estado_herramientas > 0 &&
            $utilidad_herramientas !== null && $utilidad_herramientas > 0 &&
            !empty($ubicacion_herramientas)) {

            $sql = "INSERT INTO tbl_herramientas (nombre_herramientas, cantidad_herramientas, id_empresa, estado_herramientas, utilidad_herramientas, ubicacion_herramientas) VALUES (?, ?, ?, ?, ?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("siiiss", $nombre_herramientas, $cantidad_herramientas, $id_empresa, $estado_herramientas, $utilidad_herramientas, $ubicacion_herramientas);
                
                if ($stmt->execute()) {
                    header("Location: ../pages/Admin/herramientas.php?action=added&table=herramientas");
                    exit();
                } else {
                    $mensaje = "Error al agregar la herramienta: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensaje = "Error al preparar la consulta: " . $conn->error;
            }
        } else {
            $mensaje = "Todos los campos son obligatorios y deben ser válidos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Herramienta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Agregar Herramienta</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Herramientas
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mb-10 text-red-500"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form id="formHerramienta" action="" method="POST">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div class="relative">
                    <input type="text" id="nombre_herramientas" name="nombre_herramientas"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500"
                        placeholder="Nombre" 
                        value="<?php echo htmlspecialchars($nombre_herramientas); ?>" 
                        required pattern="[A-Za-zÁ-Úá-úñÑ\s]+"
                        title="Solo se permiten letras y espacios" oninput="validarSoloLetras(this)">
                    <label for="nombre_herramientas"
                        class="absolute text-[14px] text-primary duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2">
                        Nombre
                    </label>
                </div>

                <!-- Cantidad -->
                <div class="relative">
                    <input type="number" id="cantidad_herramientas" name="cantidad_herramientas"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500"
                        min="1" max="9999" step="1"
                        placeholder="Cantidad" value="<?php echo htmlspecialchars($cantidad_herramientas); ?>" 
                        required oninput="validarCantidad(this)">
                    <label for="cantidad_herramientas"
                        class="absolute text-[14px] text-primary duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2">
                        Cantidad
                    </label>
                </div>

                <!-- Empresa -->
                <div class="relative">
                    <select name="id_empresa" id="empresa_select"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500" required>
                        <option value="" disabled selected>Selecciona una Empresa</option>
                    </select>
                    <label for="empresa_select"
                        class="absolute text-[14px] text-primary duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2">
                        Empresa
                    </label>
                </div>

                <!-- Estado -->
                <div class="relative">
                    <select name="estado_herramientas" id="estado_select"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500" required>
                        <option value="" disabled selected>Selecciona un Estado</option>
                    </select>
                    <label for="estado_select"
                        class="absolute text-[14px] text-primary duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2">
                        Estado
                    </label>
                </div>

                <!-- Utilidad -->
                <div class="relative">
                    <select name="utilidad_herramientas" id="utilidad_select"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500" required>
                        <option value="" disabled selected>Selecciona una Utilidad</option>
                    </select>
                    <label for="utilidad_select"
                        class="absolute text-[14px] text-primary duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2">
                        Utilidad
                    </label>
                </div>

                <!-- Ubicación -->
                <div class="relative">
                    <select name="ubicacion_herramientas" id="ubicacion_select"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500" required>
                        <option value="" disabled selected>Selecciona una Ubicación</option>
                        <option value="1">En Campo</option>
                        <option value="2">En Almacén</option>
                    </select>
                    <label for="ubicacion_select"
                        class="absolute text-[14px] text-primary duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2">
                        Ubicación
                    </label>
                </div>
            </div>

            <div class="sm:flex sm:flex-row-reverse flex gap-4">
                <button type="submit"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-green-900 transition-all duration-300">
                    Guardar
                </button>
                <button type="button"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300"
                    onclick="window.history.back();">
                    Cancelar
                </button>
            </div>
        </form>
    </div>

    <script>
        // Validación para solo letras (incluye acentos y ñ)
        function validarSoloLetras(input) {
            const valorOriginal = input.value;
            const valorLimpio = valorOriginal.replace(/[^A-Za-zÁ-Úá-úñÑ\s]/g, '');
            if (valorOriginal !== valorLimpio) {
                input.value = valorLimpio;
            }
            input.value = input.value.toLowerCase().replace(/\b\w/g, letra => letra.toUpperCase());
        }

        // Validación del campo cantidad
        function validarCantidad(input) {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (input.value > 9999) input.value = 9999;
            if (input.value < 1 && input.value !== '') input.value = 1;
        }

        // Cargar datos dinámicamente
        document.addEventListener("DOMContentLoaded", function() {
            async function cargarDatos(endpoint, selectId, placeholder) {
                try {
                    const response = await fetch(endpoint);
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    const data = await response.json();
                    
                    const select = document.getElementById(selectId);
                    select.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
                    
                    data.forEach(item => {
                        const option = document.createElement("option");
                        if (selectId === "empresa_select") {
                            option.value = item.id_empresa;
                            option.textContent = item.nombre;
                        } else if (selectId === "estado_select") {
                            option.value = item.id_estado;
                            option.textContent = item.nombre_estado;
                        } else if (selectId === "utilidad_select") {
                            option.value = item.id_utilidad;
                            option.textContent = item.nombre_utilidad;
                        }
                        select.appendChild(option);
                    });
                } catch (error) {
                    console.error(`Error cargando ${selectId}:`, error);
                    document.getElementById(selectId).innerHTML = 
                        `<option value="" disabled selected>Error cargando datos</option>`;
                }
            }

            cargarDatos("../Uses/get_empresas.php", "empresa_select", "Selecciona una Empresa");
            cargarDatos("../Uses/get_estados.php", "estado_select", "Selecciona un Estado");
            cargarDatos("../Uses/get_utilidades.php", "utilidad_select", "Selecciona una Utilidad");
        });

        // Validación del formulario completo
        document.getElementById('formHerramienta').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre_herramientas');
            if (!/^[A-Za-zÁ-Úá-úñÑ\s]+$/.test(nombre.value)) {
                alert('El nombre solo puede contener letras y espacios');
                nombre.focus();
                e.preventDefault();
                return;
            }
            
            const cantidad = document.getElementById('cantidad_herramientas');
            if (cantidad.value <= 0 || cantidad.value > 9999 || isNaN(cantidad.value)) {
                alert('La cantidad debe ser un número entre 1 y 9999');
                cantidad.focus();
                e.preventDefault();
            }
        });
    </script>
</body>
</html>