<?php
// migrations/m240101_000003_alter_producto_table.php

use yii\db\Migration;

/**
 * Class m240101_000003_alter_producto_table
 */
class m240101_000003_alter_producto_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Agregar columna eliminado si no existe
        if (!$this->db->getTableSchema('{{%comercio.producto}}')->getColumn('eliminado')) {
            $this->addColumn('{{%comercio.producto}}', 'eliminado', $this->boolean()->defaultValue(false));
        }

        // Agregar columna u_creacion si no existe
        if (!$this->db->getTableSchema('{{%comercio.producto}}')->getColumn('u_creacion')) {
            $this->addColumn('{{%comercio.producto}}', 'u_creacion', $this->integer());
        }

        // Crear índices para mejor performance
        $this->createIndex('idx_producto_activo', '{{%comercio.producto}}', 'activo');
        $this->createIndex('idx_producto_eliminado', '{{%comercio.producto}}', 'eliminado');
        $this->createIndex('idx_producto_destacado', '{{%comercio.producto}}', 'destacado');
        $this->createIndex('idx_producto_tienda', '{{%comercio.producto}}', 'id_tienda');
        $this->createIndex('idx_producto_categoria', '{{%comercio.producto}}', 'id_categoria');
        $this->createIndex('idx_producto_stock', '{{%comercio.producto}}', 'stock');

        // Clave foránea para usuario creación
        $this->addForeignKey(
            'fk_producto_usuario_creacion',
            '{{%comercio.producto}}',
            'u_creacion',
            '{{%seguridad.user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Insertar algunos productos de ejemplo
        $this->insertProductosDemo();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Eliminar clave foránea
        $this->dropForeignKey('fk_producto_usuario_creacion', '{{%comercio.producto}}');
        
        // Eliminar índices
        $this->dropIndex('idx_producto_activo', '{{%comercio.producto}}');
        $this->dropIndex('idx_producto_eliminado', '{{%comercio.producto}}');
        $this->dropIndex('idx_producto_destacado', '{{%comercio.producto}}');
        $this->dropIndex('idx_producto_tienda', '{{%comercio.producto}}');
        $this->dropIndex('idx_producto_categoria', '{{%comercio.producto}}');
        $this->dropIndex('idx_producto_stock', '{{%comercio.producto}}');
        
        // No eliminamos las columnas para no perder datos
    }

    /**
     * Insertar productos de demostración
     */
    private function insertProductosDemo()
    {
        // Obtener la primera tienda y categoría
        $tienda = $this->db->createCommand('SELECT id FROM {{%comercio.tienda}} LIMIT 1')->queryOne();
        $categoria = $this->db->createCommand('SELECT id FROM {{%comercio.categoria_producto}} LIMIT 1')->queryOne();

        if ($tienda && $categoria) {
            $productosDemo = [
                [
                    'id_tienda' => $tienda['id'],
                    'id_categoria' => $categoria['id'],
                    'nombre' => 'Balón de Fútbol Profesional',
                    'descripcion' => 'Balón de fútbol profesional tamaño 5, ideal para entrenamientos y partidos.',
                    'precio' => 25.99,
                    'precio_oferta' => 22.99,
                    'stock' => 50,
                    'sku' => 'BAL-FUT-001',
                    'activo' => true,
                    'destacado' => true,
                ],
                [
                    'id_tienda' => $tienda['id'],
                    'id_categoria' => $categoria['id'],
                    'nombre' => 'Raqueta de Tenis Intermedia',
                    'descripcion' => 'Raqueta de tenis para nivel intermedio, perfecta para jugadores en desarrollo.',
                    'precio' => 89.99,
                    'precio_oferta' => null,
                    'stock' => 25,
                    'sku' => 'RAQ-TEN-001',
                    'activo' => true,
                    'destacado' => false,
                ],
                [
                    'id_tienda' => $tienda['id'],
                    'id_categoria' => $categoria['id'],
                    'nombre' => 'Set de Pesas Ajustables',
                    'descripcion' => 'Set de pesas ajustables de 5kg a 20kg, ideal para entrenamiento en casa.',
                    'precio' => 149.99,
                    'precio_oferta' => 129.99,
                    'stock' => 15,
                    'sku' => 'PES-AJU-001',
                    'activo' => true,
                    'destacado' => true,
                ],
            ];

            foreach ($productosDemo as $producto) {
                $this->insert('{{%comercio.producto}}', $producto);
            }
        }
    }
}