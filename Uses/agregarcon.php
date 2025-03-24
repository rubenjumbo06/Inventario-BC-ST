<?php
include '../conexion.php';
session_start();

// Función para redirección basada en rol
function redirectBasedOnRole() {
    if (isset($_SESSION['role'])) {
        $role = $_SESSION['role'];
        $page = ($role == 'admin') ? '../pages/Admin/consumibles.php' : 
                (($role == 'user') ? '../pages/Usuario/consumibles.php' : 
                '../pages/Tecnico/consumibles.php');
        header("Location: " . $page);
        exit();
    }
    // Redirección por defecto si no hay rol
    header("Location: ../pages/consumibles.php");
    exit();
}

// Inicializar variables del formulario
$nombre_consumibles = $cantidad_consumibles = $id_empresa = $estado_consumibles = $utilidad_consumibles = $id_user = "";
$mensaje = "";

// Procesar el formulario cuando se envíe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Recoger los datos del formulario y sanitizar
        $nombre_consumibles = htmlspecialchars($_POST['nombre_consumibles']);
        $cantidad_consumibles = htmlspecialchars($_POST['cantidad_consumibles']);
        $id_empresa = intval($_POST['id_empresa']);
        $estado_consumibles = intval($_POST['estado_consumibles']); 
        $utilidad_consumibles = intval($_POST['utilidad_consumibles']); 
        $id_user = intval($_POST['id_user']);

        // Validar campos obligatorios
        if (empty($nombre_consumibles) || empty($cantidad_consumibles) || empty($id_empresa) || 
            empty($estado_consumibles) || empty($utilidad_consumibles) || empty($id_user)) {
            throw new Exception("Todos los campos son obligatorios.");
        }

        // Preparar y ejecutar la consulta
        $sql = "INSERT INTO tbl_consumibles (nombre_consumibles, cantidad_consumibles, id_empresa, estado_consumibles, utilidad_consumibles, id_user) VALUES (?, ?, ?, ?, ?, ?)";
        
        if (!$stmt = $conn->prepare($sql)) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }

        if (!$stmt->bind_param("ssiiii", $nombre_consumibles, $cantidad_consumibles, $id_empresa, $estado_consumibles, $utilidad_consumibles, $id_user)) {
            throw new Exception("Error al vincular parámetros: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }

        // Redireccionar según el rol
        redirectBasedOnRole();

    } catch (Exception $e) {
        $mensaje = $e->getMessage();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Datos</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Agregar Datos</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Consumibles
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mb-10 text-green-500"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form action="agregarcon.php" method="POST">

            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_consumibles" name="nombre_consumibles"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" value="<?php echo $nombre_consumibles; ?>" required />
                    <label for="nombre"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>

                <!-- Cantidad -->
                <div id="input" class="relative">
                    <input type="number" id="cantidad_consumibles" name="cantidad_consumibles"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Cantidad" value="<?php echo $cantidad_consumibles; ?>" required />
                    <label for="cantidad"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Cantidad
                    </label>
                </div>

                <!-- Empresa -->
                <div id="input" class="relative">
                    <select name="id_empresa" id="empresa_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]" required>
                        <option value="" disabled selected>Selecciona una Empresa</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Empresa
                    </label>
                </div>

                
                <!-- Estado -->
                <div id="input" class="relative">
                    <select name="estado_consumibles" id="estado_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]" required>
                        <option value="" disabled selected>Selecciona un Estado</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Estado
                    </label>
                </div>

                <!-- Utilidad -->
                <div id="input" class="relative">
                    <select name="utilidad_consumibles" id="utilidad_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]" required>
                        <option value="" disabled selected>Selecciona un Utilidad</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Utilidad
                    </label>
                </div>

                <!-- Usuario -->
                <div id="input" class="relative">
                    <select name="id_user" id="users_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled selected>Selecciona un Usuario</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Usuario
                    </label>
                </div>

            </div>

            <div class="sm:flex sm:flex-row-reverse flex gap-4">
                <!-- Botón Guardar -->
                <button type="submit"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-green-900 transition-all duration-300">
                    <div class="flex gap-2 items-center">Guardar</div>
                </button>

                <!-- Botón Cancelar -->
                <button type="reset"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300"
                    onclick="window.history.back();">
                    <div class="flex gap-2 items-center">Cancelar</div>
                </button>
            </div>

        </form>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        async function cargarDatos(endpoint, selectId) {
            try {
                const response = await fetch(endpoint);
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                const data = await response.json();
                
                if (!Array.isArray(data)) {
                    throw new Error("Respuesta no válida del servidor");
                }

                const select = document.getElementById(selectId);
                let placeholderText = "";
                
                switch (selectId) {
                    case "empresa_select": placeholderText = "Selecciona una Empresa"; break;
                    case "estado_select": placeholderText = "Selecciona un Estado"; break;
                    case "utilidad_select": placeholderText = "Selecciona una Utilidad"; break;
                    case "users_select": placeholderText = "Selecciona un Usuario"; break;
                    default: placeholderText = "Selecciona una opción";
                }

                select.innerHTML = `<option value="" disabled selected>${placeholderText}</option>`;

                data.forEach(item => {
                    const option = document.createElement("option");
                    option.value = item.id_empresa || item.id_estado || item.id_utilidad || item.id_user;
                    option.textContent = item.nombre || item.nombre_estado || item.nombre_utilidad || item.username;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error("Error cargando los datos:", error);
                // Opcional: Mostrar mensaje de error al usuario
            }
        }

        // Cargar datos iniciales
        Promise.all([
            cargarDatos("get_empresas.php", "empresa_select"),
            cargarDatos("get_estados.php", "estado_select"),
            cargarDatos("get_utilidades.php", "utilidad_select"),
            cargarDatos("get_users.php", "users_select")
        ]).catch(error => {
            console.error("Error al cargar datos iniciales:", error);
        });
    });
    </script>
</body>
</html>