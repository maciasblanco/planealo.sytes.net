<?php

use yii\db\Migration;

/**
 * Class m260115_000002_corregir_estructura_aportes
 * Migración segura para PostgreSQL que:
 * 1. Verifica columnas existentes antes de actuar
 * 2. Renombra columnas si existen
 * 3. Agrega nuevas columnas con manejo dual de moneda
 * 4. Usa IF EXISTS/IF NOT EXISTS para evitar errores
 */
class m260115_000002_corregir_estructura_aportes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableName = 'contabilidad.aportes_semanales';
        $schema = Yii::$app->db->schema;
        $tableSchema = $schema->getTableSchema($tableName);
        
        if ($tableSchema === null) {
            echo "La tabla {$tableName} no existe.\n";
            return false;
        }
        
        echo "=== INICIANDO MIGRACIÓN SEGURA PARA POSTGRESQL ===\n";
        echo "Tabla encontrada: {$tableName}\n";
        
        // 1. VERIFICAR Y RENOMBRAR COLUMNAS EXISTENTES
        $columns = array_keys($tableSchema->columns);
        
        // Renombrar fecha_viernes a fecha_quincena (si existe)
        if (in_array('fecha_viernes', $columns) && !in_array('fecha_quincena', $columns)) {
            echo "Renombrando fecha_viernes -> fecha_quincena\n";
            $this->renameColumn($tableName, 'fecha_viernes', 'fecha_quincena');
        }
        
        // Renombrar numero_semana a numero_quincena (si existe)
        if (in_array('numero_semana', $columns) && !in_array('numero_quincena', $columns)) {
            echo "Renombrando numero_semana -> numero_quincena\n";
            $this->renameColumn($tableName, 'numero_semana', 'numero_quincena');
        }
        
        // 2. AGREGAR NUEVAS COLUMNAS PARA SISTEMA DUAL
        echo "Agregando columnas para sistema dual de moneda...\n";
        
        // Columna: tasa_dolar_quincena
        if (!in_array('tasa_dolar_quincena', $columns)) {
            echo "Agregando columna: tasa_dolar_quincena\n";
            $this->addColumn($tableName, 'tasa_dolar_quincena', $this->decimal(10,4)->defaultValue(0));
        }
        
        // Columna: monto_bs_original
        if (!in_array('monto_bs_original', $columns)) {
            echo "Agregando columna: monto_bs_original\n";
            $this->addColumn($tableName, 'monto_bs_original', $this->decimal(10,4)->defaultValue(0));
        }
        
        // Columna: tipo_cambio
        if (!in_array('tipo_cambio', $columns)) {
            echo "Agregando columna: tipo_cambio\n";
            $this->addColumn($tableName, 'tipo_cambio', $this->string(50)->defaultValue('oficial'));
        }
        
        // 3. CREAR ÍNDICES (con verificación)
        echo "Creando índices...\n";
        
        // Verificar si ya existe un índice en fecha_quincena
        $indexName = 'idx_aportes_fecha_quincena';
        $existingIndexes = Yii::$app->db->createCommand("
            SELECT indexname FROM pg_indexes 
            WHERE tablename = 'aportes_semanales' 
            AND indexname = '{$indexName}'
        ")->queryScalar();
        
        if (!$existingIndexes && $this->db->schema->getTableSchema($tableName)->getColumn('fecha_quincena')) {
            echo "Creando índice en fecha_quincena\n";
            $this->createIndex('idx_aportes_fecha_quincena', $tableName, 'fecha_quincena');
        }
        
        // 4. ACTUALIZAR CONSTRAINT DE MONTO (si existe la columna monto)
        if (in_array('monto', $columns)) {
            echo "Actualizando constraint de monto...\n";
            // En PostgreSQL, podemos modificar el default
            $this->alterColumn($tableName, 'monto', $this->decimal(10,2)->defaultValue(4.00));
        }
        
        echo "=== MIGRACIÓN COMPLETADA CON ÉXITO ===\n";
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260115_000002_corregir_estructura_aportes cannot be reverted.\n";
        return false;
    }
}