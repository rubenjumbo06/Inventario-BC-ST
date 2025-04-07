<?php
require '../vendor/autoload.php';
require '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Obtener filtros desde el formulario POST (assuming you'll add this to the HTML)
$filter_search = isset($_POST['filter_search']) && !empty($_POST['filter_search']) ? $_POST['filter_search'] : null;
$filter_rol = isset($_POST['filter_rol']) && !empty($_POST['filter_rol']) ? $_POST['filter_rol'] : null;

// Crear una nueva hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir encabezados y estilos
$encabezados = ['ID', 'Nombre', 'Apellidos', 'Usuario', 'Rol', 'Correo', 'Telefono', 'Creación', 'Modificacion'];
$columnas = range('A', 'I');

// Aplicar estilos a los encabezados
$sheet->getStyle('A1:I1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0073E6']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
]);

// Insertar encabezados
foreach ($encabezados as $index => $nombre) {
    $sheet->setCellValue($columnas[$index] . '1', $nombre);
}

// Construir la consulta SQL con filtros
$sql = "SELECT id_user, nombre, apellidos, username, role, correo, telefono, fecha_creacion, fecha_modificacion 
        FROM tbl_users 
        WHERE 1=1 AND id_status = 1";
$params = [];
$types = "";

if ($filter_search) {
    $sql .= " AND nombre LIKE ?";
    $params[] = "%$filter_search%";
    $types .= "s";
}
if ($filter_rol) {
    $sql .= " AND role = ?";
    $params[] = $filter_rol;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$fila = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['id_user']);
    $sheet->setCellValue('B' . $fila, $row['nombre']);
    $sheet->setCellValue('C' . $fila, $row['apellidos']);
    $sheet->setCellValue('D' . $fila, $row['username']);
    $sheet->setCellValue('E' . $fila, $row['role']);
    $sheet->setCellValue('F' . $fila, $row['correo']);
    $sheet->setCellValue('G' . $fila, $row['telefono']);
    $sheet->setCellValue('H' . $fila, $row['fecha_creacion']);
    $sheet->setCellValue('I' . $fila, $row['fecha_modificacion']);
    $fila++;
}

// Aplicar bordes a la tabla
$ultimaFila = $fila - 1;
$sheet->getStyle("A1:I$ultimaFila")->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
    ]
]);

// Ajustar tamaño automático de las columnas
foreach ($columnas as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar el archivo Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_usuarios.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>