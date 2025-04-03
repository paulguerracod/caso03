<?php
header('Content-Type: application/json');

try {
    // Validar tamaño antes de procesar
    $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
    $maxSize = 500 * 1024 * 1024; // 500MB
    
    if($contentLength > $maxSize) {
        throw new Exception("Superas el límite de 500MB");
    }

    // Validar carpeta
    $carpetaNombre = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_POST['carpeta'] ?? '');
    if(empty($carpetaNombre)) {
        throw new Exception("Nombre inválido");
    }
    
    $carpetaRuta = __DIR__ . "/private/descarga/" . $carpetaNombre;
    if(!is_dir($carpetaRuta)) {
        mkdir($carpetaRuta, 0755, true);
    }

    // Validar archivo
    if(empty($_FILES['archivo'])) {
        throw new Exception("No se recibió archivo");
    }

    $archivo = $_FILES['archivo'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'txt'];

    if(!in_array($extension, $allowedExtensions)) {
        throw new Exception("Tipo de archivo no permitido");
    }

    // Generar nombre único
    $nombreFinal = bin2hex(random_bytes(8)) . '.' . $extension;
    $rutaFinal = $carpetaRuta . '/' . $nombreFinal;

    if(move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
        echo json_encode([
            'success' => true,
            'file' => [
                'nombre' => $nombreFinal,
                'size' => $archivo['size']
            ]
        ]);
    } else {
        throw new Exception("Error al guardar archivo");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}