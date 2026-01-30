<?php
/**
 * Configuración de conexión PostgreSQL para sistema de autenticación seguro
 * Base: escuelas_deportivas | Esquema: seguridad
 */

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'pgsql:host=localhost;dbname=escuelas_deportivas',
    'username' => 'postgres',
    'password' => '*m4c145',
    'charset' => 'utf8',
    
    // ==================== CACHE DE ESQUEMA (IMPORTANTE PARA RENDIMIENTO) ====================
    'enableSchemaCache' => true,           // Activar cache de esquema
    'schemaCacheDuration' => 3600,         // Duración: 1 hora (3600 segundos)
    'schemaCache' => 'cache',              // Componente cache a usar
    
    // ==================== CONFIGURACIÓN ESPECÍFICA POSTGRESQL ====================
    'schemaMap' => [
        'pgsql' => [
            'class' => 'yii\db\pgsql\Schema',
            'defaultSchema' => 'seguridad', // Esquema por defecto
        ]
    ],
    
    // ==================== ATRIBUTOS PDO (OPTIMIZACIÓN) ====================
    'attributes' => [
        // Desactivar emulación de prepared statements (MEJOR SEGURIDAD)
        PDO::ATTR_EMULATE_PREPARES => false,
        // Desactivar stringify en fetch (MEJOR RENDIMIENTO)
        PDO::ATTR_STRINGIFY_FETCHES => false,
        // Manejo de errores
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Fetch por defecto como array asociativo
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
    
    // ==================== CACHE DE CONSULTAS ====================
    'enableQueryCache' => true,            // Activar cache de consultas
    'queryCacheDuration' => 300,           // 5 minutos para consultas frecuentes
    'queryCache' => 'cache',
    
    // ==================== EVENTO DESPUÉS DE ABRIR CONEXIÓN ====================
    'on afterOpen' => function($event) {
        try {
            $connection = $event->sender;
            
            // 1. Establecer search_path (ESQUEMA PRINCIPAL: seguridad)
            $connection->createCommand("SET search_path TO seguridad, public")->execute();
            
            // 2. Configurar timezone (América/Caracas para Venezuela)
            $connection->createCommand("SET TIME ZONE 'America/Caracas'")->execute();
            
            Yii::info('✅ Conexión PostgreSQL establecida', 'db');
            
        } catch (\Exception $e) {
            Yii::error('❌ Error configurando conexión PostgreSQL: ' . $e->getMessage(), 'db');
            // NO relanzar la excepción para no romper la aplicación
        }
    },
    
    // ==================== LOGGING DE CONSULTAS (SOLO DESARROLLO) ====================
    'enableLogging' => YII_DEBUG,          // Log SQL solo en modo debug
    'enableProfiling' => YII_DEBUG,        // Profiling solo en modo debug
    
    // ==================== CONFIGURACIÓN DE COMANDO ====================
    'commandClass' => 'yii\db\Command',
];