document.getElementById('archivo').addEventListener('change', function(e) {
    const MAX_TAMANO = 50 * 1024 * 1024; // 50MB
    const TIPOS_PERMITIDOS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
    const archivos = Array.from(e.target.files);
    
    // Limpiar contenedor
    const lista = document.getElementById('file-list');
    lista.innerHTML = '<h3 style="margin-bottom:10px;">Archivos Subidos:</h3>';
    
    // Mostrar archivos seleccionados
    archivos.forEach(archivo => {
        const item = document.createElement('div');
        item.className = 'archivos_subidos';
        item.innerHTML = `
            <div class="file-info">
                <i class="far ${obtenerIcono(archivo.name.split('.').pop().toLowerCase())}"></i>
                <span>${archivo.name}</span>
                <span class="estado">(Pendiente)</span>
            </div>
        `;
        lista.appendChild(item);
    });

    // Validar archivos
    const archivosInvalidos = archivos.filter(archivo => {
        const extension = archivo.name.split('.').pop().toLowerCase();
        return !TIPOS_PERMITIDOS.includes(extension) || archivo.size > MAX_TAMANO;
    });

    if (archivosInvalidos.length > 0) {
        alert('Archivos no válidos:\n' + archivosInvalidos.map(a => a.name).join('\n'));
        e.target.value = '';
        lista.innerHTML = '<h3 style="margin-bottom:10px;">No hay archivos subidos</h3>';
        return;
    }

    // Iniciar subida
    document.querySelector('.upload-status').style.display = 'block';
    archivos.forEach((archivo, indice) => {
        subirArchivo(archivo, indice);
    });
});

function subirArchivo(archivo, indice) {
    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('codigo', document.querySelector('[name="codigo"]').value);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

    const xhr = new XMLHttpRequest();
    const items = document.querySelectorAll('.archivos_subidos');

    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            const porcentaje = Math.round((e.loaded / e.total) * 100);
            items[indice].querySelector('.estado').textContent = `(${porcentaje}% subido)`;
        }
    };

    xhr.onload = () => {
        if (xhr.status === 200) {
            const respuesta = JSON.parse(xhr.responseText);
            if (respuesta.success) {
                items[indice].innerHTML = `
                    <div class="file-info">
                        <i class="far ${obtenerIcono(archivo.name.split('.').pop().toLowerCase())}"></i>
                        <a href="descargar.php?codigo=${encodeURIComponent(document.querySelector('[name="codigo"]').value)}&file=${encodeURIComponent(respuesta.nombre)}" download>
                            ${archivo.name}
                        </a>
                    </div>
                    <div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="${document.querySelector('[name="csrf_token"]').value}">
                            <input type="hidden" name="codigo" value="${document.querySelector('[name="codigo"]').value}">
                            <input type="hidden" name="eliminarArchivo" value="${respuesta.nombre}">
                            <button type="submit" class="btn_delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" 
                                     width="24" height="24" viewBox="0 0 24 24" 
                                     stroke-width="2" stroke="currentColor" fill="none" 
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M4 7l16 0" />
                                    <path d="M10 11l0 6" />
                                    <path d="M14 11l0 6" />
                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                </svg>
                            </button>
                        </form>
                    </div>
                `;
            }
        }
    };

    xhr.open('POST', 'subir.php', true);
    xhr.send(formData);
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
        'png': 'fa-file-image',
        'txt': 'fa-file-alt'
    };
    return iconos[extension] || 'fa-file';
}