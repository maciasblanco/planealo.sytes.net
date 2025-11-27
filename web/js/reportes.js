// ===== FUNCIONALIDADES PARA EL MÓDULO DE REPORTES =====

// Función para filtrar por estado
function filtrarPorEstado(estado) {
    const filas = document.querySelectorAll('#tabla-atletas tbody tr');
    filas.forEach(fila => {
        if (estado === 'todos') {
            fila.style.display = '';
        } else {
            const tieneEstado = fila.getAttribute('data-estado').includes(estado);
            fila.style.display = tieneEstado ? '' : 'none';
        }
    });
}

// Función para exportar la tabla
function exportarTabla() {
    // Aquí implementarías la lógica para exportar a Excel o PDF
    alert('Funcionalidad de exportación en desarrollo');
}

// Inicialización de tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Hacer clickeables los paneles de estadísticas
    document.querySelectorAll('.info-box').forEach(box => {
        box.style.cursor = 'pointer';
    });
});