// web/js/modules/horario-selector.js
class HorarioModule {
    constructor() {
        this.horariosSeleccionados = {};
        this.dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        this.inicializar();
    }

    inicializar() {
        this.inicializarHorarios();
        this.cargarHorarioDesdeInput();
        this.configurarEventListeners();
        this.aplicarColoresTurnos();
    }

    inicializarHorarios() {
        this.dias.forEach(dia => {
            this.horariosSeleccionados[dia] = [];
        });
    }

    configurarEventListeners() {
        // Click en celdas de horario
        $(document).on('click', '.horario-cell', (e) => {
            const $celda = $(e.currentTarget);
            const dia = $celda.data('dia');
            const hora = $celda.data('hora');
            this.toggleHorario(dia, hora);
        });
        
        // Selector de tipo de horario
        $('#tipo-horario').on('change', (e) => {
            const tipo = $(e.target).val();
            this.seleccionarRango(tipo);
        });
        
        // Botones de acciones rápidas
        $('#select-all').on('click', () => this.seleccionarTodo());
        $('#clear-all').on('click', () => this.limpiarTodo());
    }

    toggleHorario(dia, hora) {
        const index = this.horariosSeleccionados[dia].indexOf(hora);
        const cell = $(`#${dia}_${hora}`);
        
        if (index === -1) {
            // Agregar horario
            this.horariosSeleccionados[dia].push(hora);
            cell.addClass('selected');
            cell.find('.fas').removeClass('fa-times text-muted').addClass('fa-check text-white');
        } else {
            // Quitar horario
            this.horariosSeleccionados[dia].splice(index, 1);
            cell.removeClass('selected');
            cell.find('.fas').removeClass('fa-check text-white').addClass('fa-times text-muted');
        }
        
        this.actualizarVistaPrevia();
        this.guardarHorarioEnInput();
    }

    actualizarVistaPrevia() {
        const preview = $('#horario-preview');
        let html = '';
        
        const diasMap = {
            'lunes': 'Lunes',
            'martes': 'Martes', 
            'miercoles': 'Miércoles',
            'jueves': 'Jueves',
            'viernes': 'Viernes',
            'sabado': 'Sábado',
            'domingo': 'Domingo'
        };
        
        let tieneHorarios = false;
        
        for (const [dia, horas] of Object.entries(this.horariosSeleccionados)) {
            if (horas.length > 0) {
                tieneHorarios = true;
                const horasOrdenadas = horas.sort((a, b) => a - b);
                const horariosFormateados = horasOrdenadas.map(h => {
                    return h <= 12 ? h + ':00 AM' : (h - 12) + ':00 PM';
                });
                
                html += `<div class="mb-1"><strong>${diasMap[dia]}:</strong> ${horariosFormateados.join(', ')}</div>`;
            }
        }
        
        if (!tieneHorarios) {
            html = '<small class="text-muted">No se han seleccionado horarios</small>';
        }
        
        preview.html(html);
    }

    guardarHorarioEnInput() {
        $('#horario-data').val(JSON.stringify(this.horariosSeleccionados));
    }

    cargarHorarioDesdeInput() {
        const horarioData = $('#horario-data').val();
        if (horarioData && horarioData !== '') {
            try {
                this.horariosSeleccionados = JSON.parse(horarioData);
                
                // Actualizar interfaz
                for (const [dia, horas] of Object.entries(this.horariosSeleccionados)) {
                    horas.forEach(hora => {
                        const cell = $(`#${dia}_${hora}`);
                        cell.addClass('selected');
                        cell.find('.fas').removeClass('fa-times text-muted').addClass('fa-check text-white');
                    });
                }
                
                this.actualizarVistaPrevia();
            } catch (e) {
                console.error('Error al cargar horario:', e);
                this.horariosSeleccionados = {};
                this.inicializarHorarios();
            }
        }
    }

    seleccionarRango(tipo) {
        const rangos = {
            'manana': [6, 7, 8, 9, 10, 11],
            'tarde': [12, 13, 14, 15, 16, 17], 
            'noche': [18, 19, 20, 21, 22],
            'completo': [6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22],
            'fin_semana': [8,9,10,11,12,13,14,15,16,17]
        };
        
        const horas = rangos[tipo] || [];
        let dias = [];
        
        // Definir días según el tipo
        switch(tipo) {
            case 'fin_semana':
                dias = ['sabado', 'domingo'];
                break;
            default:
                dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        }
        
        dias.forEach(dia => {
            horas.forEach(hora => {
                if (!this.horariosSeleccionados[dia].includes(hora)) {
                    this.toggleHorario(dia, hora);
                }
            });
        });
    }

    seleccionarTodo() {
        $('.horario-cell').each((index, element) => {
            const $celda = $(element);
            const dia = $celda.data('dia');
            const hora = $celda.data('hora');
            if (!this.horariosSeleccionados[dia].includes(hora)) {
                this.toggleHorario(dia, hora);
            }
        });
    }

    limpiarTodo() {
        $('.horario-cell').each((index, element) => {
            const $celda = $(element);
            const dia = $celda.data('dia');
            const hora = $celda.data('hora');
            if (this.horariosSeleccionados[dia].includes(hora)) {
                this.toggleHorario(dia, hora);
            }
        });
    }

    aplicarColoresTurnos() {
        $('.horario-cell').each((index, element) => {
            const $celda = $(element);
            const hora = $celda.data('hora');
            
            // Solo aplicar colores si no está seleccionado
            if (!$celda.hasClass('selected')) {
                if (hora >= 6 && hora < 12) {
                    $celda.addClass('morning');
                } else if (hora >= 12 && hora < 18) {
                    $celda.addClass('afternoon');
                } else {
                    $celda.addClass('evening');
                }
            }
        });
    }

    // Método para obtener los horarios seleccionados
    getHorariosSeleccionados() {
        return this.horariosSeleccionados;
    }

    // Método para establecer horarios (útil para edición)
    setHorariosSeleccionados(horarios) {
        this.horariosSeleccionados = horarios;
        this.limpiarTodo(); // Primero limpiar
        this.cargarHorarioDesdeInput(); // Luego cargar los nuevos
    }
}

// Inicialización global
let horarioModuleInstance = null;

function initHorarioModule() {
    if ($('#horario-grid').length > 0) {
        horarioModuleInstance = new HorarioModule();
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    initHorarioModule();
});

// Para compatibilidad con Yii2 y inicialización manual
window.HorarioModule = HorarioModule;
window.initHorarioModule = initHorarioModule;
window.getHorarioModuleInstance = function() {
    return horarioModuleInstance;
};