<?php

use yii\db\Migration;

/**
 * Class m251105_213716_add_approval_fields_to_escuela
 */
class m251105_213716_add_approval_fields_to_escuela extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Verificar si las columnas ya existen para evitar errores
        $table = 'atletas.escuela';
        $tableSchema = Yii::$app->db->schema->getTableSchema($table);
        
        // Agregar estado_registro si no existe
        if (!isset($tableSchema->columns['estado_registro'])) {
            $this->addColumn($table, 'estado_registro', $this->string(20)->defaultValue('pre_registro'));
        }
        
        // Agregar comentarios_aprobacion si no existe
        if (!isset($tableSchema->columns['comentarios_aprobacion'])) {
            $this->addColumn($table, 'comentarios_aprobacion', $this->text());
        }
        
        // Agregar aprobado_por si no existe
        if (!isset($tableSchema->columns['aprobado_por'])) {
            $this->addColumn($table, 'aprobado_por', $this->integer());
        }
        
        // Agregar fecha_aprobacion si no existe
        if (!isset($tableSchema->columns['fecha_aprobacion'])) {
            $this->addColumn($table, 'fecha_aprobacion', $this->timestamp());
        }
        
        // Crear índices si no existen
        try {
            $this->createIndex('idx_escuela_estado_registro', $table, 'estado_registro');
        } catch (\Exception $e) {
            // Índice ya existe, continuar
        }
        
        try {
            $this->createIndex('idx_escuela_aprobado_por', $table, 'aprobado_por');
        } catch (\Exception $e) {
            // Índice ya existe, continuar
        }
        
        // Actualizar escuelas existentes a estado aprobado
        $this->update($table, ['estado_registro' => 'aprobado']);
        
        echo "Migración completada: Campos de aprobación agregados a la tabla escuela.\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = 'atletas.escuela';
        
        // Eliminar columnas si existen
        $tableSchema = Yii::$app->db->schema->getTableSchema($table);
        
        if (isset($tableSchema->columns['fecha_aprobacion'])) {
            $this->dropColumn($table, 'fecha_aprobacion');
        }
        
        if (isset($tableSchema->columns['aprobado_por'])) {
            $this->dropColumn($table, 'aprobado_por');
        }
        
        if (isset($tableSchema->columns['comentarios_aprobacion'])) {
            $this->dropColumn($table, 'comentarios_aprobacion');
        }
        
        if (isset($tableSchema->columns['estado_registro'])) {
            $this->dropColumn($table, 'estado_registro');
        }
        
        echo "Migración revertida: Campos de aprobación eliminados.\n";
    }
}