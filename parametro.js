// ----- inicio  -----
const MAX_FILE_SIZE_MB = 500;
const MAX_TOTAL_SIZE_MB = 500;

// Función uploadFile actualizada
function uploadFile(file) {
    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('codigo', document.querySelector('[name="codigo"]').value);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

    const xhr = new XMLHttpRequest();
    
    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const percent = (e.loaded / e.total) * 100;
            document.getElementById('progressBar').style.width = `${percent}%`;
        }
    };

    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    // Actualizar lista sin recargar
                    const codigo = encodeURIComponent(document.querySelector('[name="codigo"]').value);
                    const nombreArchivo = encodeURIComponent(response.nombre);
                    
                    const newFile = document.createElement('div');
                    newFile.className = 'archivos_subidos';
                    newFile.innerHTML = `
                        <div>
                            <a href="descargar.php?codigo=${codigo}&file=${nombreArchivo}" download>
                                ${file.name}
                            </a>
                        </div>
                        <!-- Botón eliminar -->
                    `;
                    document.getElementById('file-list').prepend(newFile);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        }
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