<?php
session_start();
require('../fpdf/fpdf.php');
require('../conexion.php');

class PDF extends FPDF {
    function Header() {
        $image_path = __DIR__ . '/../assets/img/fondopdf.jpeg';
        if (!file_exists($image_path)) {
            die("Error: La imagen de fondo no existe en $image_path");
        }
        $this->Image($image_path, 98.5, 65, 100, 80);
        $this->Image(__DIR__ . '/../assets/img/logo.png', 15, 10, 25);

        $this->SetFont('Arial', 'B', 24);
        $this->SetY(20);
        $this->Cell(0, 20, utf8_decode('Reporte de Consumibles'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }
}

function fixQuotes($text) {
    $replacements = [
        '“' => '"', '”' => '"', '‘' => "'", '’' => "'"
    ];
    return strtr($text, $replacements);
}

// Obtener filtros del formulario
$filter_empresa = isset($_POST['filter_empresa']) ? $_POST['filter_empresa'] : '';
$filter_estado = isset($_POST['filter_estado']) ? $_POST['filter_estado'] : '';
$filter_utilidad = isset($_POST['filter_utilidad']) ? $_POST['filter_utilidad'] : '';
$filter_usuario = isset($_POST['filter_usuario']) ? $_POST['filter_usuario'] : '';
$filter_search = isset($_POST['filter_search']) ? $_POST['filter_search'] : '';

// Construir la consulta con filtros
$sql = "SELECT c.id_consumibles, c.nombre_consumibles, c.cantidad_consumibles, 
        e.nombre AS nombre_empresa, es.nombre_estado, u.nombre_utilidad, c.fecha_ingreso, us.nombre AS nombre_usuario
        FROM tbl_consumibles c
        LEFT JOIN tbl_empresa e ON c.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON c.estado_consumibles = es.id_estado
        LEFT JOIN tbl_utilidad u ON c.utilidad_consumibles = u.id_utilidad
        LEFT JOIN tbl_users us ON c.id_user = us.id_user
        WHERE 1=1";

if ($filter_empresa) $sql .= " AND e.nombre = '" . $conn->real_escape_string($filter_empresa) . "'";
if ($filter_estado) $sql .= " AND es.nombre_estado = '" . $conn->real_escape_string($filter_estado) . "'";
if ($filter_utilidad) $sql .= " AND u.nombre_utilidad = '" . $conn->real_escape_string($filter_utilidad) . "'";
if ($filter_usuario) $sql .= " AND us.nombre = '" . $conn->real_escape_string($filter_usuario) . "'";
if ($filter_search) $sql .= " AND c.nombre_consumibles LIKE '%" . $conn->real_escape_string($filter_search) . "%'";
$sql .= " ORDER BY c.id_consumibles";

$conn->set_charset("utf8mb4");
$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$pdf = new PDF('L');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(50, 168, 82);
$pdf->SetTextColor(255);

$widths = [
    'id' => 10, 'nombre' => 50, 'cantidad' => 10, 'empresa' => 25, 
    'estado' => 20, 'utilidad' => 55, 'ingreso' => 40, 'usuario' => 35
];
$tableWidth = array_sum($widths);
$startX = ($pdf->GetPageWidth() - $tableWidth) / 2;

$pdf->SetX($startX);
$pdf->Cell($widths['id'], 10, 'ID', 1, 0, 'C', true);
$pdf->Cell($widths['nombre'], 10, utf8_decode('Nombre'), 1, 0, 'C', true);
$pdf->Cell($widths['cantidad'], 10, utf8_decode('Cant.'), 1, 0, 'C', true);
$pdf->Cell($widths['empresa'], 10, utf8_decode('Empresa'), 1, 0, 'C', true);
$pdf->Cell($widths['estado'], 10, utf8_decode('Estado'), 1, 0, 'C', true);
$pdf->Cell($widths['utilidad'], 10, utf8_decode('Utilidad'), 1, 0, 'C', true);
$pdf->Cell($widths['ingreso'], 10, utf8_decode('Ingreso'), 1, 0, 'C', true);
$pdf->Cell($widths['usuario'], 10, utf8_decode('Usuario'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    
    $nombre = fixQuotes($row['nombre_consumibles']);
    $nombre = utf8_decode($nombre);
    $maxWidth = $widths['nombre'] - 2;
    $textHeight = 6;
    $lines = [];
    $words = explode(' ', $nombre);
    $currentLine = '';
    
    foreach ($words as $word) {
        $testLine = $currentLine ? $currentLine.' '.$word : $word;
        if ($pdf->GetStringWidth($testLine) < $maxWidth) {
            $currentLine = $testLine;
        } else {
            $lines[] = $currentLine;
            $currentLine = $word;
        }
    }
    $lines[] = $currentLine;
    
    $lineCount = count($lines);
    $cellHeight = max(10, $lineCount * $textHeight);
    
    $pdf->Cell($widths['id'], $cellHeight, $row['id_consumibles'], 1, 0, 'C');
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Cell($widths['nombre'], $cellHeight, '', 1, 0);
    $pdf->SetXY($x, $y);
    foreach ($lines as $line) {
        $pdf->Cell($widths['nombre'], $textHeight, $line, 0, 2, 'C');
    }
    $pdf->SetXY($x + $widths['nombre'], $y);
    
    $pdf->Cell($widths['cantidad'], $cellHeight, $row['cantidad_consumibles'], 1, 0, 'C');
    $pdf->Cell($widths['empresa'], $cellHeight, utf8_decode($row['nombre_empresa']), 1, 0, 'C');
    $pdf->Cell($widths['estado'], $cellHeight, utf8_decode($row['nombre_estado']), 1, 0, 'C');
    $pdf->Cell($widths['utilidad'], $cellHeight, utf8_decode($row['nombre_utilidad']), 1, 0, 'C');
    $pdf->Cell($widths['ingreso'], $cellHeight, $row['fecha_ingreso'], 1, 0, 'C');
    $pdf->Cell($widths['usuario'], $cellHeight, utf8_decode($row['nombre_usuario']), 1, 1, 'C');
    
    if ($lineCount > 1) {
        $pdf->SetY($y + $cellHeight);
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_consumibles.pdf"');
header('Cache-Control: max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

$pdf->Output('D', 'reporte_consumibles.pdf');
exit();
?>