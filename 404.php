<?php
header("HTTP/1.0 404 Not Found");
header("HTTP/1.0 404 Not Found");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <style>
        /* Resetear márgenes y establecer altura completa */
        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Evita barras de desplazamiento */
        }

        /* Contenedor principal */
        .container {
            display: flex;
            justify-content: center; /* Centra horizontalmente */
            align-items: center; /* Centra verticalmente */
            height: 100vh; /* Altura exacta de la ventana */
            width: 100vw; /* Ancho exacto de la ventana */
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Formulario */
        .form-container {
            background-color: #AABF70;
            padding: 24px;
            border-radius: 0; /* Quitamos bordes redondeados para que ocupe todo */
            box-shadow: none; /* Opcional: quitar sombra si ocupa toda la pantalla */
            width: 100%; /* Ocupa todo el ancho */
            height: 100%; /* Ocupa todo el alto */
            text-align: center; /* Alinea el contenido al centro */
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        /* Contenido interno del formulario */
        .form-inner {
            background-color: #D9D2B0;
            padding: 24px;
            border-radius: 0; /* Sin bordes redondeados para llenar todo */
            text-align: center; /* Alinea el texto al centro */
            display: flex;
            flex-direction: column;
            justify-content: center; /* Centra verticalmente los elementos */
            align-items: center; /* Centra horizontalmente los elementos */
            gap: 24px; /* Espacio entre los elementos */
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            margin: 0;
        }

        /* Estilo para el título y párrafo */
        h1 {
            color: #333;
            font-size: 2.5rem;
            margin: 0;
        }

        p {
            color: #666;
            font-size: 1.2rem;
            margin: 0;
        }

        /* Contenedor de la imagen */
        .image-container {
            position: relative;
            display: inline-block;
        }

        .image-container img {
            width: 300px; /* Puedes ajustar este valor según necesites */
            height: auto;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.3));
        }

        /* Contenedor de botones */
        .button-container {
            display: flex;
            justify-content: center; /* Centra los botones horizontalmente */
            gap: 16px; /* Espacio entre los botones */
        }

        /* Estilo de los botones */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none; /* Para el enlace <a> */
            display: inline-block; /* Para que el enlace respete el padding */
        }

        .btn-danger {
            background-color: #E74C3C;
            color: white;
        }

        .btn-danger:hover {
            background-color: #5e3535;
        }

        .btn-success {
            background-color: #53A670;
            color: white;
        }

        .btn-success:hover {
            background-color: #1C4029;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-inner">
                <div class="image-container">
                    <img src="/Inventario/assets/img/lagarto_triste.png" alt="Lagarto triste">
                </div>
                <h1>404 - Página no encontrada</h1>
                <p>Lo sentimos, la página que estás buscando no existe o ha sido movida.</p>
                <div class="button-container">
                    <a href="/Inventario/login.php" class="btn btn-success">Volver a la página principal</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>