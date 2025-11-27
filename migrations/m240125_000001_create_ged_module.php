<?php

use yii\db\Migration;

/**
 * Class m240125_000001_create_ged_module
 */
class m240125_000001_create_ged_module extends Migration
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

        // Tabla de categorías/documentos
        $this->createTable('{{%ged_category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'parent_id' => $this->integer()->defaultValue(null),
            'path' => $this->string(1000),
            'level' => $this->integer()->defaultValue(0),
            'sort_order' => $this->integer()->defaultValue(0),
            'is_active' => $this->boolean()->defaultValue(true),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        // Tabla de documentos
        $this->createTable('{{%ged_document}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'original_name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'file_path' => $this->string(1000)->notNull(),
            'file_size' => $this->integer()->defaultValue(0),
            'file_extension' => $this->string(10),
            'mime_type' => $this->string(100),
            'version' => $this->integer()->defaultValue(1),
            'is_latest' => $this->boolean()->defaultValue(true),
            'status' => $this->smallInteger()->defaultValue(1), // 1=active, 0=inactive, 2=archived
            'metadata' => $this->json(),
            'tags' => $this->string(500),
            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        // Tabla de versiones de documentos
        $this->createTable('{{%ged_document_version}}', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer()->notNull(),
            'version' => $this->integer()->notNull(),
            'file_path' => $this->string(1000)->notNull(),
            'file_size' => $this->integer()->defaultValue(0),
            'changes' => $this->text(),
            'created_by' => $this->integer(),
            'created_at' => $this->integer(),
        ], $tableOptions);

        // Tabla de metadatos adicionales
        $this->createTable('{{%ged_metadata}}', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer()->notNull(),
            'key' => $this->string(255)->notNull(),
            'value' => $this->text(),
            'type' => $this->string(50)->defaultValue('string'), // string, integer, date, etc.
        ], $tableOptions);

        // Tabla de tags
        $this->createTable('{{%ged_tag}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'color' => $this->string(7)->defaultValue('#007bff'),
            'created_by' => $this->integer(),
            'created_at' => $this->integer(),
        ], $tableOptions);

        // Tabla de relación documento-tags
        $this->createTable('{{%ged_document_tag}}', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer()->notNull(),
            'tag_id' => $this->integer()->notNull(),
        ], $tableOptions);

        // Índices para la tabla ged_category
        $this->createIndex('idx-ged_category-parent_id', '{{%ged_category}}', 'parent_id');
        $this->createIndex('idx-ged_category-is_active', '{{%ged_category}}', 'is_active');
        $this->createIndex('idx-ged_category-level', '{{%ged_category}}', 'level');
        $this->createIndex('idx-ged_category-sort_order', '{{%ged_category}}', 'sort_order');

        // Índices para la tabla ged_document
        $this->createIndex('idx-ged_document-category_id', '{{%ged_document}}', 'category_id');
        $this->createIndex('idx-ged_document-status', '{{%ged_document}}', 'status');
        $this->createIndex('idx-ged_document-is_latest', '{{%ged_document}}', 'is_latest');
        $this->createIndex('idx-ged_document-file_extension', '{{%ged_document}}', 'file_extension');
        $this->createIndex('idx-ged_document-created_at', '{{%ged_document}}', 'created_at');

        // Índices para la tabla ged_document_version
        $this->createIndex('idx-ged_document_version-document_id', '{{%ged_document_version}}', 'document_id');
        $this->createIndex('idx-ged_document_version-version', '{{%ged_document_version}}', 'version');

        // Índices para la tabla ged_metadata
        $this->createIndex('idx-ged_metadata-document_id', '{{%ged_metadata}}', 'document_id');
        $this->createIndex('idx-ged_metadata-key', '{{%ged_metadata}}', 'key');

        // Índices para la tabla ged_tag
        $this->createIndex('idx-ged_tag-name', '{{%ged_tag}}', 'name');

        // Índices para la tabla ged_document_tag
        $this->createIndex('idx-ged_document_tag-document_id', '{{%ged_document_tag}}', 'document_id');
        $this->createIndex('idx-ged_document_tag-tag_id', '{{%ged_document_tag}}', 'tag_id');
        $this->createIndex('idx-ged_document_tag-unique', '{{%ged_document_tag}}', ['document_id', 'tag_id'], true);

        // Claves foráneas
        $this->addForeignKey(
            'fk-ged_category-parent_id',
            '{{%ged_category}}',
            'parent_id',
            '{{%ged_category}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-ged_document-category_id',
            '{{%ged_document}}',
            'category_id',
            '{{%ged_category}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-ged_document_version-document_id',
            '{{%ged_document_version}}',
            'document_id',
            '{{%ged_document}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-ged_metadata-document_id',
            '{{%ged_metadata}}',
            'document_id',
            '{{%ged_document}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-ged_document_tag-document_id',
            '{{%ged_document_tag}}',
            'document_id',
            '{{%ged_document}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-ged_document_tag-tag_id',
            '{{%ged_document_tag}}',
            'tag_id',
            '{{%ged_tag}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Insertar categoría raíz por defecto
        $this->insert('{{%ged_category}}', [
            'name' => 'Documentos Raíz',
            'description' => 'Categoría raíz del sistema de documentos',
            'parent_id' => null,
            'path' => '/',
            'level' => 0,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Eliminar claves foráneas en orden inverso
        $this->dropForeignKey('fk-ged_document_tag-tag_id', '{{%ged_document_tag}}');
        $this->dropForeignKey('fk-ged_document_tag-document_id', '{{%ged_document_tag}}');
        $this->dropForeignKey('fk-ged_metadata-document_id', '{{%ged_metadata}}');
        $this->dropForeignKey('fk-ged_document_version-document_id', '{{%ged_document_version}}');
        $this->dropForeignKey('fk-ged_document-category_id', '{{%ged_document}}');
        $this->dropForeignKey('fk-ged_category-parent_id', '{{%ged_category}}');

        // Eliminar tablas en orden inverso
        $this->dropTable('{{%ged_document_tag}}');
        $this->dropTable('{{%ged_tag}}');
        $this->dropTable('{{%ged_metadata}}');
        $this->dropTable('{{%ged_document_version}}');
        $this->dropTable('{{%ged_document}}');
        $this->dropTable('{{%ged_category}}');
    }
}