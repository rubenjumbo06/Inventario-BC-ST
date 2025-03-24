<?php
session_start();
include '../../conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Obtener el ID del usuario autenticado
$id_user = $_SESSION['id_user'];

// Inicializar variables
$nombre = $apellidos = $username = $correo = $telefono = $rol = '';
$mensaje = '';

try {
    // Obtener los datos del usuario de la base de datos
    $query = "SELECT nombre, apellidos, username, correo, telefono, role FROM tbl_users WHERE id_user = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $nombre = $user['nombre'];
        $apellidos = $user['apellidos'];
        $username = $user['username'];
        $correo = $user['correo'];
        $telefono = $user['telefono'];
        $rol = $user['role'];
    } else {
        throw new Exception("Usuario no encontrado.");
    }

    // Procesar el formulario cuando se envía
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre = trim(htmlspecialchars($_POST['nombre']));
        $apellidos = trim(htmlspecialchars($_POST['apellidos']));
        $username = trim(htmlspecialchars($_POST['username']));
        $correo = trim(htmlspecialchars($_POST['correo']));
        $telefono = trim(htmlspecialchars($_POST['telefono']));
        $password = $_POST['password'];

        // Validar que los nombres y apellidos solo contengan letras y espacios
        if (!preg_match('/^[A-Za-z\s]+$/', $nombre)) {
            throw new Exception("El campo 'Nombres' solo puede contener letras y espacios.");
        }
        if (!preg_match('/^[A-Za-z\s]+$/', $apellidos)) {
            throw new Exception("El campo 'Apellidos' solo puede contener letras y espacios.");
        }

        // Validar el correo electrónico
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El correo electrónico no es válido.");
        }

        // Validar el número de teléfono
        if (!preg_match('/^[0-9]{9}$/', $telefono)) {
            throw new Exception("El número de teléfono debe tener 9 dígitos.");
        }

        // Actualizar los datos del usuario
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE tbl_users SET nombre = ?, apellidos = ?, username = ?, password = ?, correo = ?, telefono = ? WHERE id_user = ?";
            $params = [$nombre, $apellidos, $username, $hashed_password, $correo, $telefono, $id_user];
        } else {
            $sql = "UPDATE tbl_users SET nombre = ?, apellidos = ?, username = ?, correo = ?, telefono = ? WHERE id_user = ?";
            $params = [$nombre, $apellidos, $username, $correo, $telefono, $id_user];
        }

        if ($stmt = $conn->prepare($sql)) {
            $types = str_repeat('s', count($params) - 1) . 'i';
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                // ACTUALIZAR LAS VARIABLES DE SESIÓN CON LOS NUEVOS DATOS
                $_SESSION['username'] = $username;
                $_SESSION['nombre'] = $nombre;
                $_SESSION['apellidos'] = $apellidos;
                $_SESSION['correo'] = $correo;
                $_SESSION['telefono'] = $telefono;
                
                $mensaje = "¡Datos actualizados correctamente!";
                header("Location: perfiltec.php");
                exit();
            } else {
                throw new Exception("Error al actualizar los datos: " . $stmt->error);
            }
        } else {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
    }
} catch (Exception $e) {
    $mensaje = $e->getMessage(); // Capturar el mensaje de error
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex relative flex-col justify-center items-center bg-gray-100 h-[70px] w-[70px] rounded-[16px] overflow-hidden">
                    <img src="../../assets/img/p3.jpeg" alt="Editar Listas" class="w-full h-full object-cover">
                </div>
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]"><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        <?php echo htmlspecialchars($rol); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mostrar mensajes de error o éxito -->
        <?php if (!empty($mensaje)): ?>
            <div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Campo Nombres -->
                <div id="input" class="relative">
                    <input type="text" id="nombre" name="nombre"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombres" value="<?php echo htmlspecialchars($nombre); ?>" required />
                    <label for="nombre"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombres
                    </label>
                </div>

                <!-- Campo Apellidos -->
                <div id="input" class="relative">
                    <input type="text" id="apellidos" name="apellidos"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Apellidos" value="<?php echo htmlspecialchars($apellidos); ?>" required />
                    <label for="apellidos"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Apellidos
                    </label>
                </div>

                <!-- Campo Username -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="username"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Username
                    </label>
                </div>

                <!-- Campo Password -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="password"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Contraseña" />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Contraseña
                    </label>
                </div>

                <!-- Campo Correo Electrónico -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="correo"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Correo Electrónico" value="<?php echo htmlspecialchars($correo); ?>" required />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Correo Electrónico
                    </label>
                </div>

                <!-- Campo Número de Teléfono -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="telefono"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Número de Teléfono" value="<?php echo htmlspecialchars($telefono); ?>" required />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
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
                <a href="perfiltec.php">
                    <button type="button"
                        class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300">
                        <div class="flex gap-2 items-center">Cancelar</div>
                    </button>
                </a>
            </div>
        </form>
    </div>
</body>
</html>