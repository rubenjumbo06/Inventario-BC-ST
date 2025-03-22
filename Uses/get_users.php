<?php   
include '../conexion.php';

$sql = "SELECT id_user, username FROM tbl_users";
$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
?>
