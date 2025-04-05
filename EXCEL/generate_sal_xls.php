<?php
require '../vendor/autoload.php';
require '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$encabezados = ['ID', 'Fecha Creación', 'Items', 'Título', 'Destino', 'Cuerpo', 'Usuario'];
$columnas = range('A', 'G');

$sheet->getStyle('A1:G1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0073E6']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
]);

foreach ($encabezados as $index => $nombre) {
    $sheet->setCellValue($columnas[$index] . '1', $nombre);
}

// Obtener filtros del formulario
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$destino = isset($_POST['destino']) ? trim($_POST['destino']) : '';
$fecha_desde = isset($_POST['fechaDesde']) ? trim($_POST['fechaDesde']) : '';
$fecha_hasta = isset($_POST['fechaHasta']) ? trim($_POST['fechaHasta']) : '';
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';

// Construir la consulta SQL con filtros
$sql = "SELECT s.id_salidas, s.fecha_creacion, s.items, s.titulo, s.Destino, s.body, u.username 
        FROM tbl_reg_salidas s
        LEFT JOIN tbl_users u ON s.id_user = u.id_user
        WHERE 1=1";

$types = '';
$params = [];

if (!empty($titulo)) {
    $sql .= " AND s.titulo LIKE ?";
    $types .= 's';
    $params[] = "%$titulo%";
}
if (!empty($destino)) {
    $sql .= " AND s.Destino LIKE ?";
    $types .= 's';
    $params[] = "%$destino%";
}
if (!empty($fecha_desde)) {
    $sql .= " AND s.fecha_creacion >= ?";
    $types .= 's';
    $params[] = "$fecha_desde 00:00:00";
}
if (!empty($fecha_hasta)) {
    $sql .= " AND s.fecha_creacion <= ?";
    $types .= 's';
    $params[] = "$fecha_hasta 23:59:59";
}
if (!empty($usuario)) {
    $sql .= " AND u.username = ?";
    $types .= 's';
    $params[] = $usuario;
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$fila = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['id_salidas']);
    $sheet->setCellValue('B' . $fila, $row['fecha_creacion']);
    $sheet->setCellValue('C' . $fila, $row['items']);
    $sheet->setCellValue('D' . $fila, $row['titulo']);
    $sheet->setCellValue('E' . $fila, $row['Destino']);
    $sheet->setCellValue('F' . $fila, $row['body']);
    $sheet->setCellValue('G' . $fila, $row['username']);
    $fila++;
}

$ultimaFila = $fila - 1;
$sheet->getStyle("A1:G$ultimaFila")->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
    ]
]);

foreach ($columnas as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_salidas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>