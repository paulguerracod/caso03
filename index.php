<?php
$carpetaNombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$carpetaRuta = "./private/descarga/" . $carpetaNombre;

try {
    if (!file_exists($carpetaRuta)) {
        mkdir($carpetaRuta, 0755, true);
        $mensaje = "Carpeta '$carpetaNombre' creada con éxito.";
    } else {
        $mensaje = "La carpeta '$carpetaNombre' ya existe.";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['archivo'])) {
            foreach ($_FILES['archivo']['tmp_name'] as $key => $tmp_name) {
                $archivo = [
                    'tmp_name' => $tmp_name,
                    'name' => $_FILES['archivo']['name'][$key],
                    'error' => $_FILES['archivo']['error'][$key],
                    'size' => $_FILES['archivo']['size'][$key],
                ];

                if ($archivo['error'] === UPLOAD_ERR_OK) {
                    if (move_uploaded_file($archivo['tmp_name'], $carpetaRuta . '/' . $archivo['name'])) {
                        $mensaje = "Archivo '{$archivo['name']}' subido con éxito.";
                    } else {
                        throw new Exception("Error al subir el archivo '{$archivo['name']}'.");
                    }
                }
            }
        }
    }

    if (isset($_POST['eliminarArchivo'])) {
        $archivoAEliminar = $_POST['eliminarArchivo'];
        $archivoRutaAEliminar = $carpetaRuta . '/' . $archivoAEliminar;

        if (file_exists($archivoRutaAEliminar)) {
            if (unlink($archivoRutaAEliminar)) {
                $mensaje = "Archivo '$archivoAEliminar' eliminado con éxito.";
            } else {
                throw new Exception("Error al eliminar el archivo '$archivoAEliminar'.");
            }
        } else {
            throw new Exception("El archivo '$archivoAEliminar' no existe.");
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
    <title>Sistema para compartir archivos</title>
    <script src="parametro.js"></script>
    <link rel="stylesheet" href="estilo.css">
    <!-- Agregar Font Awesome para los iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="content">
        <h1>Compartir Archivos</h1>
        <div class="container">
            <h3>Sube tus archivos y comparte este enlace temporal: 
                <span>tuip/<?php echo htmlspecialchars($carpetaNombre); ?></span>
            </h3>
            
            <!-- Contenedor principal flex -->
            <div class="main-grid">
                <!-- Sección de subida -->
                <div class="drop-area" id="drop-area">
                    <form action="" id="form" method="POST" enctype="multipart/form-data">
                        <img src="https://cdn.pixabay.com/photo/2016/01/03/00/43/upload-1118929_1280.png" 
                             id="img" 
                             style="width: 70px; height: 70px;"><br>
                        <input type="file" class="file-input" 
                               name="archivo[]" 
                               id="archivo" 
                               multiple 
                               onchange="document.getElementById('form').submit()">
                        <label> Click para seleccionar archivos<br>o</label>
                        <p><b>Abre el explorador</b></p>
                    </form>
                </div>

                <!-- Sección de archivos -->
                <div class="container2">
                    <div id="file-list" class="pila">
                        <?php
                        $targetDir = $carpetaRuta;
                        $files = scandir($targetDir);
                        $files = array_diff($files, array('.', '..'));

                        if (count($files) > 0) {
                            echo "<h3 style='margin-bottom:20px;'>Archivos Subidos:</h3>";
                            
                            foreach ($files as $file) {
                                $fileSafe = htmlspecialchars($file);
                                $extension = pathinfo($file, PATHINFO_EXTENSION);
                                
                                // Determinar el icono según la extensión
                                $iconClass = match(strtolower($extension)) {
                                    'pdf' => 'fa-file-pdf',
                                    'doc', 'docx' => 'fa-file-word',
                                    'xls', 'xlsx' => 'fa-file-excel',
                                    'ppt', 'pptx' => 'fa-file-powerpoint',
                                    'txt' => 'fa-file-alt',
                                    default => 'fa-file'
                                };
                                
                                echo <<<HTML
                                <div class='archivos_subidos'>
                                    <div class="file-info">
                                        <i class="far $iconClass icon-file"></i>
                                        <a href="$carpetaRuta/$fileSafe" download class='boton-descargar'>
                                            $fileSafe
                                        </a>
                                    </div>
                                    <div>
                                        <form action='' method='POST' style='display:inline;'>
                                            <input type='hidden' name='eliminarArchivo' value='$fileSafe'>
                                            <button type='submit' class='btn_delete'>
                                                <svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-trash' 
                                                     width='24' height='24' viewBox='0 0 24 24' 
                                                     stroke-width='2' stroke='currentColor' fill='none' 
                                                     stroke-linecap='round' stroke-linejoin='round'>
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
                                </div>
                                HTML;
                            }
                        } else {
                            echo "<h3 style='margin-bottom:10px;'>No hay archivos subidos:</h3>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>