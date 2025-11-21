<?php
// migrations/m240101_000005_create_user_roles_table.php

use yii\db\Migration;

/**
 * Class m240101_000005_create_user_roles_table
 */
class m240101_000005_create_user_roles_table extends Migration
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

        // Tabla para roles múltiples de usuarios
        $this->createTable('{{%seguridad.user_roles}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'role_type' => $this->string(20)->notNull(), // 'deportivo', 'vendedor', 'ambos'
            'is_active' => $this->boolean()->defaultValue(true),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        // Tabla para perfiles específicos
        $this->createTable('{{%seguridad.user_profiles}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'profile_type' => $this->string(20)->notNull(), // 'deportivo', 'comercio'
            'profile_data' => $this->json()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ], $tableOptions);

        // Crear índices
        $this->createIndex('idx_user_roles_user_id', '{{%seguridad.user_roles}}', 'user_id');
        $this->createIndex('idx_user_roles_type', '{{%seguridad.user_roles}}', 'role_type');
        $this->createIndex('idx_user_roles_active', '{{%seguridad.user_roles}}', 'is_active');
        
        $this->createIndex('idx_user_profiles_user_id', '{{%seguridad.user_profiles}}', 'user_id');
        $this->createIndex('idx_user_profiles_type', '{{%seguridad.user_profiles}}', 'profile_type');

        // Claves foráneas
        $this->addForeignKey(
            'fk_user_roles_user',
            '{{%seguridad.user_roles}}',
            'user_id',
            '{{%seguridad.user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_user_profiles_user',
            '{{%seguridad.user_profiles}}',
            'user_id',
            '{{%seguridad.user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Asignar rol de vendedor al usuario demo
        $vendedorDemo = $this->db->createCommand('SELECT id FROM {{%comercio.vendedor}} WHERE email = :email', [':email' => 'vendedor@demo.com'])->queryOne();
        if ($vendedorDemo) {
            $user = $this->db->createCommand('SELECT id FROM {{%seguridad.user}} WHERE email = :email', [':email' => 'vendedor@demo.com'])->queryOne();
            if ($user) {
                $this->insert('{{%seguridad.user_roles}}', [
                    'user_id' => $user['id'],
                    'role_type' => 'vendedor',
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Eliminar claves foráneas
        $this->dropForeignKey('fk_user_roles_user', '{{%seguridad.user_roles}}');
        $this->dropForeignKey('fk_user_profiles_user', '{{%seguridad.user_profiles}}');
        
        // Eliminar tablas
        $this->dropTable('{{%seguridad.user_roles}}');
        $this->dropTable('{{%seguridad.user_profiles}}');
    }
}