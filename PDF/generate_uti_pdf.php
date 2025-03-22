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

            $this->Image($image_path, 98.5, 65, 100, 80); // Centrar fondo
            $this->Image(__DIR__ . '/../assets/img/logo.png', 15, 10, 25);

            $this->SetFont('Arial', 'B', 24);
            $this->SetY(20);
            $this->Cell(0, 20, 'Reporte de Utilidad', 0, 1, 'C');
            $this->Ln(5);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetX($this->GetPageWidth() / 2 - 10);
            $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    $pdf = new PDF('L');
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(50, 168, 82);
    $pdf->SetTextColor(255);

    // Ancho total de la tabla
    $tableWidth = 20 + 60 + 75;
    $pageWidth = $pdf->GetPageWidth();
    $startX = ($pageWidth - $tableWidth) / 2;
    $pdf->SetX($startX);

    // Encabezado
    $pdf->Cell(20, 10, 'ID', 1, 0, 'C', true);
    $pdf->Cell(60, 10, 'Nombre', 1, 0, 'C', true);
    $pdf->Cell(75, 10, 'Descrcipcion', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0);

    $sql = "SELECT * FROM tbl_utilidad";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $pdf->SetX($startX);
        $pdf->Cell(20, 10, $row['id_utilidad'], 1, 0, 'C');
        $pdf->Cell(60, 10, utf8_decode($row['nombre_utilidad']), 1, 0, 'C');
        $pdf->Cell(75, 10, utf8_decode($row['descripcion']), 1, 1, 'C');
    }

 // Forzar la descarga del PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_utilidades.pdf"');
header('Cache-Control: max-age=0');

$pdf->Output('F', 'php://output'); // Enviar el PDF al navegador para descarga
exit();
?>
