<?php
require_once("../conexion.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_empresa'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request or ID not provided']);
    exit;
}

$id = $_POST['id_empresa'];
$check_only = isset($_POST['check_only']) && $_POST['check_only'] === 'true';
$new_empresa = isset($_POST['new_empresa']) ? $_POST['new_empresa'] : null;

// Función para verificar relaciones
function check_relations($conn, $id) {
    $tables = [];
    
    try {
        // Verificar tbl_activos
        $sql_activos = "SELECT COUNT(*) as count FROM tbl_activos WHERE id_empresa = ?";
        $stmt_activos = $conn->prepare($sql_activos);
        if (!$stmt_activos) throw new Exception("Error preparando consulta tbl_activos: " . $conn->error);
        $stmt_activos->bind_param("i", $id);
        $stmt_activos->execute();
        $result_activos = $stmt_activos->get_result();
        $count_activos = $result_activos->fetch_assoc()['count'];
        $stmt_activos->close();
        if ($count_activos > 0) $tables[] = "tbl_activos ($count_activos registros)";

        // Verificar tbl_consumibles
        $sql_consumibles = "SELECT COUNT(*) as count FROM tbl_consumibles WHERE id_empresa = ?";
        $stmt_consumibles = $conn->prepare($sql_consumibles);
        if (!$stmt_consumibles) throw new Exception("Error preparando consulta tbl_consumibles: " . $conn->error);
        $stmt_consumibles->bind_param("i", $id);
        $stmt_consumibles->execute();
        $result_consumibles = $stmt_consumibles->get_result();
        $count_consumibles = $result_consumibles->fetch_assoc()['count'];
        $stmt_consumibles->close();
        if ($count_consumibles > 0) $tables[] = "tbl_consumibles ($count_consumibles registros)";

        // Verificar tbl_herramientas
        $sql_herramientas = "SELECT COUNT(*) as count FROM tbl_herramientas WHERE id_empresa = ?";
        $stmt_herramientas = $conn->prepare($sql_herramientas);
        if (!$stmt_herramientas) throw new Exception("Error preparando consulta tbl_herramientas: " . $conn->error);
        $stmt_herramientas->bind_param("i", $id);
        $stmt_herramientas->execute();
        $result_herramientas = $stmt_herramientas->get_result();
        $count_herramientas = $result_herramientas->fetch_assoc()['count'];
        $stmt_herramientas->close();
        if ($count_herramientas > 0) $tables[] = "tbl_herramientas ($count_herramientas registros)";
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error en check_relations: ' . $e->getMessage()]);
        exit;
    }

    return $tables;
}

if ($check_only) {
    $related_tables = check_relations($conn, $id);
    echo json_encode([
        'success' => true,
        'related' => !empty($related_tables),
        'tables' => $related_tables
    ]);
    exit;
}

$related_tables = check_relations($conn, $id);

// Iniciar transacción
$conn->begin_transaction();
try {
    if (!empty($related_tables)) {
        if (!$new_empresa) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'La empresa tiene registros relacionados. Por favor, reasigna antes de ocultarla.']);
            exit;
        }

        // Reasignar registros
        $sql = "UPDATE tbl_activos SET id_empresa = ? WHERE id_empresa = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error preparando UPDATE tbl_activos: " . $conn->error);
        $stmt->bind_param("ii", $new_empresa, $id);
        $stmt->execute();
        $stmt->close();

        $sql = "UPDATE tbl_consumibles SET id_empresa = ? WHERE id_empresa = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error preparando UPDATE tbl_consumibles: " . $conn->error);
        $stmt->bind_param("ii", $new_empresa, $id);
        $stmt->execute();
        $stmt->close();

        $sql = "UPDATE tbl_herramientas SET id_empresa = ? WHERE id_empresa = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Error preparando UPDATE tbl_herramientas: " . $conn->error);
        $stmt->bind_param("ii", $new_empresa, $id);
        $stmt->execute();
        $stmt->close();
    }

    // Ocultar empresa (cambiar id_status a 2)
    $sql = "UPDATE tbl_empresa SET id_status = 2 WHERE id_empresa = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Error preparando UPDATE tbl_empresa: " . $conn->error);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Empresa eliminada correctamente']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al procesar: ' . $e->getMessage()]);
}

$conn->close();
?>