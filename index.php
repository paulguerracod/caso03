<?php
session_start();

// Generar código único de 3 caracteres
function generarCodigo() {
    $caracteres = 'abcdefghjkmnpqrstuvwxyz23456789';
    do {
        $codigo = substr(str_shuffle($caracteres), 0, 3);
        $ruta = __DIR__ . "/private/descarga/$codigo";
    } while (file_exists($ruta));
    return $codigo;
}

// Manejar código existente o nuevo
$codigo = isset($_GET['codigo']) && preg_match('/^[a-z0-9]{3}$/', $_GET['codigo']) 
    ? $_GET['codigo'] 
    : generarCodigo();

// Redirigir para fijar código en URL
if (!isset($_GET['codigo'])) {
    header("Location: ?codigo=$codigo");
    exit;
}

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Procesar eliminación de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminarArchivo'])) {
    try {
        // Validar CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Acceso no autorizado");
        }

        // Validar y sanitizar entrada
        $archivo = basename($_POST['eliminarArchivo']);
        $codigo = $_POST['codigo'];
        
        // Construir rutas seguras
        $directorioBase = realpath(__DIR__ . "/private/descarga/$codigo");
        $rutaArchivo = realpath("$directorioBase/$archivo");

        // Verificar rutas válidas
        if (!$rutaArchivo || strpos($rutaArchivo, $directorioBase) !== 0) {
            throw new Exception("Ruta inválida");
        }

        // Eliminar archivo
        if (file_exists($rutaArchivo)) {
            if (!unlink($rutaArchivo)) {
                throw new Exception("Error al eliminar archivo");
            }
            // Redirigir para evitar reenvío de formulario
            header("Location: ?codigo=$codigo");
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener archivos existentes
$directorio = realpath(__DIR__ . "/private/descarga/$codigo");
$archivos = is_dir($directorio) 
    ? array_diff(scandir($directorio), ['.', '..', '.htaccess']) 
    : [];
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
                <div class="drop-area" id="drop-area">
                    <form action="" id="form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                        <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
                        
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
                        
                        <div class="upload-status">
                            <div class="progress-container">
                                <div class="progress-bar" id="progressBar"></div>
                            </div>
                            <div class="status-message" id="statusMessage"></div>
                        </div>
                    </form>
                </div>

                <div class="container2">
                    <div id="file-list" class="pila">
                        <?php if (!empty($archivos)): ?>
                            <h3 style="margin-bottom:10px;">Archivos Subidos:</h3>
                            <?php foreach ($archivos as $archivo): 
                                $fileInfo = pathinfo($archivo);
                                $extension = $fileInfo['extension'] ?? 'desconocido';
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
                                    <div class="file-info">
                                        <i class="far <?= $iconClass ?>"></i>
                                        <a href="descargar.php?codigo=<?= $codigo ?>&file=<?= urlencode($archivo) ?>" download>
                                            <?= htmlspecialchars($archivo, ENT_QUOTES) ?>
                                        </a>
                                    </div>
                                    <div>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="codigo" value="<?= $codigo ?>">
                                            <input type="hidden" name="eliminarArchivo" value="<?= htmlspecialchars($archivo, ENT_QUOTES) ?>">
                                            <button type="submit" class="btn_delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" 
                                                     width="24" height="24" viewBox="0 0 24 24" 
                                                     stroke-width="2" stroke="currentColor" fill="none" 
                                                     stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
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