<?php
// Generar código único de 3 caracteres (ej: gfr, a3b)
function generarCodigo() {
    $caracteres = 'abcdefghijklmnopqrstuvwxyz0123456789';
    return substr(str_shuffle($caracteres), 0, 3);
}

$codigo = isset($_GET['codigo']) && preg_match('/^[a-z0-9]{3}$/', $_GET['codigo']) 
    ? $_GET['codigo'] 
    : generarCodigo();

$carpetaNombre = $codigo;
$carpetaRuta = "./private/descarga/" . $carpetaNombre;

// Redirigir si no existe el código en la URL
if(!isset($_GET['codigo'])) {
    header("Location: ?codigo=$codigo");
    exit;
}

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

// Definir ruta de forma segura
$codigo = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
$carpetaRuta = __DIR__ . "/private/descarga/" . $codigo;

// Verificar y listar archivos
if (is_dir($carpetaRuta)) {
    $files = array_diff(scandir($carpetaRuta), ['.', '..', '.htaccess']);
} else {
    $files = [];
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
    <br><h1>Compartir Archivos
        <sup class="beta">BETA</sup>
        </h1></br>
    <div class="content">
    <br><h3>Sube tus archivos y comparte este enlace temporal: 
        <span id="enlaceTemporal">ibu.pe/<?php echo htmlspecialchars($codigo); ?></span>
            </h3></br>
        <div class="container">
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
                         <!-- Barra de progreso -->
                          <div class="upload-status">
                            <div class="progress-container">
                                <div class="progress-bar" id="progressBar"></div>
                            </div>
                            <div class="status-message" id="statusMessage"></div>
                        </div>
                    </form>
                </div>

                <!-- Sección de archivos -->
                <div class="container2">
                    <div id="file-list" class="pila">
                    <?php if (!empty($files)): ?>
                        <h3 style="margin-bottom:10px;">Archivos Subidos:</h3>
                            <?php foreach ($files as $file): 
                            $fileSafe = htmlspecialchars($file, ENT_QUOTES);
                            $fileUrl = "descargar.php?codigo=$codigo&file=" . urlencode($file);
                                
                                // Determinar el icono según la extensión
                                $iconClass = match(strtolower($extension)) {
                                    'pdf' => 'fa-file-pdf',
                                    'doc', 'docx' => 'fa-file-word',
                                    'xls', 'xlsx' => 'fa-file-excel',
                                    'ppt', 'pptx' => 'fa-file-powerpoint',
                                    'txt' => 'fa-file-alt',
                                    default => 'fa-file'
                                };
                                ?>
                                <div class="archivos_subidos">
                                    <div>
                                        <a href="<?= $fileUrl ?>" download class="boton-descargar">
                                            <?= $fileSafe ?>
                                        </a>
                                    </div>
                                    <div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="eliminarArchivo" value="<?= $fileSafe ?>">
                            <button type="submit" class="btn_delete">
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
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                            <h3 style="margin-bottom:10px;">No hay archivos subidos</h3>
                                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>