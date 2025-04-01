<?php
// Sanitizar el nombre de la carpeta:
$carpetaNombre = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['nombre']); // Solo alfanuméricos
$carpetaRuta = "./descarga/" . $carpetaNombre;

// Verifica si la carpeta ya existe antes de crearla
if (!file_exists($carpetaRuta)) {
    // Crea la carpeta con permisos adecuados (por ejemplo, 0755)
    mkdir($carpetaRuta, 0755, true);
    $mensaje = "Carpeta '$carpetaNombre' creada con éxito.";
} else {
    $mensaje = "La carpeta '$carpetaNombre' ya existe.";
}

// Luego, cuando se procese un archivo, guárdalo en la carpeta creada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archivo = $_FILES['archivo'];

    // Validar extensiones permitidas:
$permitidos = ['pdf', 'docx', 'png', 'jpg', 'jpeg'];
$extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $permitidos)) {
    die("Error: Tipo de archivo no permitido.");
}
}
?>




