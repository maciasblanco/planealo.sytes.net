<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "seguridad.password_history".
 *
 * @property int $id
 * @property int $user_id
 * @property string $password_hash
 * @property string|null $created_at
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
            [['created_at'], 'safe'],
            [['password_hash'], 'string', 'max' => 255],
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
     * Agrega un hash de contraseña al historial del usuario
     * 
     * @param int $userId ID del usuario
     * @param string $passwordHash Hash de la contraseña
     * @return bool
     */
    public static function addToHistory($userId, $passwordHash)
    {
        $history = new self();
        $history->user_id = $userId;
        $history->password_hash = $passwordHash;
        
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
     * @param string $passwordHash Hash de la contraseña a verificar
     * @return bool
     */
    public static function isPasswordUsed($userId, $passwordHash)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->andWhere(['password_hash' => $passwordHash])
            ->exists();
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
            ->orderBy(['created_at' => SORT_DESC])
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
            ->orderBy(['created_at' => SORT_DESC]);
            
        if ($limit > 0) {
            $query->limit($limit);
        }
        
        return $query->all();
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