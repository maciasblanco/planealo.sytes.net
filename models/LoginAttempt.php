<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "seguridad.login_attempt".
 *
 * @property int $id
 * @property string $username
 * @property bool $success
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $created_at
 */
class LoginAttempt extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seguridad.login_attempt';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'ip_address'], 'required'],
            [['success'], 'boolean'],
            [['created_at'], 'safe'],
            [['username', 'ip_address'], 'string', 'max' => 255],
            [['user_agent'], 'string', 'max' => 512],
            [['success'], 'default', 'value' => false],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Nombre de Usuario',
            'success' => 'Éxito',
            'ip_address' => 'Dirección IP',
            'user_agent' => 'Agente de Usuario',
            'created_at' => 'Fecha de Creación',
        ];
    }

    /**
     * Registra un intento de login
     * 
     * @param string $username Nombre de usuario
     * @param bool $success Éxito del intento
     * @param string|null $ipAddress Dirección IP (si null, se usa la actual)
     * @param string|null $userAgent User Agent (si null, se usa el actual)
     * @return bool
     */
    public static function logAttempt($username, $success, $ipAddress = null, $userAgent = null)
    {
        $attempt = new self();
        $attempt->username = $username;
        $attempt->success = $success;
        $attempt->ip_address = $ipAddress ?: Yii::$app->request->userIP;
        $attempt->user_agent = $userAgent ?: Yii::$app->request->userAgent;
        
        return $attempt->save();
    }

    /**
     * Cuenta los intentos fallidos recientes desde una IP específica
     * 
     * @param string $ipAddress Dirección IP
     * @param int $minutes Período en minutos (por defecto 30)
     * @return int
     */
    public static function getFailedAttemptsCountByIp($ipAddress, $minutes = 30)
    {
        return self::find()
            ->where(['ip_address' => $ipAddress, 'success' => false])
            ->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"))])
            ->count();
    }

    /**
     * Cuenta los intentos fallidos recientes para un usuario específico
     * 
     * @param string $username Nombre de usuario
     * @param int $minutes Período en minutos (por defecto 30)
     * @return int
     */
    public static function getFailedAttemptsCountByUsername($username, $minutes = 30)
    {
        return self::find()
            ->where(['username' => $username, 'success' => false])
            ->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"))])
            ->count();
    }

    /**
     * Verifica si una IP está bloqueada por demasiados intentos fallidos
     * 
     * @param string $ipAddress Dirección IP
     * @param int $maxAttempts Máximo de intentos permitidos (por defecto 5)
     * @param int $minutes Período en minutos (por defecto 30)
     * @return bool
     */
    public static function isIpBlocked($ipAddress, $maxAttempts = 5, $minutes = 30)
    {
        return self::getFailedAttemptsCountByIp($ipAddress, $minutes) >= $maxAttempts;
    }

    /**
     * Verifica si un usuario está bloqueado por demasiados intentos fallidos
     * 
     * @param string $username Nombre de usuario
     * @param int $maxAttempts Máximo de intentos permitidos (por defecto 3)
     * @param int $minutes Período en minutos (por defecto 30)
     * @return bool
     */
    public static function isUsernameBlocked($username, $maxAttempts = 3, $minutes = 30)
    {
        return self::getFailedAttemptsCountByUsername($username, $minutes) >= $maxAttempts;
    }

    /**
     * Limpia los intentos de login antiguos
     * 
     * @param int $days Días a mantener (por defecto 7)
     * @return int Número de registros eliminados
     */
    public static function cleanupOldAttempts($days = 7)
    {
        return self::deleteAll([
            '<', 'created_at', date('Y-m-d H:i:s', strtotime("-{$days} days"))
        ]);
    }

    /**
     * Obtiene estadísticas de intentos de login
     * 
     * @param int $days Días a considerar
     * @return array
     */
    public static function getStatistics($days = 7)
    {
        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $total = self::find()
            ->where(['>=', 'created_at', $dateFrom])
            ->count();
            
        $successful = self::find()
            ->where(['success' => true])
            ->andWhere(['>=', 'created_at', $dateFrom])
            ->count();
            
        $failed = self::find()
            ->where(['success' => false])
            ->andWhere(['>=', 'created_at', $dateFrom])
            ->count();
            
        $uniqueIps = self::find()
            ->select(['DISTINCT ip_address'])
            ->where(['>=', 'created_at', $dateFrom])
            ->count();
            
        $topFailedIps = self::find()
            ->select(['ip_address', 'COUNT(*) as attempts'])
            ->where(['success' => false])
            ->andWhere(['>=', 'created_at', $dateFrom])
            ->groupBy(['ip_address'])
            ->orderBy(['attempts' => SORT_DESC])
            ->limit(10)
            ->asArray()
            ->all();
            
        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'unique_ips' => $uniqueIps,
            'top_failed_ips' => $topFailedIps,
        ];
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