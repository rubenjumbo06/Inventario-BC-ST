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
    
    // Función para envolver texto dentro de la clase PDF
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

// Configuración exacta de columnas (ajustado para herramientas)
$widths = [
    'id' => 15,
    'nombre' => 50,
    'cantidad' => 15,
    'empresa' => 15,
    'estado' => 15,
    'utilidad' => 15,
    'ubicacion' => 50,
    'ingreso' => 50
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
        'empresa' => 'Emp.',
        'estado' => 'Est.',
        'utilidad' => 'Uti.',
        'ubicacion' => 'Ubicacion',
        'ingreso' => 'Ingreso'
    };
    $pdf->Cell($width, 10, $header, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

// Obtener datos
$sql = "SELECT * FROM tbl_herramientas ORDER BY id_herramientas";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    
    // Preparar el texto para el nombre
    $nombre = utf8_decode($row['nombre_herramientas']);
    $maxWidth = $widths['nombre'] - 2;
    $textHeight = 6;
    
    // Usar la función wrapText de la clase
    $lines = $pdf->wrapText($nombre, $maxWidth);
    $lineCount = count($lines);
    $cellHeight = max(10, $lineCount * $textHeight);
    
    // Dibujar celda de ID
    $pdf->Cell($widths['id'], $cellHeight, $row['id_herramientas'], 1, 0, 'C');
    
    // Guardar posición para el nombre
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    
    // Dibujar celda de nombre (solo borde)
    $pdf->Cell($widths['nombre'], $cellHeight, '', 1, 0);
    
    // Escribir el texto en la celda de nombre (centrado)
    $pdf->SetXY($x, $y);
    foreach ($lines as $i => $line) {
        $pdf->Cell($widths['nombre'], $textHeight, $line, 0, 2, 'C');
    }
    
    // Restaurar posición para continuar
    $pdf->SetXY($x + $widths['nombre'], $y);
    
    // Resto de celdas (todas con la misma altura)
    $pdf->Cell($widths['cantidad'], $cellHeight, $row['cantidad_herramientas'], 1, 0, 'C');
    $pdf->Cell($widths['empresa'], $cellHeight, $row['id_empresa'], 1, 0, 'C');
    $pdf->Cell($widths['estado'], $cellHeight, $row['estado_herramientas'], 1, 0, 'C');
    $pdf->Cell($widths['utilidad'], $cellHeight, $row['utilidad_herramientas'], 1, 0, 'C');
    
    // Manejo para ubicación
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
    
    // Celda de fecha de ingreso
    $pdf->Cell($widths['ingreso'], $cellHeight, $row['fecha_ingreso'], 1, 1, 'C');
    
    // Ajustar posición Y si hubo saltos
    if ($lineCount > 1 || count($ubicacionLines) > 1) {
        $pdf->SetY($y + $cellHeight);
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_herramientas.pdf"');
header('Cache-Control: max-age=0');
$pdf->Output('F', 'php://output');
exit();
?>