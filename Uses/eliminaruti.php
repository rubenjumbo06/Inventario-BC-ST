<?php
require_once("../conexion.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_utilidad'])) {
    $id = $_POST['id_utilidad'];
    
    // Verificar si la utilidad está en uso en tbl_consumibles
    $sql_consumibles = "SELECT COUNT(*) as count FROM tbl_consumibles WHERE utilidad_consumibles = ?";
    $stmt_consumibles = $conn->prepare($sql_consumibles);
    $stmt_consumibles->bind_param("i", $id);
    $stmt_consumibles->execute();
    $result_consumibles = $stmt_consumibles->get_result();
    $count_consumibles = $result_consumibles->fetch_assoc()['count'];
    $stmt_consumibles->close();

    // Verificar si la utilidad está en uso en tbl_herramientas
    $sql_herramientas = "SELECT COUNT(*) as count FROM tbl_herramientas WHERE utilidad_herramientas = ?";
    $stmt_herramientas = $conn->prepare($sql_herramientas);
    $stmt_herramientas->bind_param("i", $id);
    $stmt_herramientas->execute();
    $result_herramientas = $stmt_herramientas->get_result();
    $count_herramientas = $result_herramientas->fetch_assoc()['count'];
    $stmt_herramientas->close();

    // Determinar si la utilidad está en uso
    $in_use = ($count_consumibles > 0 || $count_herramientas > 0);

    if ($in_use) {
        // Si está en uso, solo actualizamos id_status a 2
        $sql = "UPDATE tbl_utilidad SET id_status = 2 WHERE id_utilidad = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'action' => 'hidden']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } else {
        // Si no está en uso, eliminamos el registro
        $sql = "DELETE FROM tbl_utilidad WHERE id_utilidad = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'action' => 'deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request or ID not provided']);
}

$conn->close();
?>