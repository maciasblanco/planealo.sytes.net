<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * LoginAttempt model
 * 
 * @property int $id
 * @property string $username
 * @property int|null $user_id
 * @property string $status
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $created_at
 */
class LoginAttempt extends ActiveRecord
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_BLOCKED = 'blocked';
    
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
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null,
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'status', 'ip_address'], 'required'],
            [['user_id'], 'integer'],
            [['user_agent'], 'string'],
            [['created_at'], 'safe'],
            [['username'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 20],
            [['ip_address'], 'string', 'max' => 45],
            ['status', 'in', 'range' => [self::STATUS_SUCCESS, self::STATUS_FAILED, self::STATUS_BLOCKED]],
            [['user_id'], 'exist', 'skipOnError' => true, 
                'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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
            'user_id' => 'ID Usuario',
            'status' => 'Estado',
            'ip_address' => 'Dirección IP',
            'user_agent' => 'Agente de Usuario',
            'created_at' => 'Fecha de Creación',
        ];
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
    
    /**
     * Registra un intento de login exitoso
     * 
     * @param string $username
     * @param int|null $userId
     * @return bool
     */
    public static function recordSuccess($username, $userId = null)
    {
        $model = new self();
        $model->username = $username;
        $model->user_id = $userId;
        $model->status = self::STATUS_SUCCESS;
        $model->ip_address = Yii::$app->request->getUserIP();
        $model->user_agent = Yii::$app->request->getUserAgent();
        
        return $model->save();
    }
    
    /**
     * Registra un intento de login fallido
     * 
     * @param string $username
     * @param int|null $userId
     * @return bool
     */
    public static function recordFailure($username, $userId = null)
    {
        $model = new self();
        $model->username = $username;
        $model->user_id = $userId;
        $model->status = self::STATUS_FAILED;
        $model->ip_address = Yii::$app->request->getUserIP();
        $model->user_agent = Yii::$app->request->getUserAgent();
        
        return $model->save();
    }
    
    /**
     * Registra un bloqueo de login
     * 
     * @param string $username
     * @param int|null $userId
     * @return bool
     */
    public static function recordBlock($username, $userId = null)
    {
        $model = new self();
        $model->username = $username;
        $model->user_id = $userId;
        $model->status = self::STATUS_BLOCKED;
        $model->ip_address = Yii::$app->request->getUserIP();
        $model->user_agent = Yii::$app->request->getUserAgent();
        
        return $model->save();
    }
    
    /**
     * Obtiene intentos fallidos recientes por IP
     * 
     * @param string $ip
     * @param int $minutes
     * @return int
     */
    public static function getRecentFailuresByIp($ip, $minutes = 15)
    {
        $since = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));
        
        return self::find()
            ->where(['ip_address' => $ip])
            ->andWhere(['status' => self::STATUS_FAILED])
            ->andWhere(['>=', 'created_at', $since])
            ->count();
    }
    
    /**
     * Obtiene intentos fallidos recientes por usuario
     * 
     * @param string $username
     * @param int $minutes
     * @return int
     */
    public static function getRecentFailuresByUser($username, $minutes = 15)
    {
        $since = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));
        
        return self::find()
            ->where(['username' => $username])
            ->andWhere(['status' => self::STATUS_FAILED])
            ->andWhere(['>=', 'created_at', $since])
            ->count();
    }
    
    /**
     * Verifica si una IP está bloqueada (demasiados intentos)
     * 
     * @param string $ip
     * @param int $maxAttempts
     * @param int $minutes
     * @return bool
     */
    public static function isIpBlocked($ip, $maxAttempts = 5, $minutes = 15)
    {
        $failures = self::getRecentFailuresByIp($ip, $minutes);
        return $failures >= $maxAttempts;
    }
    
    /**
     * Verifica si un usuario tiene demasiados intentos fallidos
     * 
     * @param string $username
     * @param int $maxAttempts
     * @param int $minutes
     * @return bool
     */
    public static function isUserBlocked($username, $maxAttempts = 3, $minutes = 15)
    {
        $failures = self::getRecentFailuresByUser($username, $minutes);
        return $failures >= $maxAttempts;
    }
}