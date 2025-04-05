<?php
session_start();
require_once("../fpdf/fpdf.php"); 
require_once("../conexion.php");  

// Obtener el ID de la salida desde el formulario POST
$id_salidas = isset($_POST['id_salidas']) && !empty($_POST['id_salidas']) ? $_POST['id_salidas'] : null;

if (!$id_salidas) {
    die("Error: No se proporcionó un ID de salida válido.");
}

// Consulta SQL para obtener una sola salida
$sql = "SELECT s.id_salidas, s.fecha_creacion, s.items, s.titulo, s.Destino, s.body, s.id_user, u.username 
        FROM tbl_reg_salidas s
        LEFT JOIN tbl_users u ON s.id_user = u.id_user
        WHERE s.id_salidas = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
$stmt->bind_param("i", $id_salidas); // "i" indica que es un entero
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: No se encontró la salida con el ID proporcionado.");
}

$row = $result->fetch_assoc();

// Crear el PDF con estilo de documento normal
class PDF extends FPDF {
    function Header() {
        $image_path = __DIR__ . '/../assets/img/fondopdf.jpeg';
        if (!file_exists($image_path)) {
            die("Error: La imagen de fondo no existe en $image_path");
        }

        $this->Image($image_path, 55, 80, 100, 80); // Ajustado para orientación vertical
        $this->Image(__DIR__ . '/../assets/img/logo.png', 15, 10, 25);

        $this->SetFont('Arial', 'B', 24);
        $this->SetY(20);
        $this->Cell(0, 20, 'Reporte de Salida', 0, 1, 'C');
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF('P'); // Vertical para mejor manejo de texto largo
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Función para parsear el contenido de 'body'
function parsearBody($body) {
    $categorias = [
        'Herramientas' => '',
        'Activos' => '',
        'Consumibles' => ''
    ];
    
    $partes = preg_split('/,\s*(?=[A-Za-z]+:)/', $body);
    
    foreach ($partes as $parte) {
        $parte = trim($parte);
        if (strpos($parte, 'Herr Безопасностьamientas:') === 0) {
            $categorias['Herramientas'] = trim(substr($parte, strlen('Herramientas:')));
        } elseif (strpos($parte, 'Activos:') === 0) {
            $categorias['Activos'] = trim(substr($parte, strlen('Activos:')));
        } elseif (strpos($parte, 'Consumibles:') === 0) {
            $categorias['Consumibles'] = trim(substr($parte, strlen('Consumibles:')));
        }
    }
    
    return $categorias;
}

// Mostrar los datos de la salida seleccionada
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, "Salida #" . $row['id_salidas'], 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

$pdf->Cell(0, 8, "Fecha de Creacion: " . $row['fecha_creacion'], 0, 1, 'L');
$pdf->Cell(0, 8, "Items: " . $row['items'], 0, 1, 'L');
$pdf->Cell(0, 8, "Titulo: " . utf8_decode($row['titulo']), 0, 1, 'L');
$pdf->Cell(0, 8, "Destino: " . utf8_decode($row['Destino']), 0, 1, 'L');

// Parsear y mostrar el cuerpo
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, "Cuerpo:", 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

$categorias = parsearBody($row['body']);

foreach ($categorias as $categoria => $contenido) {
    if (!empty($contenido)) {
        $texto = "$categoria: " . utf8_decode($contenido);
        $pdf->MultiCell(0, 6, $texto, 0, 'L');
        $pdf->Ln(2);
    }
}

$pdf->Cell(0, 8, "Usuario: " . utf8_decode($row['username']), 0, 1, 'L');

// Salida del PDF para descarga directa
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_salida_' . $row['id_salidas'] . '.pdf"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$pdf->Output('D', 'reporte_salida_' . $row['id_salidas'] . '.pdf');
exit();
?>