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
        $this->Cell(0, 20, 'Reporte de Herramientas', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
    
    function wrapText($text, $maxWidth) {
        $lines = [];
        $words = explode(' ', $text);
        $currentLine = '';
        
        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine.' '.$word : $word;
            if ($this->GetStringWidth($testLine) < $maxWidth) {
                $currentLine = $testLine;
            } else {
                $lines[] = $currentLine;
                $currentLine = $word;
            }
        }
        $lines[] = $currentLine;
        return $lines;
    }
}

$pdf = new PDF('L');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(50, 168, 82);
$pdf->SetTextColor(255);

// Get filter values from POST request
$empresa_filter = isset($_POST['empresa']) ? $_POST['empresa'] : '';
$estado_filter = isset($_POST['estado']) ? $_POST['estado'] : '';
$utilidad_filter = isset($_POST['utilidad']) ? $_POST['utilidad'] : '';
$ubicacion_filter = isset($_POST['ubicacion']) ? $_POST['ubicacion'] : '';
$nombre_search = isset($_POST['nombre_search']) ? $_POST['nombre_search'] : '';

// Configuración de columnas
$widths = [
    'id' => 15,
    'nombre' => 50,
    'cantidad' => 10,
    'empresa' => 25,
    'estado' => 25,
    'utilidad' => 55,
    'ubicacion' => 35,
    'ingreso' => 45
];

$tableWidth = array_sum($widths);
$startX = ($pdf->GetPageWidth() - $tableWidth) / 2;

// Encabezado
$pdf->SetX($startX);
foreach ($widths as $key => $width) {
    $header = match($key) {
        'id' => 'ID',
        'nombre' => 'Nombre',
        'cantidad' => 'Cant.',
        'empresa' => 'Empresa',
        'estado' => 'Estado',
        'utilidad' => 'Utilidad',
        'ubicacion' => 'Ubicación',
        'ingreso' => 'Ingreso'
    };
    $pdf->Cell($width, 10, $header, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

// Construir la consulta SQL con filtros, búsqueda y el filtro de id_status = 1
$sql = "SELECT h.id_herramientas, h.nombre_herramientas, h.cantidad_herramientas,
        h.id_empresa, e.nombre as empresa_nombre,
        h.estado_herramientas, es.nombre_estado,
        h.utilidad_herramientas, u.nombre_utilidad,
        h.ubicacion_herramientas, h.fecha_ingreso
        FROM tbl_herramientas h
        LEFT JOIN tbl_empresa e ON h.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON h.estado_herramientas = es.id_estado
        LEFT JOIN tbl_utilidad u ON h.utilidad_herramientas = u.id_utilidad
        WHERE h.id_status = 1"; // Added id_status = 1 condition

if ($empresa_filter) {
    $sql .= " AND e.nombre = '" . $conn->real_escape_string($empresa_filter) . "'";
}
if ($estado_filter) {
    $sql .= " AND es.nombre_estado = '" . $conn->real_escape_string($estado_filter) . "'";
}
if ($utilidad_filter) {
    $sql .= " AND u.nombre_utilidad = '" . $conn->real_escape_string($utilidad_filter) . "'";
}
if ($ubicacion_filter) {
    $sql .= " AND h.ubicacion_herramientas = '" . $conn->real_escape_string($ubicacion_filter) . "'";
}
if ($nombre_search) {
    $sql .= " AND h.nombre_herramientas LIKE '%" . $conn->real_escape_string($nombre_search) . "%'";
}

$sql .= " ORDER BY h.id_herramientas";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    
    $nombre = utf8_decode($row['nombre_herramientas']);
    $maxWidth = $widths['nombre'] - 2;
    $textHeight = 6;
    
    $lines = $pdf->wrapText($nombre, $maxWidth);
    $lineCount = count($lines);
    $cellHeight = max(10, $lineCount * $textHeight);
    
    $pdf->Cell($widths['id'], $cellHeight, $row['id_herramientas'], 1, 0, 'C');
    
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Cell($widths['nombre'], $cellHeight, '', 1, 0);
    $pdf->SetXY($x, $y);
    foreach ($lines as $i => $line) {
        $pdf->Cell($widths['nombre'], $textHeight, $line, 0, 2, 'C');
    }
    $pdf->SetXY($x + $widths['nombre'], $y);
    
    $pdf->Cell($widths['cantidad'], $cellHeight, $row['cantidad_herramientas'], 1, 0, 'C');
    $pdf->Cell($widths['empresa'], $cellHeight, utf8_decode($row['empresa_nombre']), 1, 0, 'C');
    $pdf->Cell($widths['estado'], $cellHeight, utf8_decode($row['nombre_estado']), 1, 0, 'C');
    $pdf->Cell($widths['utilidad'], $cellHeight, utf8_decode($row['nombre_utilidad']), 1, 0, 'C');
    
    $ubicacion = utf8_decode($row['ubicacion_herramientas']);
    $ubicacionLines = $pdf->wrapText($ubicacion, $widths['ubicacion'] - 2);
    $ubicacionHeight = max(10, count($ubicacionLines) * $textHeight);
    
    $xUbic = $pdf->GetX();
    $yUbic = $pdf->GetY();
    $pdf->Cell($widths['ubicacion'], $cellHeight, '', 1, 0);
    $pdf->SetXY($xUbic, $yUbic);
    foreach ($ubicacionLines as $line) {
        $pdf->Cell($widths['ubicacion'], $textHeight, $line, 0, 2, 'C');
    }
    $pdf->SetXY($xUbic + $widths['ubicacion'], $yUbic);
    
    $pdf->Cell($widths['ingreso'], $cellHeight, $row['fecha_ingreso'], 1, 1, 'C');
    
    if ($lineCount > 1 || count($ubicacionLines) > 1) {
        $pdf->SetY($y + $cellHeight);
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_herramientas.pdf"');
header('Cache-Control: max-age=0');
$pdf->Output('D', 'reporte_herramientas.pdf');
exit();
?>