<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "seguridad.verification_session".
 *
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string $code
 * @property int $attempts
 * @property int $codes_generated
 * @property string $session_token
 * @property string $expires_at
 * @property string $status
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property User $user
 */
class VerificationSession extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_EXPIRED = 'expired';
    const STATUS_BLOCKED = 'blocked';

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
            [['user_id', 'email', 'code', 'session_token', 'expires_at'], 'required'],
            [['user_id', 'attempts', 'codes_generated'], 'integer'],
            [['expires_at', 'created_at', 'updated_at'], 'safe'],
            [['email'], 'string', 'max' => 255],
            [['code'], 'string', 'length' => 6],
            [['session_token'], 'string', 'max' => 64],
            [['status'], 'string', 'max' => 20],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['attempts'], 'default', 'value' => 0],
            [['codes_generated'], 'default', 'value' => 1],
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
            'email' => 'Email',
            'code' => 'Código de Verificación',
            'attempts' => 'Intentos Fallidos',
            'codes_generated' => 'Códigos Generados',
            'session_token' => 'Token de Sesión',
            'expires_at' => 'Expira En',
            'status' => 'Estado',
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
        return strtotime($this->expires_at) < time();
    }

    /**
     * Verifica si se excedió el límite de intentos (máximo 3)
     * @return bool
     */
    public function isMaxAttemptsReached()
    {
        return $this->attempts >= 3;
    }

    /**
     * Verifica si se excedió el límite de códigos generados (máximo 5)
     * @return bool
     */
    public function isMaxCodesReached()
    {
        return $this->codes_generated >= 5;
    }

    /**
     * Incrementa el contador de intentos fallidos
     * @return bool
     */
    public function incrementAttempts()
    {
        $this->attempts++;
        return $this->save(false, ['attempts', 'updated_at']);
    }

    /**
     * Incrementa el contador de códigos generados
     * @return bool
     */
    public function incrementCodesGenerated()
    {
        $this->codes_generated++;
        return $this->save(false, ['codes_generated', 'updated_at']);
    }

    /**
     * Genera un nuevo código de verificación
     * @return string
     */
    public function generateNewCode()
    {
        $this->code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->attempts = 0;
        $this->expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        return $this->code;
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
            }
            $this->updated_at = $now;
            return true;
        }
        return false;
    }
}
?>