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
    // Obtener y validar los valores del formulario
    $nombre_tecnico = trim($_POST['nombre_tecnico'] ?? '');
    $dni_tecnico = trim($_POST['dni_tecnico'] ?? '');
    $edad_tecnico = trim($_POST['edad_tecnico'] ?? '');
    $num_telef = trim($_POST['num_telef'] ?? '');

    // Validaciones del lado del servidor
    if (!preg_match('/^[A-Za-zÁ-Úá-ú\s]+$/', $nombre_tecnico) && !empty($nombre_tecnico)) {
        die("El nombre solo puede contener letras y espacios.");
    }
    if (!preg_match('/^[0-9]{8}$/', $dni_tecnico) && !empty($dni_tecnico)) {
        die("El DNI debe contener exactamente 8 números.");
    }
    if (!preg_match('/^[0-9]{9}$/', $num_telef) && !empty($num_telef)) {
        die("El teléfono debe contener exactamente 9 números.");
    }
    if ((!empty($edad_tecnico) && !is_numeric($edad_tecnico)) || (strlen($edad_tecnico) > 2)) {
        die("La edad debe ser un número de máximo 2 dígitos.");
    }

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
        $types .= "s";
    }
    if (!empty($edad_tecnico)) {
        $sql .= "edad_tecnico=?, ";
        $params[] = $edad_tecnico;
        $types .= "i";
    }
    if (!empty($num_telef)) {
        $sql .= "num_telef=?, ";
        $params[] = $num_telef;
        $types .= "s";
    }

    $sql = rtrim($sql, ", ");
    $sql .= " WHERE id_tecnico=?";
    $params[] = $id_tecnico;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "<script>window.location.href='../pages/Admin/tecnico.php';</script>";
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
                    <input type="text" id="nombre_tecnico" name="nombre_tecnico" 
                        value="<?= htmlspecialchars($tecnico['nombre_tecnico']) ?>"
                        pattern="[A-Za-zÁ-Úá-ú\s]+"
                        title="Solo se permiten letras y espacios"
                        oninput="validarSoloLetras(this)"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre"/>
                    <label for="nombre_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Nombre
                    </label>
                </div>

                <!-- DNI -->
                <div id="input" class="relative">
                    <input type="text" id="dni_tecnico" name="dni_tecnico" 
                        value="<?= htmlspecialchars($tecnico['dni_tecnico']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="DNI" 
                        maxlength="8"
                        pattern="[0-9]{8}"
                        oninput="validarDNI(this)"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57"/>
                    <label for="dni_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        DNI (8 dígitos)
                    </label>
                </div>

                <!-- Edad -->
                <div id="input" class="relative">
                    <input type="number" id="edad_tecnico" name="edad_tecnico" 
                        value="<?= htmlspecialchars($tecnico['edad_tecnico']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Edad"
                        min="17" max="99" maxlength="2"
                        oninput="this.value=this.value.slice(0,2); if(this.value<17)this.value=17; if(this.value>99)this.value=99"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57"/>
                    <label for="edad_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Edad (17-99)
                    </label>
                </div>

                <!-- Número de Teléfono -->
                <div id="input" class="relative">
                    <input type="tel" id="num_telef" name="num_telef" 
                        value="<?= htmlspecialchars($tecnico['num_telef']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Número de Teléfono"
                        pattern="[0-9]{9}" maxlength="9"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57"/>
                    <label for="num_telef"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Número de Teléfono (9 dígitos)
                    </label>
                </div>
            </div>

            <div class="sm:flex sm:flex-row-reverse flex gap-4">
                <button type="submit"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-green-900 transition-all duration-300">
                    <div class="flex gap-2 items-center">Actualizar</div>
                </button>
                <button type="reset"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300"
                    onclick="window.history.back();">
                    <div class="flex gap-2 items-center">Cancelar</div>
                </button>
            </div>
        </form>
    </div>

    <script>
    function validarSoloLetras(input) {
        input.value = input.value.replace(/[^A-Za-zÁ-Úá-ú\s]/g, '');
        input.value = input.value.replace(/\b\w/g, l => l.toUpperCase());
    }

    function validarDNI(input) {
        input.value = input.value.replace(/[^0-9]/g, '').slice(0, 8);
    }

    document.querySelector('form').addEventListener('submit', function(e) {
        const dniInput = document.getElementById('dni_tecnico');
        if (dniInput.value.length > 0 && dniInput.value.length !== 8) {
            alert('El DNI debe tener exactamente 8 dígitos');
            dniInput.focus();
            e.preventDefault();
            return;
        }

        const edadInput = document.getElementById('edad_tecnico');
        const edad = parseInt(edadInput.value);
        if (edadInput.value.length > 0 && (isNaN(edad) || edad < 17 || edad > 99)) {
            alert('La edad debe ser un número entre 17 y 99');
            edadInput.focus();
            e.preventDefault();
            return;
        }

        const telefonoInput = document.getElementById('num_telef');
        if (telefonoInput.value.length > 0 && telefonoInput.value.length !== 9) {
            alert('El teléfono debe tener exactamente 9 dígitos');
            telefonoInput.focus();
            e.preventDefault();
            return;
        }
    });
    </script>
</body>
</html>