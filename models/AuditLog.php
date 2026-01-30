<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Class AuditLog
 * @property int $id
 * @property int $user_id
 * @property string $action_type  // ✅ CAMBIADO: action -> action_type
 * @property string|null $details
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $created_at
 */
class AuditLog extends ActiveRecord
{
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_LOGIN_ATTEMPT = 'login_attempt';
    const ACTION_VERIFICATION_CODE_SENT = 'verification_code_sent';
    const ACTION_VERIFICATION_CODE_VALIDATED = 'verification_code_validated';
    const ACTION_VERIFICATION_CODE_FAILED = 'verification_code_failed';
    const ACTION_PASSWORD_CHANGED = 'password_changed';
    const ACTION_ACCOUNT_LOCKED = 'account_locked';
    const ACTION_SESSION_EXPIRED = 'session_expired';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seguridad.audit_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'action_type'], 'required'],  // ✅ CAMBIADO: action -> action_type
            [['user_id'], 'integer'],
            [['details'], 'string'],
            [['created_at'], 'safe'],
            [['action_type', 'ip_address', 'user_agent'], 'string', 'max' => 255],  // ✅ CAMBIADO
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Usuario',
            'action_type' => 'Tipo de Acción',  // ✅ CAMBIADO
            'details' => 'Detalles',
            'ip_address' => 'Dirección IP',
            'user_agent' => 'Agente de Usuario',
            'created_at' => 'Fecha de Creación',
        ];
    }

    /**
     * Crea un registro en el log de auditoría.
     *
     * @param int $userId ID del usuario
     * @param string $actionType Tipo de acción  // ✅ CAMBIADO: $action -> $actionType
     * @param string|null $details Detalles adicionales
     * @param string|null $ipAddress Dirección IP (opcional)
     * @param string|null $userAgent Agente de usuario (opcional)
     * @return bool
     */
    public static function log($userId, $actionType, $details = null, $ipAddress = null, $userAgent = null)  // ✅ CAMBIADO
    {
        $log = new self();
        $log->user_id = $userId;
        $log->action_type = $actionType;  // ✅ CAMBIADO: action -> action_type
        $log->details = $details;
        $log->ip_address = $ipAddress ?: Yii::$app->request->getUserIP();
        $log->user_agent = $userAgent ?: Yii::$app->request->getUserAgent();
        $log->created_at = date('Y-m-d H:i:s');

        return $log->save();
    }

    /**
     * Obtiene los logs de un usuario específico.
     *
     * @param int $userId ID del usuario
     * @param int $limit Límite de registros
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getUserLogs($userId, $limit = 50)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Obtiene estadísticas de acciones de auditoría.
     *
     * @param string $period Periodo: 'today', 'week', 'month'
     * @return array
     */
    public static function getStatistics($period = 'month')
    {
        $query = self::find();

        // Filtrar por período
        $now = date('Y-m-d H:i:s');
        switch ($period) {
            case 'today':
                $startDate = date('Y-m-d 00:00:00');
                break;
            case 'week':
                $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'month':
                $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            default:
                $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        }

        $query->andWhere(['>=', 'created_at', $startDate]);

        return [
            'total' => $query->count(),
            'by_action' => $query->select(['action_type', 'COUNT(*) as count'])
                ->groupBy('action_type')
                ->indexBy('action_type')
                ->column(),
            'by_user' => $query->select(['user_id', 'COUNT(*) as count'])
                ->groupBy('user_id')
                ->orderBy(['count' => SORT_DESC])
                ->limit(10)
                ->indexBy('user_id')
                ->column(),
        ];
    }

    /**
     * Limpia registros antiguos de auditoría.
     *
     * @param int $daysToKeep Días a mantener (por defecto 90)
     * @return int Número de registros eliminados
     */
    public static function cleanupOldLogs($daysToKeep = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$daysToKeep days"));
        
        return self::deleteAll(['<', 'created_at', $cutoffDate]);
    }

    /**
     * Relación con el usuario.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Obtiene el nombre amigable de la acción.
     *
     * @return string
     */
    public function getActionLabel()
    {
        $labels = [
            self::ACTION_LOGIN => 'Inicio de sesión',
            self::ACTION_LOGOUT => 'Cierre de sesión',
            self::ACTION_LOGIN_ATTEMPT => 'Intento de inicio de sesión',
            self::ACTION_VERIFICATION_CODE_SENT => 'Código de verificación enviado',
            self::ACTION_VERIFICATION_CODE_VALIDATED => 'Código de verificación validado',
            self::ACTION_VERIFICATION_CODE_FAILED => 'Código de verificación fallido',
            self::ACTION_PASSWORD_CHANGED => 'Contraseña cambiada',
            self::ACTION_ACCOUNT_LOCKED => 'Cuenta bloqueada',
            self::ACTION_SESSION_EXPIRED => 'Sesión expirada',
        ];

        return $labels[$this->action_type] ?? $this->action_type;
    }

    /**
     * Obtiene el icono correspondiente a la acción.
     *
     * @return string
     */
    public function getActionIcon()
    {
        $icons = [
            self::ACTION_LOGIN => 'fas fa-sign-in-alt text-success',
            self::ACTION_LOGOUT => 'fas fa-sign-out-alt text-warning',
            self::ACTION_LOGIN_ATTEMPT => 'fas fa-key text-info',
            self::ACTION_VERIFICATION_CODE_SENT => 'fas fa-envelope text-primary',
            self::ACTION_VERIFICATION_CODE_VALIDATED => 'fas fa-check-circle text-success',
            self::ACTION_VERIFICATION_CODE_FAILED => 'fas fa-times-circle text-danger',
            self::ACTION_PASSWORD_CHANGED => 'fas fa-lock text-success',
            self::ACTION_ACCOUNT_LOCKED => 'fas fa-lock text-danger',
            self::ACTION_SESSION_EXPIRED => 'fas fa-clock text-warning',
        ];

        return $icons[$this->action_type] ?? 'fas fa-history';
    }
}