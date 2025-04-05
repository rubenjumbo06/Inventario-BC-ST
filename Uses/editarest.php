<?php
require_once("../conexion.php");
session_start();

// Verificar la sesión y permitir solo admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener ID del estado
if (!isset($_GET['id_estado']) || !is_numeric($_GET['id_estado'])) {
    die("ID inválido.");
}

$id_estado = intval($_GET['id_estado']);

try {
    // Obtener datos del estado
    $sql = "SELECT * FROM tbl_estados WHERE id_estado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_estado);
    $stmt->execute();
    $result = $stmt->get_result();
    $estado = $result->fetch_assoc();

    if (!$estado) {
        throw new Exception("Estado no encontrado.");
    }
    // Inicializar $descripcion con el valor de la base de datos
    $descripcion = $estado['descripcion'] ?? '';

    // Procesar el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener y sanitizar valores del formulario
        $nombre_estado = trim($_POST['nombre_estado'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        // Validaciones en el backend
        if (!empty($nombre_estado) && !preg_match("/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u", $nombre_estado)) {
            throw new Exception("El nombre del estado solo puede contener letras (incluyendo ñ y acentos) y espacios.");
        }
        if (!empty($descripcion) && !preg_match("/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/u", $descripcion)) {
            throw new Exception("La descripción solo puede contener letras (incluyendo ñ y acentos) y espacios.");
        }

        // Construir la consulta SQL dinámicamente
        $sql = "UPDATE tbl_estados SET ";
        $params = [];
        $types = "";

        if (!empty($nombre_estado)) {
            $sql .= "nombre_estado=?, ";
            $params[] = $nombre_estado;
            $types .= "s";
        }
        if (!empty($descripcion)) {
            $sql .= "descripcion=?, ";
            $params[] = $descripcion;
            $types .= "s";
        }

        // Si no hay campos para actualizar
        if (empty($params)) {
            header("Location: ../pages/Admin/estados.php");
            exit();
        }

        // Eliminar la última coma y espacio
        $sql = rtrim($sql, ", ");

        // Agregar la condición WHERE
        $sql .= " WHERE id_estado=?";
        $params[] = $id_estado;
        $types .= "i";

        // Preparar y ejecutar la consulta
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            // Redirigir con parámetros para la notificación
            header("Location: ../pages/Admin/estados.php?action=updated&table=estados");
            exit();
        } else {
            throw new Exception("Error al actualizar el estado: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    $mensaje_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Editar Estado</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Estados
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <?php echo htmlspecialchars($mensaje_error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid grid-cols-1 gap-6 mb-10">
                <!-- Nombre del Estado -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_estado" name="nombre_estado" value="<?= htmlspecialchars($estado['nombre_estado']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre del Estado"
                        pattern="[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+"
                        title="Solo se permiten letras (incluyendo ñ y acentos) y espacios"
                        oninput="validarTexto(this)"
                        required/>
                    <label for="nombre_estado"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Nombre del Estado
                    </label>
                </div>

                <!-- Descripción -->
                <div id="input" class="relative">
                    <textarea id="descripcion" name="descripcion"
                        class="block w-full text-sm px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto resize-none"
                        placeholder="Descripción"
                        oninput="autoResize(this); validarTexto(this)"
                        required><?php echo htmlspecialchars($descripcion); ?></textarea>
                    <label for="descripcion"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
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
                <!-- Botón Cancelar - Versión corregida -->
                <button type="button"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300"
                    onclick="cancelarFormulario()">
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

    // Validación para campos de texto (solo letras con acentos y espacios)
    function validarTexto(input) {
        // Permite letras (incluyendo ñ y acentos) y espacios
        input.value = input.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚ]/g, '');
    }
    function cancelarFormulario() {
        window.location.href = '../pages/Admin/estados.php'; // Redirige directamente
    }

    // Aplicar validaciones y auto-resize al cargar la página
    document.addEventListener("DOMContentLoaded", function () {
        // Configuración para nombre_estado
        const nombreInput = document.getElementById('nombre_estado');
        nombreInput.addEventListener("input", function() {
            validarTexto(this);
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