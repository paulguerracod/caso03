<?php
session_start();
header('Content-Type: application/json');

try {
    // 1. Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Token inválido");
    }

    // 2. Validar código
    $codigo = preg_replace('/[^a-z0-9]/', '', $_POST['codigo'] ?? '');
    if (strlen($codigo) !== 3) {
        throw new Exception("Código inválido");
    }

    // 3. Validar archivo
    if (empty($_FILES['archivo']['tmp_name'])) {
        throw new Exception("Archivo no recibido");
    }

    // 4. Validar tipo real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['archivo']['tmp_name']);
    $permitidos = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    if (!in_array($mime, $permitidos)) {
        throw new Exception("Tipo de archivo no permitido");
    }

    // 5. Crear directorio seguro
    $directorio = __DIR__ . "/private/descarga/$codigo";
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
        file_put_contents("$directorio/.htaccess", "Deny from all");
    }

    // 6. Generar nombre único
    $extension = array_search($mime, [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
    ]);

    $nombreUnico = bin2hex(random_bytes(8)) . ".$extension";
    $rutaFinal = "$directorio/$nombreUnico";

    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaFinal)) {
        throw new Exception("Error al guardar archivo");
    }

    echo json_encode([
        'success' => true,
        'nombre' => $nombreUnico,
        'size' => filesize($rutaFinal)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}