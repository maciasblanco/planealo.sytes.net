<?php

namespace app\models;

use Yii;
use yii\base\Model;

class CambioPasswordForm extends Model
{
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['currentPassword', 'newPassword', 'confirmPassword'], 'required'],
            ['currentPassword', 'validateCurrentPassword'],
            ['newPassword', 'string', 'min' => 6],
            ['newPassword', 'match', 'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', 
                'message' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial.'],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword', 'message' => 'Las contraseñas no coinciden.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'currentPassword' => 'Contraseña Actual',
            'newPassword' => 'Nueva Contraseña',
            'confirmPassword' => 'Confirmar Nueva Contraseña',
        ];
    }

    /**
     * Valida la contraseña actual
     */
    public function validateCurrentPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = Yii::$app->user->identity;
            if (!$user || !$user->validatePassword($this->currentPassword)) {
                $this->addError($attribute, 'La contraseña actual es incorrecta.');
            }
        }
    }

    /**
     * Cambia la contraseña del usuario
     */
    public function cambiarPassword()
    {
        if ($this->validate()) {
            $user = Yii::$app->user->identity;
            return $user->cambiarPassword($this->newPassword);
        }
        return false;
    }

    /**
     * Mensajes de ayuda para la contraseña
     */
    public function getPasswordHelp()
    {
        return 'La contraseña debe tener al menos 6 caracteres e incluir:';
    }

    /**
     * Requisitos de la contraseña
     */
    public function getPasswordRequirements()
    {
        return [
            'Al menos una letra mayúscula',
            'Al menos una letra minúscula', 
            'Al menos un número',
            'Al menos un carácter especial (@$!%*?&)'
        ];
    }
}