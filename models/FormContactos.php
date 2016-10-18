<?php
namespace app\models;

use Yii;
use yii\base\Model;

class FormContactos extends Model
{
    public $id;
    public $name_first;
    public $name_last;
    public $email;

    public function rules()
    {
        return[
            ['id', 'integer', 'message' => 'Id incorrecto'],
            ['name_first', 'required', 'message' => 'Campo requerido'],
            ['name_first', 'match', 'pattern' => "/^.{3,50}$/", 'message' => 'Minimo 3 y máximo 50 caracteres'],
            ['name_first', 'match', 'pattern' => '/^[a-záéíóúñ\s]+$/i', 'message' => 'Ingresa solo letras'],
            ['name_last', 'required', 'message' => 'Campo requerido'],
            ['name_last', 'match', 'pattern' => "/^.{3,50}$/", 'message' => 'Minimo 3 y máximo 50 caracteres'],
            ['name_last', 'match', 'pattern' => '/^[a-záéíóúñ\s]+$/i', 'message' => 'Ingresa solo letras'],
            ['email', 'required', 'message' => 'Campo requerido'],
            ['email', 'match', 'pattern' => "/^.{5,80}$/", 'message' => 'Minimo 5 y máximo 80 caracteres'],
            ['email', 'email', 'message' => 'Formato no válido'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name_first' => 'Nombres:',
            'name_last' => 'Apellidos:',
            'email' => 'Email:'
        ];
    }

}

?>