<?php
require('../fpdf/fpdf.php');
require('../conexion.php');

if (isset($_POST['generar_pdf'])) {
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
            $this->Cell(0, 20, 'Reporte de Consumibles', 0, 1, 'C');
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
    $tableWidth = 15 + 50 + 25 + 20 + 20 + 25 + 60 + 25;
    $pageWidth = $pdf->GetPageWidth();
    $startX = ($pageWidth - $tableWidth) / 2;
    $pdf->SetX($startX);

    // Encabezado
    $pdf->Cell(15, 10, 'ID', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Nombre', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Cant.', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'Emp.', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'Est.', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Uti.', 1, 0, 'C', true);
    $pdf->Cell(60, 10, 'Ingreso', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Tecnico', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0);

    $sql = "SELECT * FROM tbl_consumibles";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $pdf->SetX($startX);
        $pdf->Cell(15, 10, $row['id_consumibles'], 1, 0, 'C');
        $pdf->Cell(50, 10, utf8_decode($row['nombre_consumibles']), 1, 0, 'C');
        $pdf->Cell(25, 10, $row['cantidad_consumibles'], 1, 0, 'C');
        $pdf->Cell(20, 10, $row['id_empresa'], 1, 0, 'C');
        $pdf->Cell(20, 10, $row['estado_consumibles'], 1, 0, 'C');
        $pdf->Cell(25, 10, $row['utilidad_consumibles'], 1, 0, 'C');
        $pdf->Cell(60, 10, $row['fecha_ingreso'], 1, 0, 'C');
        $pdf->Cell(25, 10, $row['id_tecnico'], 1, 1, 'C');
    }

    $pdf->Output();
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar PDF</title>
</head>
<body>
    <h2>Generar PDF de Consumibles</h2>
    <form method="post">
        <button type="submit" name="generar_pdf">Generar PDF</button>
    </form>
</body>
</html>