<?php
require_once("../conexion.php");

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (isset($_GET['id_estado']) && is_numeric($_GET['id_estado'])) {
    $id_estado = intval($_GET['id_estado']);
    $sql = "SELECT * FROM tbl_estados WHERE id_estado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_estado);
    $stmt->execute();
    $result = $stmt->get_result();
    $estado = $result->fetch_assoc();

    if (!$estado) {
        die("Estado no encontrado.");
    }
    // Inicializar $descripcion con el valor de la base de datos
    $descripcion = $estado['descripcion'] ?? '';
} else {
    die("ID inválido.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los valores del formulario
    $nombre_estado = $_POST['nombre_estado'] ?? null;
    $descripcion = $_POST['descripcion'] ?? null;

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

    // Eliminar la última coma y espacio
    $sql = rtrim($sql, ", ");

    // Agregar la condición WHERE
    $sql .= " WHERE id_estado=?";
    $params[] = $id_estado;
    $types .= "i";

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "<script>window.location.href='../pages/Admin/estados.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar el estado');</script>";
        echo $stmt->error;
    }
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

        <form method="POST">
            <div class="grid grid-cols-1 gap-6 mb-10">
                <!-- Nombre del Estado -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_estado" name="nombre_estado" value="<?= htmlspecialchars($estado['nombre_estado']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre del Estado"/>
                    <label for="nombre_estado"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Nombre del Estado
                    </label>
                </div>

                <!-- Descripción -->
                <div id="input" class="relative">
                    <textarea id="descripcion" name="descripcion"
                        class="block w-full text-sm px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto resize-none"
                        placeholder="Descripción" required
                        oninput="autoResize(this)"><?php echo htmlspecialchars($descripcion); ?></textarea>
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
    function autoResize(textarea) {
        // Reset the height to auto to recalculate the height
        textarea.style.height = 'auto';
        // Set the height to the scrollHeight (content height)
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    // Apply auto-resize when the page loads (in case there's pre-filled content)
    document.addEventListener("DOMContentLoaded", function () {
        const textarea = document.getElementById('descripcion');
        autoResize(textarea);
    });
    </script>
</body>
</html>