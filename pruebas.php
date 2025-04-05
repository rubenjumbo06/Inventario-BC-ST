<?php
session_start();
date_default_timezone_set('America/Lima');
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'tecnico') {
    header('Location: login.php');
    exit;
}
$usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario no definido';
$role = $_SESSION['role'];

include '../../conexion.php';
$conexion = $conn;

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Herramientas: Sin cambios en la consulta
$sql_h = "SELECT DISTINCT h.id_herramientas, h.nombre_herramientas 
         FROM tbl_herramientas h 
         INNER JOIN tbl_movimientos_herramientas mh ON h.id_herramientas = mh.id_herramientas 
         WHERE mh.tipo_movimiento = 'salida' 
         AND mh.ubicacion_destino = 'En campo' 
         AND NOT EXISTS (
             SELECT 1 FROM tbl_movimientos_herramientas me 
             WHERE me.id_herramientas = mh.id_herramientas 
             AND me.tipo_movimiento = 'entrada' 
             AND me.fecha > mh.fecha
         )";
$resultado_h = $conexion->query($sql_h);
if (!$resultado_h) {
    die("Error en consulta de herramientas: " . $conexion->error);
}

// Activos: Sin cambios en la consulta
$sql_act = "SELECT DISTINCT a.id_activos, a.nombre_activos 
            FROM tbl_activos a 
            INNER JOIN tbl_movimientos_activos ma ON a.id_activos = ma.id_activos 
            WHERE ma.tipo_movimiento = 'salida' 
            AND ma.ubicacion_destino = 'En instalación' 
            AND NOT EXISTS (
                SELECT 1 FROM tbl_movimientos_activos me 
                WHERE me.id_activos = ma.id_activos 
                AND me.tipo_movimiento = 'entrada' 
                AND me.ubicacion_destino = 'Instalado' 
                AND me.fecha > ma.fecha
            )";
$resultado_act = $conexion->query($sql_act);
if (!$resultado_act) {
    die("Error en consulta de activos: " . $conexion->error);
}

// Consumibles: Solo aparecen si están "En campo" y no tienen entrada que los haya marcado como "Instalado"
$sql_con = "SELECT DISTINCT c.id_consumibles, c.nombre_consumibles, 
            (SELECT SUM(mc.cantidad) 
             FROM tbl_movimientos_consumibles mc 
             WHERE mc.id_consumibles = c.id_consumibles 
             AND mc.tipo_movimiento = 'salida' 
             AND mc.ubicacion_destino = 'En campo') - 
            IFNULL((SELECT SUM(mc2.cantidad) 
                    FROM tbl_movimientos_consumibles mc2 
                    WHERE mc2.id_consumibles = c.id_consumibles 
                    AND mc2.tipo_movimiento = 'entrada' 
                    AND mc2.ubicacion_destino = 'En almacen'), 0) AS cantidad_disponible 
            FROM tbl_consumibles c 
            INNER JOIN tbl_movimientos_consumibles mc ON c.id_consumibles = mc.id_consumibles 
            WHERE mc.tipo_movimiento = 'salida' 
            AND mc.ubicacion_destino = 'En campo' 
            AND NOT EXISTS (
                SELECT 1 FROM tbl_movimientos_consumibles mi 
                WHERE mi.id_consumibles = c.id_consumibles 
                AND mi.tipo_movimiento = 'salida' 
                AND mi.ubicacion_destino = 'Instalado'
            )
            HAVING cantidad_disponible > 0";
$resultado_con = $conexion->query($sql_con);
if (!$resultado_con) {
    die("Error en consulta de consumibles: " . $conexion->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $regex = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s.,;:%¿?¡!()-]+$/';
    if (empty($titulo) || !preg_match($regex, $titulo)) {
        echo "<script>alert('El título es obligatorio y solo puede contener letras, números, espacios y signos básicos de puntuación'); window.location='reg_entradas.php';</script>";
        exit;
    }

    $id_user = $_SESSION['id_user'];
    $selectedItems = json_decode($_POST['body'] ?? '', true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($selectedItems)) {
        echo "<script>alert('Error en los datos enviados (JSON inválido o no enviado)'); window.location='reg_entradas.php';</script>";
        exit;
    }

    $totalItems = 0;
    $body = "";
    $fecha_creacion = date('Y-m-d H:i:s');

    $conn->begin_transaction();

    try {
        // Herramientas: Agregamos actualización de ubicación en tbl_herramientas
        if (!empty($selectedItems['herramientas'])) {
            $herramientas = array_map(function ($nombre) {
                return htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
            }, $selectedItems['herramientas']);
            $body .= "Herramientas: (" . implode(", ", $herramientas) . "), ";
            $totalItems += count($selectedItems['herramientas']);

            foreach ($selectedItems['herramientas'] as $id => $nombre) {
                $check_stmt = $conn->prepare("SELECT 1 FROM tbl_movimientos_herramientas mh 
                                              WHERE mh.id_herramientas = ? 
                                              AND mh.tipo_movimiento = 'salida' 
                                              AND mh.ubicacion_destino = 'En campo' 
                                              AND NOT EXISTS (
                                                  SELECT 1 FROM tbl_movimientos_herramientas me 
                                                  WHERE me.id_herramientas = mh.id_herramientas 
                                                  AND me.tipo_movimiento = 'entrada' 
                                                  AND me.fecha > mh.fecha
                                              )");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows == 0) {
                    throw new Exception("Error: La herramienta $nombre no está en campo");
                }
                $check_stmt->close();

                // Registrar el movimiento de entrada
                $stmt = $conn->prepare("INSERT INTO tbl_movimientos_herramientas (tipo_movimiento, id_herramientas, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                        VALUES ('entrada', ?, 1, 'En campo', 'En almacen', NOW(), ?)");
                $stmt->bind_param("ii", $id, $id_user);
                if (!$stmt->execute()) {
                    throw new Exception("Error al registrar movimiento de herramienta ID $id: " . $stmt->error);
                }
                $stmt->close();

                // Actualizar la ubicación en tbl_herramientas
                $update_stmt = $conn->prepare("UPDATE tbl_herramientas 
                                               SET ubicacion_herramientas = 'En almacen' 
                                               WHERE id_herramientas = ?");
                $update_stmt->bind_param("i", $id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Error al actualizar ubicación de herramienta ID $id: " . $update_stmt->error);
                }
                $update_stmt->close();
            }
        }

        // Activos: Agregamos actualización de ubicación en tbl_activos
        if (!empty($selectedItems['activos'])) {
            $activos = [];
            foreach ($selectedItems['activos'] as $id => $data) {
                $nombre = $data['nombre'] ?? '';
                $instalado = $data['instalado'] ?? '';
                if (empty($nombre) || !in_array($instalado, ['si', 'no'])) {
                    throw new Exception("Datos inválidos para el activo ID $id");
                }
                $ubicacion_destino = ($instalado === 'si') ? 'Instalado' : 'En almacen';
                
                $activos[] = "$nombre (" . ($instalado === 'si' ? 'Instalado' : 'Retornado') . ")";
                $totalItems++;

                $check_stmt = $conn->prepare("SELECT 1 FROM tbl_movimientos_activos ma 
                                            WHERE ma.id_activos = ? 
                                            AND ma.tipo_movimiento = 'salida' 
                                            AND ma.ubicacion_destino = 'En instalación' 
                                            AND NOT EXISTS (
                                                SELECT 1 FROM tbl_movimientos_activos me 
                                                WHERE me.id_activos = ma.id_activos 
                                                AND me.tipo_movimiento = 'entrada' 
                                                AND me.ubicacion_destino = 'Instalado' 
                                                AND me.fecha > ma.fecha
                                            )");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows == 0) {
                    throw new Exception("Error: El activo $nombre no está en instalación");
                }
                $check_stmt->close();

                // Registrar el movimiento de entrada
                $stmt = $conn->prepare("INSERT INTO tbl_movimientos_activos (tipo_movimiento, id_activos, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                      VALUES ('entrada', ?, 1, 'En instalación', ?, NOW(), ?)");
                $stmt->bind_param("isi", $id, $ubicacion_destino, $id_user);
                if (!$stmt->execute()) {
                    throw new Exception("Error al registrar movimiento de activo ID $id: " . $stmt->error);
                }
                $stmt->close();

                // Actualizar la ubicación en tbl_activos
                $update_stmt = $conn->prepare("UPDATE tbl_activos 
                                               SET ubicacion_activos = ? 
                                               WHERE id_activos = ?");
                $update_stmt->bind_param("si", $ubicacion_destino, $id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Error al actualizar ubicación de activo ID $id: " . $update_stmt->error);
                }
                $update_stmt->close();
            }
            $body .= "Activos: (" . implode(", ", $activos) . "), ";
        }

        // Consumibles: Aseguramos que los instalados no aparezcan más
        if (!empty($selectedItems['consumibles'])) {
            $consumibles = [];
            foreach ($selectedItems['consumibles'] as $id => $data) {
                $nombre = $data['nombre'] ?? '';
                $cantidadSeleccionada = (int)($data['cantidad'] ?? 0);
                if (empty($nombre)) {
                    throw new Exception("Nombre de consumible inválido para ID $id");
                }

                // Verificar cantidad en campo
                $check_stmt = $conn->prepare("SELECT 
                                                (SELECT SUM(mc.cantidad) 
                                                 FROM tbl_movimientos_consumibles mc 
                                                 WHERE mc.id_consumibles = ? 
                                                 AND mc.tipo_movimiento = 'salida' 
                                                 AND mc.ubicacion_destino = 'En campo') - 
                                                IFNULL((SELECT SUM(mc2.cantidad) 
                                                        FROM tbl_movimientos_consumibles mc2 
                                                        WHERE mc2.id_consumibles = ? 
                                                        AND mc2.tipo_movimiento = 'entrada' 
                                                        AND mc2.ubicacion_destino = 'En almacen'), 0) AS cantidad_disponible,
                                                c.cantidad_consumibles 
                                              FROM tbl_consumibles c 
                                              WHERE c.id_consumibles = ?");
                $check_stmt->bind_param("iii", $id, $id, $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $row = $check_result->fetch_assoc();
                $cantidad_en_campo = (int)($row['cantidad_disponible'] ?? 0);
                $cantidad_stock_total = (int)($row['cantidad_consumibles'] ?? 0);
                $check_stmt->close();

                if ($cantidad_en_campo <= 0) {
                    throw new Exception("Error: El consumible $nombre no está en campo o ya fue devuelto");
                }
                if ($cantidadSeleccionada > $cantidad_en_campo) {
                    throw new Exception("Error: No puedes devolver más ($cantidadSeleccionada) de $nombre de lo que está en campo ($cantidad_en_campo)");
                }

                // Registrar lo devuelto a "En almacen"
                if ($cantidadSeleccionada > 0) {
                    $consumibles[] = "$nombre($cantidadSeleccionada)";
                    $totalItems += $cantidadSeleccionada;

                    $stmt = $conn->prepare("INSERT INTO tbl_movimientos_consumibles (tipo_movimiento, id_consumibles, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                            VALUES ('entrada', ?, ?, 'En campo', 'En almacen', NOW(), ?)");
                    $stmt->bind_param("iii", $id, $cantidadSeleccionada, $id_user);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al registrar movimiento de consumible ID $id: " . $stmt->error);
                    }
                    $stmt->close();

                    // Sumar lo devuelto a tbl_consumibles
                    $update_stmt = $conn->prepare("UPDATE tbl_consumibles 
                                                   SET cantidad_consumibles = cantidad_consumibles + ?, 
                                                       ubicacion_consumibles = 'En almacen' 
                                                   WHERE id_consumibles = ?");
                    $update_stmt->bind_param("ii", $cantidadSeleccionada, $id);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Error al actualizar tbl_consumibles ID $id: " . $update_stmt->error);
                    }
                    $update_stmt->close();
                }

                // Registrar lo sobrante como "Instalado" (sin afectar cantidad_consumibles)
                $cantidad_instalada = $cantidad_en_campo - $cantidadSeleccionada;
                if ($cantidad_instalada > 0) {
                    $stmt = $conn->prepare("INSERT INTO tbl_movimientos_consumibles (tipo_movimiento, id_consumibles, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                            VALUES ('salida', ?, ?, 'En campo', 'Instalado', NOW(), ?)");
                    $stmt->bind_param("iii", $id, $cantidad_instalada, $id_user);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al registrar consumibles instalados ID $id: " . $stmt->error);
                    }
                    $stmt->close();

                    // Actualizar la ubicación en tbl_consumibles a "Instalado" para los que quedan instalados
                    $update_stmt = $conn->prepare("UPDATE tbl_consumibles 
                                                   SET ubicacion_consumibles = 'Instalado' 
                                                   WHERE id_consumibles = ?");
                    $update_stmt->bind_param("i", $id);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Error al actualizar ubicación de consumible ID $id: " . $update_stmt->error);
                    }
                    $update_stmt->close();
                }
            }
            if (!empty($consumibles)) {
                $body .= "Consumibles: [" . implode(", ", $consumibles) . "]";
            }
        }

        $stmt = $conn->prepare("INSERT INTO tbl_reg_entradas (fecha_creacion, items, titulo, body, id_user) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $fecha_creacion, $totalItems, $titulo, $body, $id_user);
        if (!$stmt->execute()) {
            throw new Exception("Error al registrar la entrada: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        echo "<script>alert('Entrada registrada exitosamente'); window.location='reg_entradas.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('" . addslashes($e->getMessage()) . "'); window.location='reg_entradas.php';</script>";
    }
    exit;
}
?>