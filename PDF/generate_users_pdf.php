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
            $this->Cell(0, 20, 'Reporte de Usuarios', 0, 1, 'C');
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
    $tableWidth = 10 + 20 + 25 + 20 + 25 + 15 + 40 + 25 + 40 + 40;
    $pageWidth = $pdf->GetPageWidth();
    $startX = ($pageWidth - $tableWidth) / 2;
    $pdf->SetX($startX);

    // Encabezado
    $pdf->Cell(10, 10, 'ID', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'Nombre', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Apellidos', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'Usuario', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Password', 1, 0, 'C', true);
    $pdf->Cell(15, 10, 'Rol', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Correo', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Telefono', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Creacion', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'Modificacion', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0);

    $sql = "SELECT * FROM tbl_users";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $pdf->SetX($startX);
        $pdf->Cell(10, 10, $row['id_user'], 1, 0, 'C');
        $pdf->Cell(20, 10, utf8_decode($row['nombre']), 1, 0, 'C');
        $pdf->Cell(25, 10, utf8_decode($row['apellidos']), 1, 0, 'C');
        $pdf->Cell(20, 10, $row['username'], 1, 0, 'C');
        $pdf->Cell(25, 10, $row['password'], 1, 0, 'C');
        $pdf->Cell(15, 10, $row['role'], 1, 0, 'C');
        $pdf->Cell(40, 10, $row['correo'], 1, 0, 'C');
        $pdf->Cell(25, 10, $row['telefono'], 1, 0, 'C');
        $pdf->Cell(40, 10, $row['fecha_creacion'], 1, 0, 'C');
        $pdf->Cell(40, 10, $row['fecha_modificacion'], 1, 1, 'C');
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
    <h2>Generar PDF de Usuarios</h2>
    <form method="post">
        <button type="submit" name="generar_pdf">Generar PDF</button>
    </form>
</body>
</html>