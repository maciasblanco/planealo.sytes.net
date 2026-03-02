<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "seguridad.password_history".
 *
 * @property int $id
 * @property int $user_id
 * @property string $password_hash
 * @property string|null $changed_at
 * @property string|null $changed_by_ip
 * @property string|null $reason
 *
 * @property User $user
 */
class PasswordHistory extends \yii\db\ActiveRecord
{
    const MAX_HISTORY = 5; // Mantener historial de las últimas 5 contraseñas

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'seguridad.password_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'password_hash'], 'required'],
            [['user_id'], 'integer'],
            [['changed_at'], 'safe'],
            [['password_hash'], 'string', 'max' => 255],
            [['changed_by_ip'], 'string', 'max' => 45],
            [['reason'], 'string', 'max' => 50],
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
            'password_hash' => 'Hash de Contraseña',
            'changed_at' => 'Fecha de Cambio',
            'changed_by_ip' => 'IP de Cambio',
            'reason' => 'Motivo',
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
     * Agrega un hash de contraseña al historial del usuario
     * 
     * @param int $userId ID del usuario
     * @param string $passwordHash Hash de la contraseña
     * @param string|null $reason Motivo del cambio
     * @return bool
     */
    public static function addToHistory($userId, $passwordHash, $reason = 'password_change')
    {
        $history = new self();
        $history->user_id = $userId;
        $history->password_hash = $passwordHash;
        $history->changed_by_ip = Yii::$app->request->userIP;
        $history->reason = $reason;
        
        if ($history->save()) {
            // Limitar el historial a MAX_HISTORY registros
            self::limitHistory($userId);
            return true;
        }
        
        return false;
    }

    /**
     * Verifica si una contraseña ya fue utilizada anteriormente por el usuario
     * 
     * @param int $userId ID del usuario
     * @param string $password (texto plano)
     * @return bool
     */
    public static function isPasswordUsed($userId, $password)
    {
        $histories = self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['changed_at' => SORT_DESC])
            ->limit(self::MAX_HISTORY)
            ->all();

        foreach ($histories as $history) {
            if (Yii::$app->security->validatePassword($password, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limita el historial de contraseñas a MAX_HISTORY registros
     * 
     * @param int $userId ID del usuario
     */
    private static function limitHistory($userId)
    {
        $records = self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['changed_at' => SORT_DESC])
            ->all();

        if (count($records) > self::MAX_HISTORY) {
            // Obtener IDs de registros a eliminar (los más antiguos)
            $idsToDelete = [];
            for ($i = self::MAX_HISTORY; $i < count($records); $i++) {
                $idsToDelete[] = $records[$i]->id;
            }
            
            if (!empty($idsToDelete)) {
                self::deleteAll(['id' => $idsToDelete]);
            }
        }
    }

    /**
     * Obtiene el historial completo de contraseñas de un usuario
     * 
     * @param int $userId ID del usuario
     * @param int $limit Límite de registros (0 = todos)
     * @return array
     */
    public static function getUserHistory($userId, $limit = 0)
    {
        $query = self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['changed_at' => SORT_DESC]);
            
        if ($limit > 0) {
            $query->limit($limit);
        }
        
        return $query->all();
    }

    /**
     * Obtiene la fecha del último cambio de contraseña
     * 
     * @param int $userId
     * @return string|null
     */
    public static function getLastChangeDate($userId)
    {
        $lastHistory = self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['changed_at' => SORT_DESC])
            ->one();

        return $lastHistory ? $lastHistory->changed_at : null;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->changed_at)) {
                $this->changed_at = date('Y-m-d H:i:s');
            }
            return true;
        }
        return false;
    }
}
?>