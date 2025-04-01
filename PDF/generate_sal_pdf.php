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
        $this->Cell(0, 20, 'Reporte de Salidas', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
    
    // Método para formatear la fecha
    function formatFecha($fechaOriginal) {
        if (empty($fechaOriginal)) return '';
        
        $fecha = new DateTime($fechaOriginal);
        return $fecha->format('d/m/Y H:i');
    }
    
    // Método para dibujar texto en múltiples líneas
    function drawMultiLineText($text, $width, $lineHeight, $maxLines) {
        $words = explode(' ', $text);
        $currentLine = '';
        $lines = 0;
        
        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine.' '.$word : $word;
            if ($this->GetStringWidth($testLine) < $width - 2) {
                $currentLine = $testLine;
            } else {
                $this->Cell($width, $lineHeight, $currentLine, 0, 2, 'C');
                $currentLine = $word;
                $lines++;
                if ($lines >= $maxLines) break;
            }
        }
        if ($currentLine != '' && $lines < $maxLines) {
            $this->Cell($width, $lineHeight, $currentLine, 0, 2, 'C');
        }
    }
}

$pdf = new PDF('L');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(50, 168, 82);
$pdf->SetTextColor(255);

// Configuración exacta de columnas
$widths = [
    'id' => 10,
    'fecha' => 30,
    'items' => 10,
    'titulo' => 50,
    'destino' => 35,
    'body' => 85,
    'usuario' => 25
];

$tableWidth = array_sum($widths);
$startX = ($pdf->GetPageWidth() - $tableWidth) / 2;

// Encabezado
$pdf->SetX($startX);
foreach ($widths as $key => $width) {
    $header = match($key) {
        'id' => 'ID',
        'fecha' => 'Fecha',
        'items' => 'Items',
        'titulo' => 'Titulo',
        'destino' => 'Destino',
        'body' => 'Descripcion',
        'usuario' => 'Usuario'
    };
    $pdf->Cell($width, 10, $header, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

// Obtener datos
$sql = "SELECT rs.*, u.nombre as nombre_usuario 
        FROM tbl_reg_salidas rs
        LEFT JOIN tbl_users u ON rs.id_user = u.id_user
        ORDER BY rs.id_salidas";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    
    // Preparar el texto para las celdas que pueden ser largas
    $titulo = utf8_decode($row['titulo']);
    $destino = utf8_decode($row['Destino']);
    $body = utf8_decode($row['body']);
    $usuario = utf8_decode($row['nombre_usuario'] ?? $row['id_user']);
    $fecha = $pdf->formatFecha($row['fecha_creacion']); // Formatear fecha
    
    // Calcular la altura máxima necesaria entre todas las celdas con texto largo
    $textHeight = 6; // Altura por línea
    $maxLines = 1;
    
    // Función para calcular líneas necesarias
    $calculateLines = function($text, $maxWidth) use ($pdf) {
        $lines = [];
        $words = explode(' ', $text);
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
        return count($lines);
    };
    
    // Calcular líneas para cada campo de texto
    $linesTitulo = $calculateLines($titulo, $widths['titulo'] - 2);
    $linesDestino = $calculateLines($destino, $widths['destino'] - 2);
    $linesBody = $calculateLines($body, $widths['body'] - 2);
    $linesUsuario = $calculateLines($usuario, $widths['usuario'] - 2);
    
    $maxLines = max($linesTitulo, $linesDestino, $linesBody, $linesUsuario);
    $cellHeight = max(10, $maxLines * $textHeight); // Mínimo 10 de altura
    
    // Dibujar celdas fijas primero
    $pdf->Cell($widths['id'], $cellHeight, $row['id_salidas'], 1, 0, 'C');
    $pdf->Cell($widths['fecha'], $cellHeight, $fecha, 1, 0, 'C'); // Usar fecha formateada
    $pdf->Cell($widths['items'], $cellHeight, $row['items'], 1, 0, 'C');
    
    // Celda de Título (con ajuste de texto)
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Cell($widths['titulo'], $cellHeight, '', 1, 0); // Borde
    $pdf->SetXY($x, $y);
    $pdf->drawMultiLineText($titulo, $widths['titulo'], $textHeight, $linesTitulo);
    $pdf->SetXY($x + $widths['titulo'], $y);
    
    // Celda de Destino (con ajuste de texto)
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Cell($widths['destino'], $cellHeight, '', 1, 0); // Borde
    $pdf->SetXY($x, $y);
    $pdf->drawMultiLineText($destino, $widths['destino'], $textHeight, $linesDestino);
    $pdf->SetXY($x + $widths['destino'], $y);
    
    // Celda de Body (con ajuste de texto)
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Cell($widths['body'], $cellHeight, '', 1, 0); // Borde
    $pdf->SetXY($x, $y);
    $pdf->drawMultiLineText($body, $widths['body'], $textHeight, $linesBody);
    $pdf->SetXY($x + $widths['body'], $y);
    
    // Celda de Usuario (con ajuste de texto)
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Cell($widths['usuario'], $cellHeight, '', 1, 1); // Borde
    $pdf->SetXY($x, $y);
    $pdf->drawMultiLineText($usuario, $widths['usuario'], $textHeight, $linesUsuario);
    
    // Ajustar posición Y si hubo saltos
    if ($maxLines > 1) {
        $pdf->SetY($y + $cellHeight);
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_salidas.pdf"');
header('Cache-Control: max-age=0');
$pdf->Output('F', 'php://output');
exit();
?>