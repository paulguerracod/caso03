var uploadsCompletados = 0;
var totalSubidas = 0;

document.getElementById('archivo').addEventListener('change', function(e) {
    const MAX_TAMANO = 50 * 1024 * 1024;
    const TIPOS_PERMITIDOS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
    const archivos = Array.from(e.target.files);

    uploadsCompletados = 0;
    totalSubidas = archivos.length;
    document.querySelector('.upload-status').style.display = 'block';
    document.getElementById('progressBar').style.width = '0%';

    const archivosInvalidos = archivos.filter(archivo => {
        const extension = archivo.name.split('.').pop().toLowerCase();
        return !TIPOS_PERMITIDOS.includes(extension) || archivo.size > MAX_TAMANO;
    });

    if (archivosInvalidos.length > 0) {
        alert('Archivos no válidos:\n' + archivosInvalidos.map(a => a.name).join('\n'));
        e.target.value = '';
        return;
    }

    archivos.forEach((archivo, indice) => {
        subirArchivo(archivo, indice + 1, archivos.length);
    });
});

function subirArchivo(archivo, indiceActual, totalArchivos) {
    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('codigo', document.querySelector('[name="codigo"]').value);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

    const xhr = new XMLHttpRequest();

    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            const porcentaje = Math.round((e.loaded / e.total) * 100);
            actualizarUIProgreso(archivo.name, porcentaje, indiceActual, totalArchivos);
        }
    };

    xhr.onload = () => {
        uploadsCompletados++;
        
        if (xhr.status === 200) {
            try {
                const respuesta = JSON.parse(xhr.responseText);
                if (respuesta.success) { // <-- Usar 'success' aquí
                    agregarArchivoALista(archivo, respuesta.nombre);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

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

function actualizarUIProgreso(nombreArchivo, porcentaje, indiceActual, totalArchivos) {
    document.getElementById('progressBar').style.width = `${porcentaje}%`;
    document.getElementById('statusMessage').innerHTML = `
        <strong>Subiendo:</strong> ${nombreArchivo}<br>
        <strong>Progreso:</strong> ${porcentaje}%<br>
        Archivo ${indiceActual} de ${totalArchivos}
    `;
}

function agregarArchivoALista(archivoOriginal, nombreServidor) {
    const lista = document.getElementById('file-list');
    // Eliminar mensaje de "No hay archivos" si existe
    if (lista.querySelector('h3')) {
        lista.innerHTML = '';
    }

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

    lista.prepend(nuevoElemento);
}

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