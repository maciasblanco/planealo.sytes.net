<?php
use yii\db\Migration;

/**
 * Class m250119_000007_security_tables_consolidated
 * 
 * Migración consolidada para crear todas las tablas del módulo de seguridad
 * Compatible con PostgreSQL (sin ON UPDATE CURRENT_TIMESTAMP)
 * Verifica existencia de tablas antes de crearlas
 */
class m250119_000007_security_tables_consolidated extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        echo "🚀 INICIANDO MIGRACIÓN CONSOLIDADA DE SEGURIDAD\n";
        echo "📅 " . date('Y-m-d H:i:s') . "\n\n";
        
        $this->createVerificationSessionTable();
        $this->createAuditLogTable();
        $this->createPasswordHistoryTable();
        $this->createUserBlockHistoryTable();
        $this->createLoginAttemptTable();
        
        echo "\n🎉 MIGRACIÓN CONSOLIDADA COMPLETADA EXITOSAMENTE\n";
        return true;
    }
    
    /**
     * Crear tabla verification_session
     */
    private function createVerificationSessionTable()
    {
        $tableName = 'seguridad.verification_session';
        
        if ($this->tableExists($tableName)) {
            echo "ℹ️  Tabla ya existe: {$tableName}\n";
            return;
        }
        
        echo "📦 Creando tabla: {$tableName}\n";
        
        $this->createTable($tableName, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'session_token' => $this->string(64)->notNull(),
            'email' => $this->string(255)->null(),
            'verification_code' => $this->string(6)->null(),
            'code_sent_at' => $this->timestamp()->null(),
            'code_expires_at' => $this->timestamp()->null(),
            'attempts_remaining' => $this->integer()->defaultValue(3),
            'codes_sent_count' => $this->integer()->defaultValue(0),
            'status' => $this->string(20)->defaultValue('pending'),
            'ip_address' => $this->string(45)->notNull(),
            'user_agent' => $this->text()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->null(),
        ]);
        
        // Índices
        $this->createIndex('idx_verification_session_user_id', $tableName, 'user_id');
        $this->createIndex('idx_verification_session_token', $tableName, 'session_token');
        $this->createIndex('idx_verification_session_status', $tableName, 'status');
        $this->createIndex('idx_verification_session_expires', $tableName, 'code_expires_at');
        
        // Clave foránea
        $this->addForeignKey(
            'fk_verification_session_user_id',
            $tableName,
            'user_id',
            'seguridad.user',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        echo "✅ Tabla creada: {$tableName}\n";
    }
    
    /**
     * Crear tabla audit_log
     */
    private function createAuditLogTable()
    {
        $tableName = 'seguridad.audit_log';
        
        if ($this->tableExists($tableName)) {
            echo "ℹ️  Tabla ya existe: {$tableName}\n";
            return;
        }
        
        echo "📦 Creando tabla: {$tableName}\n";
        
        $this->createTable($tableName, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->null(),
            'action_type' => $this->string(50)->notNull(),
            'action_subtype' => $this->string(50)->null(),
            'status' => $this->string(20)->notNull(), // success, failure, warning
            'ip_address' => $this->string(45)->notNull(),
            'user_agent' => $this->text()->null(),
            'details' => $this->text()->null(),
            'risk_score' => $this->integer()->defaultValue(0),
            'flagged' => $this->boolean()->defaultValue(false),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        
        // Índices
        $this->createIndex('idx_audit_log_user_id', $tableName, 'user_id');
        $this->createIndex('idx_audit_log_action_type', $tableName, 'action_type');
        $this->createIndex('idx_audit_log_status', $tableName, 'status');
        $this->createIndex('idx_audit_log_created_at', $tableName, 'created_at');
        $this->createIndex('idx_audit_log_flagged', $tableName, 'flagged');
        $this->createIndex('idx_audit_log_user_action_date', $tableName, ['user_id', 'action_type', 'created_at']);
        $this->createIndex('idx_audit_log_ip_date', $tableName, ['ip_address', 'created_at']);
        
        // Clave foránea opcional
        $this->addForeignKey(
            'fk_audit_log_user_id',
            $tableName,
            'user_id',
            'seguridad.user',
            'id',
            'SET NULL',
            'CASCADE'
        );
        
        echo "✅ Tabla creada: {$tableName}\n";
    }
    
    /**
     * Crear tabla password_history
     */
    private function createPasswordHistoryTable()
    {
        $tableName = 'seguridad.password_history';
        
        if ($this->tableExists($tableName)) {
            echo "ℹ️  Tabla ya existe: {$tableName}\n";
            return;
        }
        
        echo "📦 Creando tabla: {$tableName}\n";
        
        $this->createTable($tableName, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'changed_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'changed_by_ip' => $this->string(45)->null(),
            'reason' => $this->string(50)->null(), // first_access, expired, manual, admin
        ]);
        
        // Índices
        $this->createIndex('idx_password_history_user_id', $tableName, 'user_id');
        $this->createIndex('idx_password_history_changed_at', $tableName, 'changed_at');
        
        // Clave foránea
        $this->addForeignKey(
            'fk_password_history_user_id',
            $tableName,
            'user_id',
            'seguridad.user',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        echo "✅ Tabla creada: {$tableName}\n";
    }
    
    /**
     * Crear tabla user_block_history
     */
    private function createUserBlockHistoryTable()
    {
        $tableName = 'seguridad.user_block_history';
        
        if ($this->tableExists($tableName)) {
            echo "ℹ️  Tabla ya existe: {$tableName}\n";
            return;
        }
        
        echo "📦 Creando tabla: {$tableName}\n";
        
        $this->createTable($tableName, [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'block_type' => $this->string(20)->notNull(), // temporary_24h, permanent_admin
            'block_reason' => $this->string(255)->notNull(),
            'blocked_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'blocked_until' => $this->timestamp()->null(),
            'unblocked_at' => $this->timestamp()->null(),
            'unblocked_by' => $this->integer()->null(),
            'ip_address' => $this->string(45)->null(),
            'details' => $this->text()->null(),
            'notified_admin' => $this->boolean()->defaultValue(false),
            'notified_user' => $this->boolean()->defaultValue(false),
        ]);
        
        // Índices
        $this->createIndex('idx_block_history_user_id', $tableName, 'user_id');
        $this->createIndex('idx_block_history_block_type', $tableName, 'block_type');
        $this->createIndex('idx_block_history_blocked_at', $tableName, 'blocked_at');
        $this->createIndex('idx_block_history_blocked_until', $tableName, 'blocked_until');
        
        // Claves foráneas
        $this->addForeignKey(
            'fk_block_history_user_id',
            $tableName,
            'user_id',
            'seguridad.user',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk_block_history_unblocked_by',
            $tableName,
            'unblocked_by',
            'seguridad.user',
            'id',
            'SET NULL',
            'CASCADE'
        );
        
        echo "✅ Tabla creada: {$tableName}\n";
    }
    
    /**
     * Crear tabla login_attempt
     */
    private function createLoginAttemptTable()
    {
        $tableName = 'seguridad.login_attempt';
        
        if ($this->tableExists($tableName)) {
            echo "ℹ️  Tabla ya existe: {$tableName}\n";
            return;
        }
        
        echo "📦 Creando tabla: {$tableName}\n";
        
        $this->createTable($tableName, [
            'id' => $this->primaryKey(),
            'username' => $this->string(100)->notNull(),
            'user_id' => $this->integer()->null(),
            'status' => $this->string(20)->notNull(), // success, invalid_password, user_not_found, blocked
            'ip_address' => $this->string(45)->notNull(),
            'user_agent' => $this->text()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        
        // Índices
        $this->createIndex('idx_login_attempt_username', $tableName, 'username');
        $this->createIndex('idx_login_attempt_user_id', $tableName, 'user_id');
        $this->createIndex('idx_login_attempt_status', $tableName, 'status');
        $this->createIndex('idx_login_attempt_ip_address', $tableName, 'ip_address');
        $this->createIndex('idx_login_attempt_created_at', $tableName, 'created_at');
        $this->createIndex('idx_login_attempt_ip_time', $tableName, ['ip_address', 'created_at']);
        $this->createIndex('idx_login_attempt_username_time', $tableName, ['username', 'created_at']);
        
        echo "✅ Tabla creada: {$tableName}\n";
    }
    
    /**
     * Verificar si una tabla existe
     */
    private function tableExists($tableName)
    {
        $schema = $this->db->schema->getTableSchema($tableName);
        return $schema !== null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "\n⚠️  REVERTIENDO MIGRACIÓN CONSOLIDADA\n";
        echo "📅 " . date('Y-m-d H:i:s') . "\n";
        
        // NOTA: No revertimos porque es una migración consolidada
        // Si necesitas revertir, debes hacerlo manualmente o usar migraciones individuales
        echo "❌ Esta migración consolidada no se puede revertir automáticamente.\n";
        echo "📋 Si necesitas revertir, elimina manualmente las tablas:\n";
        echo "   - seguridad.verification_session\n";
        echo "   - seguridad.audit_log\n";
        echo "   - seguridad.password_history\n";
        echo "   - seguridad.user_block_history\n";
        echo "   - seguridad.login_attempt\n";
        
        return false;
    }
}