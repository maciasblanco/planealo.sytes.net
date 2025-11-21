<?php
// migrations/m240101_000001_create_vendedor_table.php

use yii\db\Migration;

/**
 * Class m240101_000001_create_vendedor_table
 */
class m240101_000001_create_vendedor_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // Crear tabla vendedor
        $this->createTable('{{%comercio.vendedor}}', [
            'id' => $this->primaryKey(),
            'nombre_completo' => $this->string(200)->notNull(),
            'email' => $this->string(100)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'telefono' => $this->string(20),
            'identificacion' => $this->string(20),
            'tipo_identificacion' => $this->string(10),
            'direccion' => $this->text(),
            'id_estado' => $this->integer(),
            'id_municipio' => $this->integer(),
            'activo' => $this->boolean()->defaultValue(true),
            'fecha_registro' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'ultimo_login' => $this->timestamp()->null(),
            'terminos_aceptados' => $this->boolean()->defaultValue(false),
            'user_id' => $this->integer(),
        ], $tableOptions);

        // Crear índices
        $this->createIndex('idx_vendedor_email', '{{%comercio.vendedor}}', 'email', true);
        $this->createIndex('idx_vendedor_identificacion', '{{%comercio.vendedor}}', 'identificacion', true);
        $this->createIndex('idx_vendedor_activo', '{{%comercio.vendedor}}', 'activo');
        $this->createIndex('idx_vendedor_user_id', '{{%comercio.vendedor}}', 'user_id');

        // Claves foráneas
        $this->addForeignKey(
            'fk_vendedor_estado',
            '{{%comercio.vendedor}}',
            'id_estado',
            '{{%catalogos.estado}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_vendedor_municipio',
            '{{%comercio.vendedor}}',
            'id_municipio',
            '{{%catalogos.municipio}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_vendedor_user',
            '{{%comercio.vendedor}}',
            'user_id',
            '{{%seguridad.user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Insertar datos iniciales de ejemplo (opcional)
        $this->insert('{{%comercio.vendedor}}', [
            'nombre_completo' => 'Vendedor Demo',
            'email' => 'vendedor@demo.com',
            'password_hash' => Yii::$app->security->generatePasswordHash('demo123'),
            'telefono' => '0412-1234567',
            'identificacion' => 'V12345678',
            'tipo_identificacion' => 'V',
            'activo' => true,
            'terminos_aceptados' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Eliminar claves foráneas
        $this->dropForeignKey('fk_vendedor_estado', '{{%comercio.vendedor}}');
        $this->dropForeignKey('fk_vendedor_municipio', '{{%comercio.vendedor}}');
        $this->dropForeignKey('fk_vendedor_user', '{{%comercio.vendedor}}');
        
        // Eliminar tabla
        $this->dropTable('{{%comercio.vendedor}}');
    }
}