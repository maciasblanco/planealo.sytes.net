// web/js/modules/reportes-module.js
class ReportesModule {
    constructor() {
        this.init();
    }

    init() {
        console.log('📊 Módulo de Reportes inicializando...');
        
        // Inicializar tooltips
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        }

        // Hacer clickeables los paneles
        document.querySelectorAll('.info-box').forEach(box => {
            box.style.cursor = 'pointer';
        });

        console.log('✅ ReportesModule inicializado');
    }

    filtrarPorEstado(estado) {
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

    exportarTabla() {
        alert('Funcionalidad de exportación en desarrollo');
    }
}

// Inicializar automáticamente si hay elementos de reportes
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.reportes-container') || document.querySelector('#tabla-atletas')) {
        window.reportesModule = new ReportesModule();
    }
});