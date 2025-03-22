<?php
require_once("../conexion.php");

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (isset($_GET['id_tecnico']) && is_numeric($_GET['id_tecnico'])) {
    $id_tecnico = intval($_GET['id_tecnico']);
    $sql = "SELECT * FROM tbl_tecnico WHERE id_tecnico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_tecnico);
    $stmt->execute();
    $result = $stmt->get_result();
    $tecnico = $result->fetch_assoc();

    if (!$tecnico) {
        die("Técnico no encontrado.");
    }
} else {
    die("ID inválido.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los valores del formulario
    $nombre_tecnico = $_POST['nombre_tecnico'] ?? null;
    $dni_tecnico = $_POST['dni_tecnico'] ?? null;
    $edad_tecnico = $_POST['edad_tecnico'] ?? null;
    $num_telef = $_POST['num_telef'] ?? null;

    // Construir la consulta SQL dinámicamente
    $sql = "UPDATE tbl_tecnico SET ";
    $params = [];
    $types = "";

    if (!empty($nombre_tecnico)) {
        $sql .= "nombre_tecnico=?, ";
        $params[] = $nombre_tecnico;
        $types .= "s";
    }
    if (!empty($dni_tecnico)) {
        $sql .= "dni_tecnico=?, ";
        $params[] = $dni_tecnico;
        $types .= "i";
    }
    if (!empty($edad_tecnico)) {
        $sql .= "edad_tecnico=?, ";
        $params[] = $edad_tecnico;
        $types .= "i";
    }
    if (!empty($num_telef)) {
        $sql .= "num_telef=?, ";
        $params[] = $num_telef;
        $types .= "i";
    }

    // Eliminar la última coma y espacio
    $sql = rtrim($sql, ", ");

    // Agregar la condición WHERE
    $sql .= " WHERE id_tecnico=?";
    $params[] = $id_tecnico;
    $types .= "i";

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "<script>window.location.href='../pages/tecnico.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar el técnico');</script>";
        echo $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Técnico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Editar Técnico</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Técnicos
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_tecnico" name="nombre_tecnico" value="<?= htmlspecialchars($tecnico['nombre_tecnico']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre"/>
                    <label for="nombre_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Nombre
                    </label>
                </div>

                <!-- DNI -->
                <div id="input" class="relative">
                    <input type="number" id="dni_tecnico" name="dni_tecnico" value="<?= htmlspecialchars($tecnico['dni_tecnico']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="DNI"/>
                    <label for="dni_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        DNI
                    </label>
                </div>

                <!-- Edad -->
                <div id="input" class="relative">
                    <input type="number" id="edad_tecnico" name="edad_tecnico" value="<?= htmlspecialchars($tecnico['edad_tecnico']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Edad"/>
                    <label for="edad_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Edad
                    </label>
                </div>

                <!-- Número de Teléfono -->
                <div id="input" class="relative">
                    <input type="number" id="num_telef" name="num_telef" value="<?= htmlspecialchars($tecnico['num_telef']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Número de Teléfono"/>
                    <label for="num_telef"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Número de Teléfono
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
</body>
</html>