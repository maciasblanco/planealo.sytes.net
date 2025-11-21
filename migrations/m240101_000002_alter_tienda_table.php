<?php
// migrations/m240101_000002_alter_tienda_table.php

use yii\db\Migration;
use yii\db\TableSchema;

/**
 * Class m240101_000002_alter_tienda_table
 */
class m240101_000002_alter_tienda_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableName = '{{%comercio.tienda}}';
        $schema = $this->db->getTableSchema($tableName);

        // Agregar nuevas columnas solo si no existen
        $columnsToAdd = [
            'id_vendedor' => $this->integer(),
            'tipo_propietario' => $this->string(20)->defaultValue('vendedor'),
            'slug' => $this->string(100),
            'horario_atencion' => $this->text(),
            'politicas_entrega' => $this->text(),
            'redes_sociales' => $this->text(),
            'user_id' => $this->integer(),
        ];

        foreach ($columnsToAdd as $columnName => $columnType) {
            if (!isset($schema->columns[$columnName])) {
                $this->addColumn($tableName, $columnName, $columnType);
                echo "✅ Columna {$columnName} agregada\n";
            } else {
                echo "ℹ️ Columna {$columnName} ya existe, omitiendo...\n";
            }
        }

        // Crear índices para nuevas columnas
        $this->createIndex('idx_tienda_vendedor', $tableName, 'id_vendedor');
        $this->createIndex('idx_tienda_slug', $tableName, 'slug', true);
        $this->createIndex('idx_tienda_user_id', $tableName, 'user_id');
        $this->createIndex('idx_tienda_tipo_propietario', $tableName, 'tipo_propietario');

        // Claves foráneas
        $this->addForeignKey(
            'fk_tienda_vendedor',
            $tableName,
            'id_vendedor',
            '{{%comercio.vendedor}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_tienda_user',
            $tableName,
            'user_id',
            '{{%seguridad.user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Actualizar tiendas existentes para que tengan slug
        $tiendas = $this->db->createCommand('SELECT id, nombre FROM {{%comercio.tienda}} WHERE slug IS NULL OR slug = \'\'')->queryAll();
        foreach ($tiendas as $tienda) {
            $slug = $this->generarSlug($tienda['nombre']);
            $this->update($tableName, 
                ['slug' => $slug], 
                ['id' => $tienda['id']]
            );
            echo "✅ Slug generado para tienda: {$tienda['nombre']} -> {$slug}\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $tableName = '{{%comercio.tienda}}';

        // Eliminar claves foráneas
        $this->dropForeignKey('fk_tienda_vendedor', $tableName);
        $this->dropForeignKey('fk_tienda_user', $tableName);
        
        // Eliminar índices
        $this->dropIndex('idx_tienda_vendedor', $tableName);
        $this->dropIndex('idx_tienda_slug', $tableName);
        $this->dropIndex('idx_tienda_user_id', $tableName);
        $this->dropIndex('idx_tienda_tipo_propietario', $tableName);
        
        // Eliminar columnas (solo las que agregamos)
        $this->dropColumn($tableName, 'id_vendedor');
        $this->dropColumn($tableName, 'tipo_propietario');
        $this->dropColumn($tableName, 'slug');
        $this->dropColumn($tableName, 'horario_atencion');
        $this->dropColumn($tableName, 'politicas_entrega');
        $this->dropColumn($tableName, 'redes_sociales');
        $this->dropColumn($tableName, 'user_id');
    }

    /**
     * Generar slug a partir del nombre
     */
    private function generarSlug($nombre)
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($nombre));
        $slug = trim($slug, '-');
        
        // Verificar si el slug ya existe
        $counter = 1;
        $baseSlug = $slug;
        
        while ($this->db->createCommand('SELECT id FROM {{%comercio.tienda}} WHERE slug = :slug', [':slug' => $slug])->queryOne()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}