// ----- inicio  -----
const MAX_FILE_SIZE_MB = 500;
const MAX_TOTAL_SIZE_MB = 500;

// Función mejorada para subir archivos
function uploadFile(file) {
    const progressBar = document.getElementById('progressBar');
    const progressContainer = document.querySelector('.progress-container');
    const statusMessage = document.getElementById('statusMessage');
    
    // Resetear estado
    progressBar.style.width = '0%';
    progressBar.classList.remove('error');
    progressContainer.classList.add('active');
    statusMessage.textContent = 'Preparando subida...';

    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('carpeta', carpetaNombre);

    const xhr = new XMLHttpRequest();
    
    // Seguimiento del progreso
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percent = (e.loaded / e.total) * 100;
            progressBar.style.width = percent + '%';
            progressBar.classList.add('uploading');
            statusMessage.textContent = `Subiendo: ${Math.round(percent)}% (${formatFileSize(e.loaded)} de ${formatFileSize(e.total)})`;
        }
    });

    xhr.onload = function() {
        progressBar.classList.remove('uploading');
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    progressBar.style.width = '100%';
                    statusMessage.innerHTML = `
                        <span style="color:#2ecc71">✓ Subido exitosamente!</span>
                        <br>${file.name} (${formatFileSize(file.size)})
                    `;
                    setTimeout(() => {
                        progressContainer.classList.remove('active');
                        fetchUploadedFiles();
                    }, 2000);
                }
            } catch (e) {
                handleUploadError('Error al procesar la respuesta', file);
            }
        } else {
            handleUploadError(`Error ${xhr.status}: ${xhr.statusText}`, file);
        }
    };

    xhr.onerror = function() {
        handleUploadError('Error de conexión', file);
    };

    xhr.open('POST', 'subir.php', true);
    xhr.send(formData);
}

// Función para manejar errores
function handleUploadError(message, file) {
    const progressBar = document.getElementById('progressBar');
    const statusMessage = document.getElementById('statusMessage');
    
    progressBar.classList.add('error');
    progressBar.style.width = '100%';
    statusMessage.innerHTML = `
        <span style="color:#e74c3c">✗ Error en ${file.name}:</span>
        <br>${message}
    `;
    
    setTimeout(() => {
        document.querySelector('.progress-container').classList.remove('active');
    }, 5000);
}

// ----- Modificar el evento 'change' del input -----
document.getElementById('archivo').addEventListener('change', function(e) {
    const files = e.target.files;
    const uploadStatus = document.querySelector('.upload-status');
    const statusMessage = document.getElementById('statusMessage');
    
    // Validación de tamaño
    let totalSize = 0;
    Array.from(files).forEach(file => {
        if(file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
            showUploadError(`Archivo demasiado grande: ${file.name} (Máximo ${MAX_FILE_SIZE_MB}MB)`);
            e.target.value = '';
            return;
        }
        totalSize += file.size;
    });

    if(totalSize > MAX_TOTAL_SIZE_MB * 1024 * 1024) {
        showUploadError(`Tamaño total excede ${MAX_TOTAL_SIZE_MB}MB`);
        e.target.value = '';
        return;
    }

    // Si pasa validaciones
    if(files.length > 0) {
        uploadStatus.classList.add('active');
        Array.from(files).forEach((file, index) => {
            uploadFile(file, index + 1, files.length);
        });
    }
});