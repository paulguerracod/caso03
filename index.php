<?php
// INICIO DEL ARCHIVO
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$carpetaNombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$carpetaRuta = "../private/descarga/" . $carpetaNombre;

try {
    if (!file_exists($carpetaRuta)) {
        mkdir($carpetaRuta, 0755, true);
        $mensaje = "Carpeta '$carpetaNombre' creada con éxito.";
    } else {
        $mensaje = "La carpeta '$carpetaNombre' ya existe.";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Error de seguridad: Token CSRF inválido.");

            if (move_uploaded_file($archivo['tmp_name'], $carpetaRuta . '/' . $archivo['name'])) {
                $subido = true;
                $mensaje = "Archivo subido con éxito.";
            } else {
                throw new Exception("Error al subir el archivo.");
            }
        }
    }

    // Modificar el bloque de eliminación:
if (isset($_POST['eliminarArchivo'])) {
    $archivoAEliminar = basename($_POST['eliminarArchivo']); // Solo nombre, sin rutas
    $archivoRutaAEliminar = $carpetaRuta . '/' . $archivoAEliminar;
    
    // Verificar que el archivo exista y esté dentro de la carpeta permitida
    if (file_exists($archivoRutaAEliminar) && strpos(realpath($archivoRutaAEliminar), realpath("./descarga/")) === 0) {
        unlink($archivoRutaAEliminar);
    }
}
} catch (Exception $e) {
    $mensaje = "Error: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compartir archivos</title>
    <script src="parametro.js"></script>
    <link rel="stylesheet" href="estilo.css">
    <style>
        /* Estilos base responsive */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            padding: 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        /* Drop zone */
        .drop-zone {
            background: white;
            border: 2px dashed #4a90e2;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .drop-zone.dragover {
            border-color: #2ecc71;
            background: #f8f9ff;
        }

        /* Progress bar */
        .progress-container {
            width: 100%;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            margin: 1rem 0;
            display: none;
        }

        .progress-bar {
            height: 100%;
            background: #4a90e2;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* File list */
        .file-list {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin: 0.5rem 0;
            background: #f8f9fa;
            border-radius: 8px;
            animation: fadeIn 0.5s ease;
        }

        .file-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            min-width: 40px;
        }

        .file-info {
            flex-grow: 1;
            overflow: hidden;
        }

        .file-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
        }

        .file-size {
            color: #666;
            font-size: 0.8rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .drop-zone {
                min-height: 200px;
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <h1>Compartir archivos <sup class="beta">BETA</sup></h1>
    <div class="content">
        <h3>Sube tus archivos y comparte este enlace temporal: <span>ibu.pe/?nombre=<?php echo $carpetaNombre;?></span></h3>
        <div class="container">
            <div class="drop-zone" id="dropZone">
            <i class="fas fa-cloud-upload-alt fa-3x" style="color: #4a90e2; margin-bottom: 1rem;"></i>
            <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" style="fill:#0730c5;transform: ;msFilter:;"><path d="M13 19v-4h3l-4-5-4 5h3v4z"></path><path d="M7 19h2v-2H7c-1.654 0-3-1.346-3-3 0-1.404 1.199-2.756 2.673-3.015l.581-.102.192-.558C8.149 8.274 9.895 7 12 7c2.757 0 5 2.243 5 5v1h1c1.103 0 2 .897 2 2s-.897 2-2 2h-3v2h3c2.206 0 4-1.794 4-4a4.01 4.01 0 0 0-3.056-3.888C18.507 7.67 15.56 5 12 5 9.244 5 6.85 6.611 5.757 9.15 3.609 9.792 2 11.82 2 14c0 2.757 2.243 5 5 5z"></path></svg> <br>
                    <input type="file" class="file-input" name="archivo" id="archivo" onchange="document.getElementById('form').submit()">
                    <label> Arrastra tus archivos aquí<br>o</label>
                    <p><b>Abre el explorador</b></p> 
                    <input type="file" id="fileInput" hidden>

                    <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            
            <div id="uploadStatus"></div>
        </div>

        <div class="file-list" id="fileList">
            <h3>Archivos Subidos</h3>
            <div id="fileItems"></div>
        </div>
    </div>    
                </form>
            </div>

            <div class="container2">
               

                <div id="file-list" class="pila">
                    <?php
                    $targetDir = $carpetaRuta;

                    $files = scandir($targetDir);
                    $files = array_diff($files, array('.', '..'));

                    if (count($files) > 0) {
                        echo "<h3 style='margin-bottom:10px;'>Archivos Subidos:</h3>";
                    
                        foreach ($files as $file) {
                            echo "<div class='archivos_subidos'>
                                    <div><a href='$carpetaRuta/$file' download class='boton-descargar'>$file</a></div>
                                    <div>
                                        <form action='' method='POST' style='display:inline;'>
                                            <input type='hidden' name='csrf_token' value='" . $_SESSION['csrf_token'] . "'>
                                            <input type='hidden' name='eliminarArchivo' value='$file'>
                                            <button type='submit' class='btn_delete'>
                                                <svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-trash' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' fill='none' stroke-linecap='round' stroke-linejoin='round'>
                                                    <path stroke='none' d='M0 0h24v24H0z' fill='none'/>
                                                    <path d='M4 7l16 0' />
                                                    <path d='M10 11l0 6' />
                                                    <path d='M14 11l0 6' />
                                                    <path d='M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12' />
                                                    <path d='M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3' />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                  </div>";
                        }
                    } else {
                        echo "No se han subido archivos.";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script>
// Configuración inicial
const fileInput = document.getElementById('fileInput');
const dropZone = document.getElementById('dropZone');
const progressBar = document.getElementById('progressBar');
const progressContainer = document.querySelector('.progress-container');
const fileItems = document.getElementById('fileItems');


    <!-- <script src="parametro.js"></script> -->

</body>

</html>