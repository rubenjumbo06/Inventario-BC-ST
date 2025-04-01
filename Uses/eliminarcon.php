<?php
require_once("../conexion.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_consumibles'])) {
    $id = $_POST['id_consumibles'];
    
    $sql = "DELETE FROM tbl_consumibles WHERE id_consumibles = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request or ID not provided']);
}

$conn->close();
?>