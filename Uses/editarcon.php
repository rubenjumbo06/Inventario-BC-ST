<?php
require_once("../conexion.php");
session_start();

// Desactivar cualquier salida automática
ob_start();

try {
    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Validar ID del consumible
    if (!isset($_GET['id_consumibles'])) {
        throw new Exception("ID de consumible no proporcionado");
    }
    
    $id_consumibles = filter_var($_GET['id_consumibles'], FILTER_VALIDATE_INT);
    if ($id_consumibles === false) {
        throw new Exception("ID de consumible inválido");
    }

    // Obtener datos del consumible
    $sql = "SELECT * FROM tbl_consumibles WHERE id_consumibles = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_consumibles);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $consumible = $result->fetch_assoc();

    if (!$consumible) {
        throw new Exception("Consumible no encontrado");
    }

    // Pasar los valores a la vista
    $id_empresa_selected = $consumible['id_empresa'];
    $estado_consumibles_selected = $consumible['estado_consumibles'];
    $utilidad_consumibles_selected = $consumible['utilidad_consumibles'];
    $id_user_selected = $consumible['id_user'];

    // Procesar formulario POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validar y sanitizar datos
            $nombre_consumibles = isset($_POST['nombre_consumibles']) ? trim($_POST['nombre_consumibles']) : '';
            $cantidad_consumibles = isset($_POST['cantidad_consumibles']) ? intval($_POST['cantidad_consumibles']) : 0;
            $id_empresa = isset($_POST['id_empresa']) ? intval($_POST['id_empresa']) : 0;
            $estado_consumibles = isset($_POST['estado_consumibles']) ? intval($_POST['estado_consumibles']) : 0;
            $utilidad_consumibles = isset($_POST['utilidad_consumibles']) ? trim($_POST['utilidad_consumibles']) : '';
            $id_user = isset($_POST['id_user']) ? intval($_POST['id_user']) : 0;

            // Validaciones básicas
            if (empty($nombre_consumibles)) {
                throw new Exception("El nombre del consumible es requerido");
            }

            // Construir consulta SQL dinámica
            $sql = "UPDATE tbl_consumibles SET ";
            $params = [];
            $types = "";
            $updates = [];

            if (!empty($nombre_consumibles)) {
                $updates[] = "nombre_consumibles = ?";
                $params[] = $nombre_consumibles;
                $types .= "s";
            }
            
            if ($cantidad_consumibles > 0) {
                $updates[] = "cantidad_consumibles = ?";
                $params[] = $cantidad_consumibles;
                $types .= "i";
            }
            
            if ($id_empresa > 0) {
                $updates[] = "id_empresa = ?";
                $params[] = $id_empresa;
                $types .= "i";
            }
            
            if ($estado_consumibles > 0) {
                $updates[] = "estado_consumibles = ?";
                $params[] = $estado_consumibles;
                $types .= "i";
            }
            
            if (!empty($utilidad_consumibles)) {
                $updates[] = "utilidad_consumibles = ?";
                $params[] = $utilidad_consumibles;
                $types .= "s";
            }
            
            if ($id_user > 0) {
                $updates[] = "id_user = ?";
                $params[] = $id_user;
                $types .= "i";
            }

            // Si no hay campos para actualizar
            if (empty($updates)) {
                $_SESSION['message'] = 'No se realizaron cambios';
                // Limpiar buffer y redirigir
                ob_end_clean();
                redirectBasedOnRole();
            }

            $sql .= implode(", ", $updates);
            $sql .= " WHERE id_consumibles = ?";
            $params[] = $id_consumibles;
            $types .= "i";

            // Ejecutar consulta
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de actualización: " . $conn->error);
            }
            
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Consumible actualizado correctamente';
                // Limpiar buffer y redirigir
                ob_end_clean();
                redirectBasedOnRole();
            } else {
                throw new Exception("Error al actualizar el consumible: " . $stmt->error);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            // Limpiar buffer y redirigir
            ob_end_clean();
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    // Limpiar buffer y redirigir
    ob_end_clean();
    header("Location: ../pages/error.php");
    exit();
}

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

// Limpiar buffer antes de mostrar el HTML
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Datos</title>

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

        <form method="POST">

            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_consumibles" name="nombre_consumibles" value="<?= htmlspecialchars($consumible['nombre_consumibles']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre"/>
                    <label for="nombre"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>

                <!-- Cantidad -->
                <div id="input" class="relative">
                    <input type="number" id="cantidad_consumibles" name="cantidad_consumibles" value="<?= htmlspecialchars($consumible['cantidad_consumibles']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Cantidad"/>
                    <label for="cantidad"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Cantidad
                    </label>
                </div>
                
                <!-- Empresa -->
                <div id="input" class="relative">
                    <select name="id_empresa" id="empresa_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
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
                    <select name="estado_consumibles" id="estado_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
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
                    <select name="utilidad_consumibles" id="utilidad_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled selected>Selecciona una Utilidad</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Utilidad
                    </label>
                </div>

                <!-- User -->
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
                    <div class="flex gap-2 items-center">Actualizar</div>
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
    function cargarDatos(endpoint, selectId, selectedValue) {
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data)) {
                    console.error("Error: Respuesta no válida", data);
                    return;
                }
                let select = document.getElementById(selectId);
                let placeholderText = "";
                switch (selectId) {
                    case "empresa_select":
                        placeholderText = "Selecciona una Empresa";
                        break;
                    case "estado_select":
                        placeholderText = "Selecciona un Estado";
                        break;
                    case "utilidad_select":
                        placeholderText = "Selecciona una Utilidad";
                        break;
                    case "users_select":
                        placeholderText = "Selecciona un Usuario";
                        break;
                    default:
                        placeholderText = "Selecciona una opción";
                }
                // Limpiar y agregar el texto predeterminado
                select.innerHTML = `<option value="" disabled selected>${placeholderText}</option>`;

                data.forEach(item => {
                    let option = document.createElement("option");
                    option.value = item.id_empresa || item.id_estado || item.id_utilidad || item.id_user;
                    option.textContent = item.nombre || item.nombre_estado || item.nombre_utilidad || item.username;
                    if (option.value == selectedValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            })
            .catch(error => console.error("Error cargando los datos:", error));
    }

    // Obtener los valores seleccionados desde PHP
    let id_empresa_selected = "<?= $id_empresa_selected ?>";
    let estado_consumibles_selected = "<?= $estado_consumibles_selected ?>";
    let utilidad_consumibles_selected = "<?= $utilidad_consumibles_selected ?>";
    let id_user_selected = "<?= $id_user_selected ?>";

    // Cargar los datos y seleccionar el valor correcto
    cargarDatos("get_empresas.php", "empresa_select", id_empresa_selected);
    cargarDatos("get_estados.php", "estado_select", estado_consumibles_selected);
    cargarDatos("get_users.php", "users_select", id_user_selected);
    cargarDatos("get_utilidades.php", "utilidad_select", utilidad_consumibles_selected);
});
    </script>
</body>
</html>