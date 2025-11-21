<?php
// migrations/m240101_000004_alter_categoria_producto_table.php

use yii\db\Migration;

/**
 * Class m240101_000004_alter_categoria_producto_table
 */
class m240101_000004_alter_categoria_producto_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Verificar que la tabla existe y tiene las columnas necesarias
        $schema = $this->db->getTableSchema('{{%comercio.categoria_producto}}');
        
        if ($schema) {
            // Crear índices para mejor performance
            $this->createIndex('idx_categoria_activo', '{{%comercio.categoria_producto}}', 'activo');
            $this->createIndex('idx_categoria_padre', '{{%comercio.categoria_producto}}', 'id_padre');
            $this->createIndex('idx_categoria_orden', '{{%comercio.categoria_producto}}', 'orden');

            // Insertar categorías principales si no existen
            $this->insertCategoriasPrincipales();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Eliminar índices
        $this->dropIndex('idx_categoria_activo', '{{%comercio.categoria_producto}}');
        $this->dropIndex('idx_categoria_padre', '{{%comercio.categoria_producto}}');
        $this->dropIndex('idx_categoria_orden', '{{%comercio.categoria_producto}}');
    }

    /**
     * Insertar categorías principales
     */
    private function insertCategoriasPrincipales()
    {
        $categoriasExistentes = $this->db->createCommand('SELECT COUNT(*) FROM {{%comercio.categoria_producto}}')->queryScalar();
        
        if ($categoriasExistentes == 0) {
            $categorias = [
                [
                    'nombre' => 'Equipamiento Deportivo',
                    'descripcion' => 'Todo tipo de equipamiento y accesorios deportivos',
                    'orden' => 1,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Nutrición Deportiva',
                    'descripcion' => 'Suplementos y productos nutricionales para deportistas',
                    'orden' => 2,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Ropa Deportiva',
                    'descripcion' => 'Ropa y calzado especializado para deportes',
                    'orden' => 3,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Servicios Deportivos',
                    'descripcion' => 'Entrenamientos, clases y servicios relacionados con el deporte',
                    'orden' => 4,
                    'activo' => true,
                ],
            ];

            foreach ($categorias as $categoria) {
                $this->insert('{{%comercio.categoria_producto}}', $categoria);
            }

            // Insertar subcategorías para Equipamiento Deportivo
            $equipamientoId = $this->db->getLastInsertID();
            $subcategorias = [
                [
                    'nombre' => 'Fútbol',
                    'id_padre' => $equipamientoId,
                    'orden' => 1,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Béisbol',
                    'id_padre' => $equipamientoId,
                    'orden' => 2,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Baloncesto',
                    'id_padre' => $equipamientoId,
                    'orden' => 3,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Tenis',
                    'id_padre' => $equipamientoId,
                    'orden' => 4,
                    'activo' => true,
                ],
            ];

            foreach ($subcategorias as $subcategoria) {
                $this->insert('{{%comercio.categoria_producto}}', $subcategoria);
            }
        }
    }
}