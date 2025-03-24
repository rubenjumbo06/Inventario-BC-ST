<?php
require_once("../conexion.php");
session_start();

// Iniciar buffer de salida
ob_start();

try {
    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Validar ID de la empresa
    if (!isset($_GET['id_empresa']) || !is_numeric($_GET['id_empresa'])) {
        throw new Exception("ID de empresa no proporcionado o inválido");
    }

    $id_empresa = intval($_GET['id_empresa']);
    
    // Obtener datos de la empresa
    $sql = "SELECT * FROM tbl_empresa WHERE id_empresa = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_empresa);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $empresa = $result->fetch_assoc();

    if (!$empresa) {
        throw new Exception("Empresa no encontrada");
    }

    // Inicializar variables para la vista
    $nombre = $empresa['nombre'] ?? '';
    $ruc = $empresa['ruc'] ?? '';
    $servicio_empresa = $empresa['servicio_empresa'] ?? '';

    // Procesar formulario POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validar y sanitizar datos
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $ruc = isset($_POST['ruc']) ? trim($_POST['ruc']) : '';
            $servicio_empresa = isset($_POST['servicio_empresa']) ? trim($_POST['servicio_empresa']) : '';

            // Validaciones básicas
            if (empty($nombre)) {
                throw new Exception("El nombre de la empresa es requerido");
            }

            if (empty($ruc)) {
                throw new Exception("El RUC de la empresa es requerido");
            }

            // Construir consulta SQL dinámica
            $sql = "UPDATE tbl_empresa SET ";
            $params = [];
            $types = "";
            $updates = [];

            if (!empty($nombre)) {
                $updates[] = "nombre = ?";
                $params[] = $nombre;
                $types .= "s";
            }
            
            if (!empty($ruc)) {
                $updates[] = "ruc = ?";
                $params[] = $ruc;
                $types .= "s";
            }
            
            if (!empty($servicio_empresa)) {
                $updates[] = "servicio_empresa = ?";
                $params[] = $servicio_empresa;
                $types .= "s";
            }

            // Si no hay campos para actualizar
            if (empty($updates)) {
                $_SESSION['message'] = 'No se realizaron cambios';
                // Limpiar buffer y redirigir
                ob_end_clean();
                header("Location: ../pages/Admin/empresa.php");
                exit();
            }

            $sql .= implode(", ", $updates);
            $sql .= " WHERE id_empresa = ?";
            $params[] = $id_empresa;
            $types .= "i";

            // Ejecutar consulta
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de actualización: " . $conn->error);
            }
            
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Empresa actualizada correctamente';
                // Limpiar buffer y redirigir
                ob_end_clean();
                header("Location: ../pages/Admin/empresa.php");
                exit();
            } else {
                throw new Exception("Error al actualizar la empresa: " . $stmt->error);
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
                        <div class="text-base text-[var(--verde-oscuro)]">Editar Datos</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Empresa
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" required/>
                    <label for="nombre"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>

                <!-- RUC -->
                <div id="input" class="relative">
                    <input type="number" id="ruc" name="ruc" value="<?= htmlspecialchars($ruc) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="RUC" required/>
                    <label for="ruc"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        RUC
                    </label>
                </div>
                
                <!-- Servicio -->
                <div id="input" class="relative">
                    <textarea id="servicio_empresa" name="servicio_empresa"
                        class="block w-full text-sm px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto resize-none"
                        placeholder="Servicio" required
                        oninput="autoResize(this)"><?= htmlspecialchars($servicio_empresa) ?></textarea>
                    <label for="servicio_empresa"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Servicio
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
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    // Aplicar auto-resize al cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        const textarea = document.getElementById('servicio_empresa');
        autoResize(textarea);
    });
    </script>
</body>
</html>