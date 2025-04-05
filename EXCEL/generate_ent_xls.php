<?php
require '../vendor/autoload.php';
require '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$encabezados = ['ID', 'Fecha Creación', 'Items', 'Título', 'Cuerpo', 'Usuario'];
$columnas = range('A', 'F');

$sheet->getStyle('A1:F1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0073E6']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
]);

foreach ($encabezados as $index => $nombre) {
    $sheet->setCellValue($columnas[$index] . '1', $nombre);
}

// Obtener filtros del formulario
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$fecha_desde = isset($_POST['fecha_desde']) ? trim($_POST['fecha_desde']) : '';
$fecha_hasta = isset($_POST['fecha_hasta']) ? trim($_POST['fecha_hasta']) : '';

// Construir la consulta SQL con filtros
$sql = "SELECT e.id_entradas, e.fecha_creacion, e.items, e.titulo, e.body, u.username 
        FROM tbl_reg_entradas e
        LEFT JOIN tbl_users u ON e.id_user = u.id_user
        WHERE 1=1";

if (!empty($titulo)) {
    $sql .= " AND e.titulo LIKE ?";
}
if (!empty($fecha_desde)) {
    $sql .= " AND e.fecha_creacion >= ?";
}
if (!empty($fecha_hasta)) {
    $sql .= " AND e.fecha_creacion <= ?";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

// Vincular parámetros dinámicamente
$types = '';
$params = [];
if (!empty($titulo)) {
    $types .= 's';
    $params[] = "%$titulo%";
}
if (!empty($fecha_desde)) {
    $types .= 's';
    $params[] = "$fecha_desde 00:00:00";
}
if (!empty($fecha_hasta)) {
    $types .= 's';
    $params[] = "$fecha_hasta 23:59:59";
}

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$fila = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['id_entradas']);
    $sheet->setCellValue('B' . $fila, $row['fecha_creacion']);
    $sheet->setCellValue('C' . $fila, $row['items']);
    $sheet->setCellValue('D' . $fila, $row['titulo']);
    $sheet->setCellValue('E' . $fila, $row['body']);
    $sheet->setCellValue('F' . $fila, $row['username']);
    $fila++;
}

$ultimaFila = $fila - 1;
$sheet->getStyle("A1:F$ultimaFila")->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
    ]
]);

foreach ($columnas as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_entradas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>