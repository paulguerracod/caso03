<?php
session_start();
header('Content-Type: application/json');

// ==================== CONFIGURACIÓN SEGURA ====================
$CONFIG = [
    'MAX_FILE_SIZE' => 100 * 1024 * 1024, // 100 MB
    'ALLOWED_TYPES' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg'],
    'UPLOAD_DIR' => realpath(__DIR__ . '/../private/descarga') . '/'
];

// ==================== VALIDACIONES INICIALES ====================
try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido", 405);
    }

    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Token CSRF inválido", 403);
    }

    // Validar parámetro 'nombre'
    if (!isset($_GET['nombre']) || !preg_match('/^[a-zA-Z0-9\-_]+$/', $_GET['nombre'])) {
        throw new Exception("Nombre de carpeta inválido", 400);
    }

    // Validar archivo subido
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo", 400);
    }

    // ==================== PROCESAMIENTO SEGURO ====================
    $file = $_FILES['archivo'];
    
    // Sanitizar nombre del archivo
    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Validar tipo de archivo
    if (!in_array($extension, $CONFIG['ALLOWED_TYPES'])) {
        throw new Exception("Tipo de archivo no permitido: .$extension", 415);
    }

    // Validar tamaño
    if ($file['size'] > $CONFIG['MAX_FILE_SIZE']) {
        throw new Exception("Archivo demasiado grande. Máximo: " . formatBytes($CONFIG['MAX_FILE_SIZE']), 413);
    }

    // Crear directorio seguro
    $carpetaNombre = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['nombre']);
    $carpetaRuta = $CONFIG['UPLOAD_DIR'] . $carpetaNombre;
    
    if (!is_dir($carpetaRuta)) {
        mkdir($carpetaRuta, 0755, true);
    }

    // Generar nombre único
    $nombreArchivo = bin2hex(random_bytes(16)) . ".$extension";
    $rutaFinal = $carpetaRuta . '/' . $nombreArchivo;

    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $rutaFinal)) {
        throw new Exception("Error al guardar el archivo", 500);
    }

    // ==================== RESPUESTA EXITOSA ====================
    echo json_encode([
        'success' => true,
        'file' => [
            'nombre' => $nombreArchivo,
            'tipo' => $extension,
            'size' => filesize($carpetaRuta . '/' . $nombreArchivo),
            'nombre_original' => $_FILES['archivo']['name'] // Agregar
        ]
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ==================== FUNCIONES AUXILIARES ====================
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function getFileIcon($ext) {
    $icons = [
        'pdf' => 'fas fa-file-pdf',
        'doc' => 'fas fa-file-word',
        'docx' => 'fas fa-file-word',
        'ppt' => 'fas fa-file-powerpoint',
        'pptx' => 'fas fa-file-powerpoint',
        'xls' => 'fas fa-file-excel',
        'xlsx' => 'fas fa-file-excel',
        'txt' => 'fas fa-file-alt',
        'png' => 'fas fa-file-image',
        'jpg' => 'fas fa-file-image',
        'jpeg' => 'fas fa-file-image'
    ];
    return $icons[$ext] ?? 'fas fa-file';
}