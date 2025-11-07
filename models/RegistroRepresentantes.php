<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "atletas.registro_representantes".
 *
 * @property int $id
 * @property int|null $id_club
 * @property int|null $id_escuela
 * @property string|null $p_nombre
 * @property string|null $s_nombre
 * @property string|null $p_apellido
 * @property string|null $s_apellido
 * @property int|null $id_nac
 * @property int|null $identificacion
 * @property string|null $cell
 * @property string|null $telf
 * @property string|null $d_creacion
 * @property int|null $u_creacion
 * @property string|null $d_update
 * @property int|null $u_update
 * @property bool|null $eliminado
 * @property string|null $dir_ip
 * @property int|null $user_id
 */
class RegistroRepresentantes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'atletas.registro_representantes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'id_escuela', 'id_nac', 'identificacion', 'u_creacion', 'u_update'], 'default', 'value' => null],
            [[ 'id_escuela', 'id_nac', 'identificacion', 'u_creacion', 'u_update', 'user_id'], 'integer'],
            [['p_nombre', 's_nombre', 'p_apellido', 's_apellido', 'cell', 'telf', 'dir_ip'], 'string'],
            [['d_creacion', 'd_update'], 'safe'],
            [['eliminado'], 'boolean'],
            
            // NUEVAS VALIDACIONES PARA BLINDAJE
            ['id_escuela', 'validateEscuelaExistente'],
            ['id_escuela', 'required', 'message' => 'La escuela es requerida'],
        ];
    }

    /**
     * VALIDACIÓN CRÍTICA - Verifica que la escuela exista y esté activa
     */
    public function validateEscuelaExistente($attribute, $params)
    {
        if (empty($this->id_escuela)) {
            $this->addError($attribute, 'La escuela es requerida.');
            return;
        }
        
        // Validar existencia en BD
        $escuela = Escuela::find()
            ->where(['id' => $this->id_escuela])
            ->andWhere(['eliminado' => false])
            ->one();
            
        if (!$escuela) {
            $this->addError($attribute, 'La escuela seleccionada no existe o está inactiva.');
            Yii::error("Intento de guardar representante con escuela inválida: {$this->id_escuela}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_escuela' => 'Id Escuela',
            'p_nombre' => 'P Nombre',
            's_nombre' => 'S Nombre',
            'p_apellido' => 'P Apellido',
            's_apellido' => 'S Apellido',
            'id_nac' => 'Id Nac',
            'identificacion' => 'Identificacion',
            'cell' => 'Cell',
            'telf' => 'Telf',
            'd_creacion' => 'D Creacion',
            'u_creacion' => 'U Creacion',
            'd_update' => 'D Update',
            'u_update' => 'U Update',
            'eliminado' => 'Eliminado',
            'dir_ip' => 'Dir Ip',
            'user_id' => 'User ID',
        ];
    }

    /**
     * VALIDACIÓN ANTES DE GUARDAR - PROTECCIÓN CRÍTICA
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        // Forzar id_escuela desde sesión si está vacío
        if (empty($this->id_escuela)) {
            $session = Yii::$app->session;
            $this->id_escuela = $session->get('id_escuela');
        }
        
        // Validación final de escuela
        if (empty($this->id_escuela) || !Escuela::find()->where(['id' => $this->id_escuela, 'eliminado' => false])->exists()) {
            Yii::error("BLOQUEO: Intento de guardar representante sin escuela válida. User: " . (Yii::$app->user->id ?? 'anonimo'));
            return false;
        }
        
        return true;
    }

    /**
     * Gets query for [[User]].
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Atletas]].
     * @return \yii\db\ActiveQuery
     */
    public function getAtletas()
    {
        return $this->hasMany(AtletasRegistro::class, ['id_representante' => 'id']);
    }

    /**
     * Crea usuario automáticamente para el representante con manejo de transacciones
     */
    public function crearUsuarioRepresentante()
    {
        if (empty($this->identificacion)) {
            Yii::error('No se puede crear usuario: identificación vacía', 'representante');
            return null;
        }

        try {
            $user = User::crearUsuarioAutomatico(
                (string)$this->identificacion,
                null, // email opcional
                $this->p_nombre . ' ' . $this->p_apellido,
                'representante'
            );

            if ($user) {
                // Actualizar en una transacción separada
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $this->user_id = $user->id;
                    if ($this->updateAttributes(['user_id' => $user->id])) {
                        $transaction->commit();
                        Yii::info("Usuario asignado al representante: {$user->id}", 'representante');
                    } else {
                        $transaction->rollBack();
                        Yii::error('Error actualizando user_id del representante: ' . json_encode($this->getErrors()), 'representante');
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::error('Excepción actualizando user_id del representante: ' . $e->getMessage(), 'representante');
                }
            }

            return $user;
        } catch (\Exception $e) {
            Yii::error('Excepción en crearUsuarioRepresentante: ' . $e->getMessage(), 'representante');
            return null;
        }
    }

    /**
     * After Save event - Crear usuario automáticamente con mejor manejo de errores
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert && empty($this->user_id)) {
            // Usar una transacción separada para la creación del usuario
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Crear usuario automáticamente para nuevo representante
                $user = $this->crearUsuarioRepresentante();

                // Actualizar el user_id si se creó exitosamente
                if ($user && $user->id) {
                    if ($this->updateAttributes(['user_id' => $user->id])) {
                        $transaction->commit();
                        Yii::info("Usuario creado y asignado exitosamente para representante ID: {$this->id}", 'representante');
                    } else {
                        $transaction->rollBack();
                        Yii::error('Error actualizando user_id después de crear usuario', 'representante');
                    }
                } else {
                    $transaction->rollBack();
                    Yii::warning('No se pudo crear usuario para representante, transacción revertida', 'representante');
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error('Excepción en afterSave del representante: ' . $e->getMessage(), 'representante');
            }
        }
    }
}