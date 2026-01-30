<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $cedula
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property boolean $email_verified
 * @property integer $block_count
 * @property string $last_password_change
 * @property boolean $requires_password_change
 * @property string $temporal_email
 * @property string $real_email
 * @property string $blocked_until
 * @property string $last_login_at
 * @property string $last_failed_login
 * @property integer $failed_login_attempts
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const STATUS_BLOCKED = 20;

    const MAX_VERIFICATION_SESSIONS = 5; // Máximo 5 códigos en 24 horas
    const MAX_CODE_ATTEMPTS = 3; // Máximo 3 intentos por código
    const CODE_VALIDITY_MINUTES = 15; // 15 minutos de validez

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seguridad.user';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'auth_key', 'password_hash', 'email'], 'required'],
            [['status', 'created_at', 'updated_at', 'block_count', 'failed_login_attempts'], 'default', 'value' => null],
            [['status', 'created_at', 'updated_at', 'block_count', 'failed_login_attempts'], 'integer'],
            [['email_verified', 'requires_password_change'], 'boolean'],
            [['last_password_change', 'blocked_until', 'last_login_at', 'last_failed_login'], 'safe'],
            [['username'], 'string', 'max' => 32],
            [['auth_key'], 'string', 'max' => 32],
            [['password_hash', 'password_reset_token', 'email', 'temporal_email', 'real_email'], 'string', 'max' => 255],
            [['cedula'], 'string', 'max' => 20],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['cedula'], 'unique'],
            [['password_reset_token'], 'unique'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED, self::STATUS_BLOCKED]],
            ['email_verified', 'default', 'value' => false],
            ['block_count', 'default', 'value' => 0],
            ['requires_password_change', 'default', 'value' => true],
            ['failed_login_attempts', 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Usuario',
            'cedula' => 'Cédula',
            'email' => 'Correo Electrónico',
            'status' => 'Estado',
            'created_at' => 'Creado En',
            'updated_at' => 'Actualizado En',
            'email_verified' => 'Correo Verificado',
            'block_count' => 'Contador de Bloqueos',
            'last_password_change' => 'Último Cambio de Contraseña',
            'requires_password_change' => 'Requiere Cambio de Contraseña',
            'temporal_email' => 'Correo Temporal',
            'real_email' => 'Correo Real',
            'blocked_until' => 'Bloqueado Hasta',
            'last_login_at' => 'Último Inicio de Sesión',
            'last_failed_login' => 'Último Intento Fallido',
            'failed_login_attempts' => 'Intentos Fallidos de Inicio de Sesión',
        ];
    }

    // ==================== RELACIONES CON NUEVAS TABLAS ====================

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVerificationSessions()
    {
        return $this->hasMany(VerificationSession::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuditLogs()
    {
        return $this->hasMany(AuditLog::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPasswordHistories()
    {
        return $this->hasMany(PasswordHistory::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserBlockHistories()
    {
        return $this->hasMany(UserBlockHistory::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLoginAttempts()
    {
        return $this->hasMany(LoginAttempt::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveVerificationSession()
    {
        return $this->hasOne(VerificationSession::class, ['user_id' => 'id'])
            ->andWhere(['>', 'expires_at', time()])
            ->andWhere(['status' => VerificationSession::STATUS_PENDING]);
    }

    // ==================== MÉTODOS DE NEGOCIO ====================

    /**
     * Determina si es el primer acceso del usuario
     * 
     * @return bool
     */
    public function isFirstAccess()
    {
        return $this->isEmailTemporal() && !$this->email_verified;
    }

    /**
     * Verifica si el email del usuario es temporal (@temporal.com)
     * 
     * @return bool
     */
    public function isEmailTemporal()
    {
        return strpos($this->email, '@temporal.com') !== false;
    }

    /**
     * Verifica si el usuario necesita cambiar su contraseña
     * 
     * @return bool
     */
    public function needsPasswordChange()
    {
        return $this->requires_password_change || $this->isPasswordGeneric();
    }

    /**
     * Verifica si la contraseña es la genérica "12345-aves"
     * 
     * @return bool
     */
    public function isPasswordGeneric()
    {
        $genericPassword = '12345-aves';
        return Yii::$app->security->validatePassword($genericPassword, $this->password_hash);
    }

    /**
     * Verifica si el usuario está actualmente bloqueado
     * 
     * @return bool
     */
    public function isBlocked()
    {
        if ($this->status == self::STATUS_BLOCKED) {
            return true;
        }
        
        if ($this->blocked_until && strtotime($this->blocked_until) > time()) {
            return true;
        }
        
        return false;
    }

    /**
     * Obtiene el bloqueo activo del usuario
     * 
     * @return UserBlockHistory|null
     */
    public function getActiveBlock()
    {
        return UserBlockHistory::find()
            ->where(['user_id' => $this->id])
            ->andWhere(['>', 'blocked_until', date('Y-m-d H:i:s')])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    /**
     * Incrementa el contador de bloqueos del usuario
     * 
     * @return bool
     */
    public function incrementBlockCount()
    {
        $this->block_count = ($this->block_count ?: 0) + 1;
        
        // Bloqueo escalonado
        if ($this->block_count == 1) {
            $blockDuration = 24 * 60 * 60; // 24 horas
        } elseif ($this->block_count == 2) {
            $blockDuration = 48 * 60 * 60; // 48 horas
        } else {
            $blockDuration = 7 * 24 * 60 * 60; // 1 semana (3er bloqueo)
        }
        
        $this->blocked_until = date('Y-m-d H:i:s', time() + $blockDuration);
        
        // Registrar en historial
        $blockHistory = new UserBlockHistory();
        $blockHistory->user_id = $this->id;
        $blockHistory->blocked_until = $this->blocked_until;
        $blockHistory->reason = 'Excedió intentos de verificación';
        $blockHistory->created_at = time();
        
        if ($blockHistory->save()) {
            AuditLog::log($this->id, 'user_blocked', 'Usuario bloqueado por exceder intentos de verificación');
        }
        
        return $this->save(false, ['block_count', 'blocked_until', 'updated_at']);
    }

    /**
     * Marca el email del usuario como verificado
     * 
     * @return bool
     */
    public function markEmailAsVerified()
    {
        $this->email_verified = true;
        $this->real_email = $this->email; // Guardar el email real
        return $this->save(false, ['email_verified', 'real_email', 'updated_at']);
    }

    /**
     * Cambia la contraseña del usuario con validaciones de seguridad
     * 
     * @param string $newPassword Nueva contraseña
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePasswordWithValidation($newPassword)
    {
        // Validar que no sea la contraseña genérica
        if ($newPassword === '12345-aves') {
            return ['success' => false, 'message' => 'No puede usar la contraseña genérica'];
        }
        
        // Validar longitud mínima
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }
        
        // Validar complejidad
        if (!preg_match('/[A-Z]/', $newPassword)) {
            return ['success' => false, 'message' => 'Debe contener al menos una mayúscula'];
        }
        
        if (!preg_match('/[a-z]/', $newPassword)) {
            return ['success' => false, 'message' => 'Debe contener al menos una minúscula'];
        }
        
        if (!preg_match('/[0-9]/', $newPassword)) {
            return ['success' => false, 'message' => 'Debe contener al menos un número'];
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            return ['success' => false, 'message' => 'Debe contener al menos un carácter especial'];
        }
        
        // Verificar si ya fue usada (últimas 5)
        $lastPasswords = PasswordHistory::find()
            ->where(['user_id' => $this->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();
            
        foreach ($lastPasswords as $oldPassword) {
            if (Yii::$app->security->validatePassword($newPassword, $oldPassword->password_hash)) {
                return ['success' => false, 'message' => 'Esta contraseña ya fue utilizada anteriormente'];
            }
        }
        
        // Cambiar contraseña
        $oldPasswordHash = $this->password_hash;
        $this->setPassword($newPassword);
        $this->last_password_change = date('Y-m-d H:i:s');
        $this->requires_password_change = false;
        
        if ($this->save()) {
            // Registrar en historial
            PasswordHistory::addToHistory($this->id, $oldPasswordHash);
            
            // Registrar en log de auditoría
            AuditLog::log($this->id, 'password_changed', 'Contraseña cambiada exitosamente');
            
            return ['success' => true, 'message' => 'Contraseña cambiada exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al cambiar la contraseña'];
    }

    /**
     * Crea una nueva sesión de verificación para el usuario
     * 
     * @param string $type Tipo de verificación (email, phone, etc.)
     * @return VerificationSession|false
     */
    public function createVerificationSession($type = 'email')
    {
        // Verificar límite de sesiones (máximo 5 en 24 horas)
        $last24Hours = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $sessionCount = VerificationSession::find()
            ->where(['user_id' => $this->id])
            ->andWhere(['>=', 'created_at', $last24Hours])
            ->count();
            
        if ($sessionCount >= self::MAX_VERIFICATION_SESSIONS) {
            $this->incrementBlockCount();
            return false;
        }
        
        // Crear sesión
        $session = new VerificationSession();
        $session->user_id = $this->id;
        $session->token = Yii::$app->security->generateRandomString(32);
        $session->code = sprintf("%06d", mt_rand(0, 999999));
        $session->type = $type;
        $session->status = VerificationSession::STATUS_PENDING;
        $session->attempts = 0;
        $session->created_at = time();
        $session->expires_at = time() + (self::CODE_VALIDITY_MINUTES * 60);
        
        if ($session->save()) {
            AuditLog::log($this->id, 'verification_sent', 'Código de verificación enviado');
            return $session;
        }
        
        return false;
    }

    /**
     * Obtiene el tiempo restante hasta que expire el bloqueo
     * 
     * @return string
     */
    public function getBlockTimeRemaining()
    {
        if ($this->blocked_until) {
            $remaining = strtotime($this->blocked_until) - time();
            if ($remaining > 0) {
                $hours = floor($remaining / 3600);
                $minutes = floor(($remaining % 3600) / 60);
                $seconds = $remaining % 60;
                return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
            }
        }
        return '00:00:00';
    }

    /**
     * Obtiene estadísticas de seguridad del usuario
     * 
     * @return array
     */
    public function getSecurityStats()
    {
        $lastWeek = date('Y-m-d H:i:s', strtotime('-1 week'));
        
        return [
            'block_count' => $this->block_count ?: 0,
            'last_password_change' => $this->last_password_change,
            'email_verified' => $this->email_verified,
            'login_attempts_last_week' => LoginAttempt::find()
                ->where(['user_id' => $this->id])
                ->andWhere(['>=', 'attempted_at', $lastWeek])
                ->count(),
            'failed_logins_last_week' => LoginAttempt::find()
                ->where(['user_id' => $this->id, 'success' => false])
                ->andWhere(['>=', 'attempted_at', $lastWeek])
                ->count(),
            'active_block' => $this->getActiveBlock() ? $this->getActiveBlock()->blocked_until : null,
            'block_time_remaining' => $this->getBlockTimeRemaining(),
        ];
    }

    /**
     * Registra un intento de login fallido
     */
    public function recordFailedLogin()
    {
        $this->failed_login_attempts = ($this->failed_login_attempts ?: 0) + 1;
        $this->last_failed_login = date('Y-m-d H:i:s');
        $this->save(false, ['failed_login_attempts', 'last_failed_login', 'updated_at']);
        
        $loginAttempt = new LoginAttempt();
        $loginAttempt->user_id = $this->id;
        $loginAttempt->username = $this->username;
        $loginAttempt->ip_address = Yii::$app->request->getUserIP();
        $loginAttempt->user_agent = Yii::$app->request->getUserAgent();
        $loginAttempt->successful = false;
        $loginAttempt->attempted_at = date('Y-m-d H:i:s');
        $loginAttempt->save();
        
        // Bloquear después de 5 intentos fallidos
        if ($this->failed_login_attempts >= 5) {
            $this->incrementBlockCount();
        }
    }

    /**
     * Resetea los intentos fallidos
     */
    public function resetFailedAttempts()
    {
        $this->failed_login_attempts = 0;
        $this->save(false, ['failed_login_attempts', 'updated_at']);
    }

    /**
     * Registra login exitoso
     */
    public function recordSuccessfulLogin()
    {
        $this->last_login_at = date('Y-m-d H:i:s');
        $this->resetFailedAttempts();
        $this->save(false, ['last_login_at', 'updated_at']);
        
        $loginAttempt = new LoginAttempt();
        $loginAttempt->user_id = $this->id;
        $loginAttempt->username = $this->username;
        $loginAttempt->ip_address = Yii::$app->request->getUserIP();
        $loginAttempt->user_agent = Yii::$app->request->getUserAgent();
        $loginAttempt->successful = true;
        $loginAttempt->attempted_at = date('Y-m-d H:i:s');
        $loginAttempt->save();
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by cédula
     *
     * @param string $cedula
     * @return static|null
     */
    public static function findByCedula($cedula)
    {
        return static::findOne(['cedula' => $cedula, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'] ?? 3600;
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * Asignar rol al usuario
     */
    public function asignarRol($rol)
    {
        try {
            $auth = Yii::$app->authManager;
            $role = $auth->getRole($rol);
            
            if ($role) {
                $auth->assign($role, $this->id);
                return true;
            }
            Yii::warning("Rol '{$rol}' no encontrado", 'user');
            return false;
        } catch (\Exception $e) {
            Yii::error("Error asignando rol {$rol} al usuario {$this->id}: " . $e->getMessage(), 'user');
            return false;
        }
    }

    /**
     * Remover todos los roles del usuario
     */
    public function revocarRoles()
    {
        try {
            $auth = Yii::$app->authManager;
            $auth->revokeAll($this->id);
            return true;
        } catch (\Exception $e) {
            Yii::error("Error revocando roles del usuario {$this->id}: " . $e->getMessage(), 'user');
            return false;
        }
    }

    /**
     * Obtener los roles del usuario
     */
    public function getRoles()
    {
        try {
            $auth = Yii::$app->authManager;
            return $auth->getRolesByUser($this->id);
        } catch (\Exception $e) {
            Yii::error("Error obteniendo roles del usuario {$this->id}: " . $e->getMessage(), 'user');
            return [];
        }
    }

    /**
     * Crea un usuario automáticamente con la cédula como username y un email opcional.
     * Si no se proporciona email, se genera uno por defecto.
     * 
     * @param string $cedula
     * @param string|null $email
     * @param string|null $nombreCompleto
     * @param string|null $rol
     * @return User|null
     */
    public static function crearUsuarioAutomatico($cedula, $email = null, $nombreCompleto = null, $rol = null)
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Verificar si ya existe un usuario con esa cédula
            $existingUser = static::findByCedula($cedula);
            if ($existingUser) {
                Yii::warning("Ya existe un usuario con la cédula: $cedula", 'user');
                $transaction->commit();
                return $existingUser;
            }

            // Verificar si ya existe un usuario con ese username (cedula)
            $existingUserByUsername = static::findByUsername($cedula);
            if ($existingUserByUsername) {
                Yii::warning("Ya existe un usuario con el username (cédula): $cedula", 'user');
                $transaction->commit();
                return $existingUserByUsername;
            }

            $user = new User();
            $user->username = $cedula;
            $user->cedula = $cedula;
            $user->email = $email ?? $cedula . '@sistema-ged.com';
            $user->status = self::STATUS_ACTIVE;
            $user->email_verified = false;
            $user->requires_password_change = true;

            // Generar auth_key y password
            $user->generateAuthKey();
            $password = '12345-aves'; // Contraseña genérica
            $user->setPassword($password);

            if ($user->save()) {
                if ($rol) {
                    $user->asignarRol($rol);
                }
                
                $transaction->commit();
                Yii::info("Usuario creado automáticamente: {$user->username} (ID: {$user->id})", 'user');
                return $user;
            } else {
                $transaction->rollBack();
                Yii::error('Error al guardar el usuario automático: ' . json_encode($user->getErrors()), 'user');
                return null;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Excepción al crear usuario automático: ' . $e->getMessage(), 'user');
            return null;
        }
    }

    /**
     * Crea un usuario con username, email y rol específicos
     * 
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string|null $rol
     * @return User|null
     */
    public static function crearUsuario($username, $email, $password, $rol = null)
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->status = self::STATUS_ACTIVE;
            $user->email_verified = false;
            $user->requires_password_change = true;

            // Generar auth_key y password
            $user->generateAuthKey();
            $user->setPassword($password);

            if ($user->save()) {
                if ($rol) {
                    $user->asignarRol($rol);
                }
                
                $transaction->commit();
                return $user;
            } else {
                $transaction->rollBack();
                Yii::error('Error al guardar el usuario: ' . json_encode($user->getErrors()), 'user');
                return null;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Excepción al crear usuario: ' . $e->getMessage(), 'user');
            return null;
        }
    }

    /**
     * Verificar si el usuario tiene un rol específico
     * 
     * @param string $rol
     * @return bool
     */
    public function tieneRol($rol)
    {
        try {
            $auth = Yii::$app->authManager;
            $roles = $auth->getRolesByUser($this->id);
            return isset($roles[$rol]);
        } catch (\Exception $e) {
            Yii::error("Error verificando rol {$rol} para usuario {$this->id}: " . $e->getMessage(), 'user');
            return false;
        }
    }

    /**
     * Obtener el nombre completo del usuario (si está disponible)
     * 
     * @return string
     */
    public function getNombreCompleto()
    {
        return $this->username;
    }

    /**
     * Activar usuario
     */
    public function activar()
    {
        $this->status = self::STATUS_ACTIVE;
        return $this->save(false, ['status']);
    }

    /**
     * Desactivar usuario
     */
    public function desactivar()
    {
        $this->status = self::STATUS_DELETED;
        return $this->save(false, ['status']);
    }

    /**
     * Verificar si el usuario está activo
     * 
     * @return bool
     */
    public function estaActivo()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}