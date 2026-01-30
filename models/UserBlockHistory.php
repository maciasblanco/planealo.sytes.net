<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "seguridad.user_block_history".
 *
 * @property int $id
 * @property int $user_id
 * @property string $reason
 * @property string $blocked_until
 * @property int $block_count
 * @property string|null $notes
 * @property string|null $created_at
 *
 * @property User $user
 */
class UserBlockHistory extends \yii\db\ActiveRecord
{
    const REASON_VERIFICATION_ATTEMPTS = 'verification_attempts';
    const REASON_LOGIN_ATTEMPTS = 'login_attempts';
    const REASON_ADMIN = 'admin';
    const REASON_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seguridad.user_block_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'reason', 'blocked_until'], 'required'],
            [['user_id', 'block_count'], 'integer'],
            [['blocked_until', 'created_at'], 'safe'],
            [['reason', 'notes'], 'string', 'max' => 255],
            [['block_count'], 'default', 'value' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Usuario ID',
            'reason' => 'Motivo',
            'blocked_until' => 'Bloqueado Hasta',
            'block_count' => 'Número de Bloqueo',
            'notes' => 'Notas',
            'created_at' => 'Fecha de Creación',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Bloquea un usuario por un período específico
     * 
     * @param int $userId ID del usuario
     * @param string $reason Motivo del bloqueo
     * @param int $hours Horas de bloqueo (0 = permanente)
     * @param string|null $notes Notas adicionales
     * @return bool
     */
    public static function blockUser($userId, $reason, $hours = 24, $notes = null)
    {
        // Obtener usuario para actualizar contador de bloqueos
        $user = User::findOne($userId);
        if (!$user) {
            Yii::error("No se encontró usuario con ID $userId para bloquear", 'security');
            return false;
        }

        // Crear registro de bloqueo
        $block = new self();
        $block->user_id = $userId;
        $block->reason = $reason;
        $block->block_count = $user->block_count + 1;
        $block->notes = $notes;
        
        // Calcular fecha de desbloqueo
        if ($hours > 0) {
            $block->blocked_until = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
        } else {
            // Bloqueo permanente (fecha muy futura)
            $block->blocked_until = '9999-12-31 23:59:59';
        }
        
        // Guardar registro de bloqueo
        if ($block->save()) {
            // Actualizar contador de bloqueos en el usuario
            $user->block_count = $block->block_count;
            $user->save(false, ['block_count', 'updated_at']);
            
            // Registrar en log de auditoría
            AuditLog::log($userId, AuditLog::ACTION_BLOCK, 
                "Usuario bloqueado por $reason por $hours horas. Bloqueo #" . $block->block_count);
            
            return true;
        }
        
        return false;
    }

    /**
     * Verifica si un usuario está actualmente bloqueado
     * 
     * @param int $userId ID del usuario
     * @return bool
     */
    public static function isUserBlocked($userId)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->andWhere(['>', 'blocked_until', date('Y-m-d H:i:s')])
            ->exists();
    }

    /**
     * Obtiene el bloqueo activo de un usuario
     * 
     * @param int $userId ID del usuario
     * @return UserBlockHistory|null
     */
    public static function getActiveBlock($userId)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->andWhere(['>', 'blocked_until', date('Y-m-d H:i:s')])
            ->orderBy(['blocked_until' => SORT_DESC])
            ->one();
    }

    /**
     * Desbloquea a un usuario inmediatamente
     * 
     * @param int $userId ID del usuario
     * @return int Número de registros actualizados
     */
    public static function unblockUser($userId)
    {
        // Actualizar todos los bloqueos activos para que expiren ahora
        $updated = self::updateAll(
            ['blocked_until' => date('Y-m-d H:i:s', strtotime('-1 minute'))],
            [
                'user_id' => $userId,
                ['>', 'blocked_until', date('Y-m-d H:i:s')]
            ]
        );
        
        if ($updated > 0) {
            // Registrar en log de auditoría
            AuditLog::log($userId, AuditLog::ACTION_UNBLOCK, "Usuario desbloqueado manualmente");
        }
        
        return $updated;
    }

    /**
     * Obtiene las etiquetas para los motivos de bloqueo
     * 
     * @return array
     */
    public static function getReasonLabels()
    {
        return [
            self::REASON_VERIFICATION_ATTEMPTS => 'Intentos de verificación excedidos',
            self::REASON_LOGIN_ATTEMPTS => 'Intentos de login fallidos',
            self::REASON_ADMIN => 'Bloqueo administrativo',
            self::REASON_SUSPICIOUS_ACTIVITY => 'Actividad sospechosa',
        ];
    }

    /**
     * Obtiene la etiqueta de un motivo específico
     * 
     * @param string $reason
     * @return string
     */
    public static function getReasonLabel($reason)
    {
        $labels = self::getReasonLabels();
        return isset($labels[$reason]) ? $labels[$reason] : $reason;
    }

    /**
     * Obtiene el tiempo restante de bloqueo en formato legible
     * 
     * @return string
     */
    public function getTimeRemaining()
    {
        $now = time();
        $until = strtotime($this->blocked_until);
        
        if ($until <= $now) {
            return 'Desbloqueado';
        }
        
        $diff = $until - $now;
        
        if ($diff >= 86400) { // Más de 1 día
            $days = floor($diff / 86400);
            $hours = floor(($diff % 86400) / 3600);
            return "$days día(s) $hours hora(s)";
        } elseif ($diff >= 3600) { // Más de 1 hora
            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);
            return "$hours hora(s) $minutes minuto(s)";
        } else { // Menos de 1 hora
            $minutes = floor($diff / 60);
            $seconds = $diff % 60;
            return "$minutes minuto(s) $seconds segundo(s)";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->created_at)) {
                $this->created_at = date('Y-m-d H:i:s');
            }
            return true;
        }
        return false;
    }
}
?>