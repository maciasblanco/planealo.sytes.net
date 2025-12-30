<?php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'pgsql:host=localhost;dbname=escuelas_deportivas',
    'username' => 'postgres',
    'password' => '*m4c145',
    'charset' => 'utf8',
    
    // Agregar esto para PostgreSQL
    'schemaMap' => [
        'pgsql' => [
            'class' => 'yii\db\pgsql\Schema',
            'defaultSchema' => 'seguridad' // Esquema por defecto
        ]
    ],
    
    // Forzar conexión al inicio
    'on afterOpen' => function($event) {
        // Establecer el search_path para incluir el esquema seguridad
        $event->sender->createCommand("SET search_path TO seguridad, public")->execute();
        Yii::info('Conexión a PostgreSQL establecida', 'db');
    }
];