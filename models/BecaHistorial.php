<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo para la tabla 'atletas.becas_historial'.
 * 
 * @property int $id_historial
 * @property int $id_beca
 * @property string $fecha_original_inicio
 * @property string|null $fecha_original_fin
 * @property string|null $fecha_reactivacion
 * @property string|null $motivo
 * @property int|null $usuario_creacion
 * @property string $fecha_creacion
 * 
 * @property Beca $beca
 * @property User $usuario
 */
class BecaHistorial extends ActiveRecord
{
    public static function tableName()
    {
        return 'atletas.becas_historial';
    }

    public function rules()
    {
        return [
            [['id_beca', 'fecha_original_inicio'], 'required'], // MOD: fecha_reactivacion ya no es requerida
            [['id_beca', 'usuario_creacion'], 'integer'],
            [['fecha_original_inicio', 'fecha_original_fin', 'fecha_reactivacion', 'fecha_creacion'], 'safe'],
            [['motivo'], 'string'],
            [['id_beca'], 'exist', 'skipOnError' => true, 'targetClass' => Beca::class, 'targetAttribute' => ['id_beca' => 'id_beca']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id_historial' => 'ID Historial',
            'id_beca' => 'Beca',
            'fecha_original_inicio' => 'Fecha Inicio Original',
            'fecha_original_fin' => 'Fecha Fin Original',
            'fecha_reactivacion' => 'Fecha Reactivación',
            'motivo' => 'Motivo',
            'usuario_creacion' => 'Usuario',
            'fecha_creacion' => 'Fecha Creación',
        ];
    }

    public function getBeca()
    {
        return $this->hasOne(Beca::class, ['id_beca' => 'id_beca']);
    }

    public function getUsuario()
    {
        return $this->hasOne(User::class, ['id' => 'usuario_creacion']);
    }
}