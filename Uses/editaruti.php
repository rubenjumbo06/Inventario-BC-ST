<?php
require_once("../conexion.php");
session_start();

// Iniciar buffer de salida
ob_start();

try {
    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $conn->connect_error);
    }

    // Validar ID de la utilidad
    if (!isset($_GET['id_utilidad']) || !is_numeric($_GET['id_utilidad'])) {
        throw new Exception("ID de utilidad no proporcionado o inválido");
    }

    $id_utilidad = intval($_GET['id_utilidad']);
    
    // Obtener datos de la utilidad
    $sql = "SELECT * FROM tbl_utilidad WHERE id_utilidad = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_utilidad);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $utilidad = $result->fetch_assoc();

    if (!$utilidad) {
        throw new Exception("Utilidad no encontrada");
    }

    // Inicializar variables para la vista
    $nombre_utilidad = $utilidad['nombre_utilidad'] ?? '';
    $descripcion = $utilidad['descripcion'] ?? '';

    // Procesar formulario POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validar y sanitizar datos
            $nombre_utilidad = isset($_POST['nombre_utilidad']) ? trim($_POST['nombre_utilidad']) : '';
            $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

            // Validaciones básicas
            if (empty($nombre_utilidad)) {
                throw new Exception("El nombre de la utilidad es requerido");
            }
            if (!preg_match("/^[a-zA-Z0-9\s]+$/", $nombre_utilidad)) {
                throw new Exception("El nombre solo puede contener letras, números y espacios");
            }
            if (!empty($descripcion) && !preg_match("/^[a-zA-Z0-9\s]+$/", $descripcion)) {
                throw new Exception("La descripción solo puede contener letras, números y espacios");
            }

            // Construir consulta SQL dinámica
            $sql = "UPDATE tbl_utilidad SET ";
            $params = [];
            $types = "";
            $updates = [];

            if (!empty($nombre_utilidad)) {
                $updates[] = "nombre_utilidad = ?";
                $params[] = $nombre_utilidad;
                $types .= "s";
            }
            
            if (!empty($descripcion)) {
                $updates[] = "descripcion = ?";
                $params[] = $descripcion;
                $types .= "s";
            }

            $sql .= implode(", ", $updates);
            $sql .= " WHERE id_utilidad = ?";
            $params[] = $id_utilidad;
            $types .= "i";

            // Ejecutar consulta
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de actualización: " . $conn->error);
            }
            
            $stmt->bind_param($types, ...$params);
            
            // Reemplazar ambas redirecciones con este código consistente:
            if ($stmt->execute()) {
                ob_end_clean();
                header("Location: ../pages/Admin/utilidad.php?action=updated&table=utilidad");
                exit();
            } else {
                throw new Exception("Error al actualizar la utilidad: " . $stmt->error);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            ob_end_clean();
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    ob_end_clean();
    header("Location: ../pages/error.php");
    exit();
}

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
                        Editando tabla: Utilidad
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_utilidad" name="nombre_utilidad" value="<?= htmlspecialchars($nombre_utilidad) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" required
                        pattern="[A-Za-z0-9\s]+"
                        title="Solo se permiten letras, números y espacios"/>
                    <label for="nombre_utilidad"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>

                <!-- Descripción -->
                <div id="input" class="relative">
                    <textarea id="descripcion" name="descripcion"
                        class="block w-full text-sm px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto resize-none"
                        placeholder="Descripción"
                        oninput="autoResize(this); this.value = this.value.replace(/[^a-zA-Z0-9\s]/g, '');"><?= htmlspecialchars($descripcion) ?></textarea>
                    <label for="descripcion"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Descripción
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
    // Función para ajustar la altura del textarea
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(textarea.scrollHeight, 50) + 'px'; // Altura mínima de 50px
    }

    // Aplicar validaciones y auto-resize al cargar la página
    document.addEventListener("DOMContentLoaded", function () {
        // Configuración para nombre_utilidad
        const nombreInput = document.getElementById('nombre_utilidad');
        nombreInput.addEventListener("input", function (e) {
            this.value = this.value.replace(/[^a-zA-Z0-9\s]/g, ''); // Solo letras, números y espacios
        });

        // Configuración para descripción
        const textarea = document.getElementById('descripcion');
        if (textarea) {
            autoResize(textarea); // Ajuste inicial
            if (textarea.value) {
                textarea.dispatchEvent(new Event('input')); // Ajustar label si hay contenido
            }
        }
    });
    </script>
</body>
</html>