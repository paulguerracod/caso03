// Función para copiar enlace al portapapeles
function copiarEnlace() {
    const enlace = document.getElementById('enlaceTemporal').textContent;
    navigator.clipboard.writeText(enlace)
        .then(() => alert('Enlace copiado al portapapeles!'))
        .catch(err => console.error('Error al copiar:', err));
}

// Variables para controlar subidas múltiples
let uploadsCompletados = 0;
let totalSubidas = 0;

// Configurar evento de selección de archivos
document.getElementById('archivo').addEventListener('change', function(e) {
    const MAX_TAMANO = 50 * 1024 * 1024; // 50MB
    const TIPOS_PERMITIDOS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
    const archivos = Array.from(e.target.files);

    // Resetear contadores y UI
    uploadsCompletados = 0;
    totalSubidas = archivos.length;
    document.querySelector('.upload-status').style.display = 'block';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('statusMessage').textContent = '';

    // Validar archivos
    const archivosInvalidos = archivos.filter(archivo => {
        const extension = archivo.name.split('.').pop().toLowerCase();
        return !TIPOS_PERMITIDOS.includes(extension) || archivo.size > MAX_TAMANO;
    });

    if (archivosInvalidos.length > 0) {
        alert('Archivos no válidos:\n' + archivosInvalidos.map(a => a.name).join('\n'));
        e.target.value = '';
        return;
    }

    // Iniciar subida de todos los archivos
    archivos.forEach((archivo, indice) => {
        subirArchivo(archivo, indice + 1, archivos.length);
    });
});

// Función principal para subir archivos
function subirArchivo(archivo, indiceActual, totalArchivos) {
    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('codigo', document.querySelector('[name="codigo"]').value);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

    const xhr = new XMLHttpRequest();

    // Seguimiento del progreso
    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            const porcentaje = Math.round((e.loaded / e.total) * 100);
            actualizarUIProgreso(archivo.name, porcentaje, indiceActual, totalArchivos);
        }
    };

    // Manejar respuesta
    xhr.onload = () => {
        uploadsCompletados++;
        
        if (xhr.status === 200) {
            try {
                const respuesta = JSON.parse(xhr.responseText);
                if (respuesta.exito) {
                    agregarArchivoALista(archivo, respuesta.nombre);
                }
            } catch (error) {
                console.error('Error al procesar respuesta:', error);
            }
        }

        // Ocultar barra al finalizar todas las subidas
        if (uploadsCompletados === totalSubidas) {
            setTimeout(() => {
                document.querySelector('.upload-status').style.display = 'none';
                document.getElementById('progressBar').style.width = '0%';
            }, 2000);
        }
    };

    xhr.open('POST', 'subir.php', true);
    xhr.send(formData);
}

// Actualizar interfaz de progreso
function actualizarUIProgreso(nombreArchivo, porcentaje, indiceActual, totalArchivos) {
    document.getElementById('progressBar').style.width = `${porcentaje}%`;
    document.getElementById('statusMessage').innerHTML = `
        <strong>Subiendo:</strong> ${nombreArchivo}<br>
        <strong>Progreso:</strong> ${porcentaje}%<br>
        Archivo ${indiceActual} de ${totalArchivos}
    `;
}

// Agregar archivo a la lista visual
function agregarArchivoALista(archivoOriginal, nombreServidor) {
    const extension = archivoOriginal.name.split('.').pop().toLowerCase();
    const icono = obtenerIcono(extension);
    
    const nuevoElemento = document.createElement('div');
    nuevoElemento.className = 'archivos_subidos';
    nuevoElemento.innerHTML = `
        <div class="file-info">
            <i class="far ${icono}"></i>
            <a href="descargar.php?codigo=${encodeURIComponent(document.querySelector('[name="codigo"]').value)}&file=${encodeURIComponent(nombreServidor)}" download>
                ${archivoOriginal.name}
            </a>
        </div>
        <div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="${document.querySelector('[name="csrf_token"]').value}">
                <input type="hidden" name="codigo" value="${document.querySelector('[name="codigo"]').value}">
                <input type="hidden" name="eliminarArchivo" value="${nombreServidor}">
                <button type="submit" class="btn_delete">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        </div>
    `;

    document.getElementById('file-list').prepend(nuevoElemento);
}

// Mapear extensiones a íconos FontAwesome
function obtenerIcono(extension) {
    const iconos = {
        'pdf': 'fa-file-pdf',
        'doc': 'fa-file-word',
        'docx': 'fa-file-word',
        'xls': 'fa-file-excel',
        'xlsx': 'fa-file-excel',
        'ppt': 'fa-file-powerpoint',
        'pptx': 'fa-file-powerpoint',
        'jpg': 'fa-file-image',
        'jpeg': 'fa-file-image',
        'png': 'fa-file-image'
    };
    return iconos[extension] || 'fa-file';
}