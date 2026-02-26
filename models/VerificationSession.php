<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "seguridad.verification_session".
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_token
 * @property string|null $email
 * @property string|null $verification_code
 * @property string|null $code_sent_at
 * @property string|null $code_expires_at
 * @property int|null $attempts_remaining
 * @property int|null $codes_sent_count
 * @property string|null $status
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property User $user
 */
class VerificationSession extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 'PENDIENTE';
    const STATUS_VERIFIED = 'VERIFICADO';
    const STATUS_EXPIRED = 'VENCIDO';
    const STATUS_BLOCKED = 'BLOQUEADO';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seguridad.verification_session';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'session_token', 'ip_address'], 'required'],
            [['user_id', 'attempts_remaining', 'codes_sent_count'], 'integer'],
            [['code_sent_at', 'code_expires_at', 'created_at', 'updated_at'], 'safe'],
            [['user_agent'], 'string'],
            [['session_token'], 'string', 'max' => 64],
            [['email', 'ip_address'], 'string', 'max' => 255],
            [['verification_code'], 'string', 'length' => 6],
            [['status'], 'string', 'max' => 20],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['attempts_remaining'], 'default', 'value' => 3],
            [['codes_sent_count'], 'default', 'value' => 1],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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
            'session_token' => 'Token de Sesión',
            'email' => 'Email',
            'verification_code' => 'Código de Verificación',
            'code_sent_at' => 'Fecha de Envío',
            'code_expires_at' => 'Fecha de Expiración',
            'attempts_remaining' => 'Intentos Restantes',
            'codes_sent_count' => 'Códigos Enviados',
            'status' => 'Estado',
            'ip_address' => 'Dirección IP',
            'user_agent' => 'User Agent',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
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
     * Verifica si la sesión está expirada
     * @return bool
     */
    public function isExpired()
    {
        return $this->code_expires_at && strtotime($this->code_expires_at) < time();
    }

    /**
     * Verifica si se excedió el límite de intentos (máximo 3)
     * @return bool
     */
    public function isMaxAttemptsReached()
    {
        return $this->attempts_remaining <= 0;
    }

    /**
     * Verifica si se excedió el límite de códigos generados (máximo 5)
     * @return bool
     */
    public function isMaxCodesReached()
    {
        return $this->codes_sent_count >= 5;
    }

    /**
     * Decrementa el contador de intentos restantes (cuando falla)
     * @return bool
     */
    public function decrementAttempts()
    {
        if ($this->attempts_remaining > 0) {
            $this->attempts_remaining--;
        }
        return $this->save(false, ['attempts_remaining', 'updated_at']);
    }

    /**
     * Incrementa el contador de códigos enviados
     * @return bool
     */
    public function incrementCodesSent()
    {
        $this->codes_sent_count++;
        return $this->save(false, ['codes_sent_count', 'updated_at']);
    }

    /**
     * Genera un nuevo código de verificación y actualiza fechas
     * @return string
     */
    public function generateNewCode()
    {
        $this->verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->attempts_remaining = 3;
        $this->code_sent_at = date('Y-m-d H:i:s');
        $this->code_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        return $this->verification_code;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $now = date('Y-m-d H:i:s');
            if ($insert) {
                $this->created_at = $now;
                if (!$this->code_sent_at) {
                    $this->code_sent_at = $now;
                }
            }
            $this->updated_at = $now;
            return true;
        }
        return false;
    }
}
?>