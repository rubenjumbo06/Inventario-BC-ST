<?php
include '../conexion.php';

$sql = "SELECT id_empresa, nombre FROM tbl_empresa WHERE id_status = 1";
$result = $conn->query($sql);

$empresas = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $empresas[] = $row;
    }
}

echo json_encode($empresas);
?>