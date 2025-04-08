<?php
session_start();
header('Content-Type: application/json');

try {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Acceso no autorizado");
    }

    // Validar código
    $codigo = preg_replace('/[^a-z0-9]/', '', $_POST['codigo'] ?? '');
    if (strlen($codigo) !== 3) {
        throw new Exception("Código inválido");
    }

    // Validar archivo
    if (empty($_FILES['archivo']['tmp_name'])) {
        throw new Exception("No se recibió archivo");
    }

    // Validar tipo MIME real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['archivo']['tmp_name']);
    $permitidos = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
    ];

    if (!isset($permitidos[$mime])) {
        throw new Exception("Tipo de archivo no permitido");
    }

    // Crear directorio seguro
    $directorio = __DIR__ . "/private/descarga/$codigo";
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
        file_put_contents("$directorio/.htaccess", "Deny from all");
    }

    // Generar nombre único
    $extension = $permitidos[$mime];
    $nombreUnico = bin2hex(random_bytes(8)) . ".$extension";
    
    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], "$directorio/$nombreUnico")) {
        throw new Exception("Error al guardar archivo");
    }

    echo json_encode([
        'success' => true,
        'nombre' => $nombreUnico,
        'size' => filesize("$directorio/$nombreUnico")
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => htmlspecialchars($e->getMessage())
    ]);
    exit;
}