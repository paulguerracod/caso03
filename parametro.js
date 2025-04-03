// parametro.js - Modificaciones necesarias

// 1. Definir los iconos fuera de las funciones (en el ámbito global)
const fileIcons = {
    pdf: 'fa-ns far fa-file-pdf',       // Agregar 'fa-ns'
    doc: 'fa-ns far fa-file-word',
    docx: 'fa-ns far fa-file-word',
    ppt: 'fa-ns far fa-file-powerpoint',
    pptx: 'fa-ns far fa-file-powerpoint',
    xls: 'fa-ns far fa-file-excel',
    xlsx: 'fa-ns far fa-file-excel',
    txt: 'fa-ns far fa-file-alt',
    default: 'fa-ns far fa-file'       // Namespace en todos
};

// 2. Función para formatear el tamaño del archivo (debe existir)
function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while(size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

// 3. Reemplazar la función fetchUploadedFiles existente
function fetchUploadedFiles() {
    // Obtener el nombre de la carpeta desde el HTML
    const urlParams = new URLSearchParams(window.location.search);
    const carpetaNombre = urlParams.get('nombre');
    
    fetch(`get_files.php?nombre=${encodeURIComponent(carpetaNombre)}`)
        .then(response => {
            if(!response.ok) throw new Error('Error en la red');
            return response.json();
        })
        .then(data => {
            if(data.success) {
                fileItems.innerHTML = '';
                data.data.forEach(file => {
                    createFileItem(file.nombre_original, file.size, file.tipo);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// 4. Añadir la nueva función createFileItem
function createFileItem(name, size, type) {
    const ext = type.toLowerCase();
    const icon = fileIcons[ext] || fileIcons.default;
    
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';
    fileItem.innerHTML = `
        <i class="${icon} file-icon"></i>
        <div class="file-info">
            <div class="file-name">${name}</div>
            <div class="file-size">${formatFileSize(size)}</div>
        </div>
    `;
    
    fileItems.prepend(fileItem);
}

// 5. Modificar la función uploadFile para actualizar la lista después de subir
function uploadFile(file) {
    const formData = new FormData();
    formData.append('archivo', file);
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.onprogress = (e) => {
        if(e.lengthComputable) {
            const percent = (e.loaded / e.total) * 100;
            progressBar.style.width = `${percent}%`;
            progressContainer.style.display = 'block';
        }
    };
    
    xhr.onload = () => {
        progressContainer.style.display = 'none';
        if(xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if(response.success) {
                    createFileItem(response.file.nombre_original, response.file.size, response.file.tipo);
                }
            } catch(e) {
                console.error('Error parsing response:', e);
            }
        }
    };
    
    xhr.open('POST', 'subir.php');
    xhr.send(formData);
}

// 6. Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Obtener referencias a los elementos del DOM
    const fileItems = document.getElementById('fileItems');
    const progressBar = document.getElementById('progressBar');
    const progressContainer = document.querySelector('.progress-container');
    
    // Cargar archivos existentes
    fetchUploadedFiles();
});