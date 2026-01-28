<?php
return [
    // Ruta por defecto del módulo
    '' => 'reportes/default/index',
    
    // Rutas específicas para diferentes tipos de usuarios
    'atletas' => 'reportes/reportes/atletas',
    'estadisticas-atleta/<id:\d+>' => 'reportes/reportes/estadisticas-atleta',
    'estadisticas-atleta' => 'reportes/reportes/estadisticas-atleta',
    'asistencias' => 'reportes/reportes/asistencias',
    'deudas-pendientes' => 'reportes/reportes/deudas-pendientes',
    'exportar-pdf/<reporte:\w+>' => 'reportes/reportes/exportar-pdf',
    'exportar-excel/<reporte:\w+>' => 'reportes/reportes/exportar-excel',
];