<?php
namespace app\models;

use Yii;
use yii\base\Model;

class ValidarFormularioAjax extends Model
{
    public $nombre;
    public $email;

    public function rules()
    {
        return[
            ['nombre', 'required', 'message' => 'Campo requerido'],
            ['nombre', 'match', 'pattern' => "/^.{3,50}$/", 'message' => 'Minimo 3 y máximo 50 caracteres'],
            ['nombre', 'match', 'pattern' => "/^[0-9a-z]+$/i", 'message' => 'Ingresa solo letras y números'],
            ['email', 'required', 'message' => 'Campo requerido'],
            ['email', 'match', 'pattern' => "/^.{5,80}$/", 'message' => 'Minimo 5 y máximo 80 caracteres'],
            ['email', 'email', 'message' => 'Formato no válido'],
            ['email', 'email_existe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'nombre' => 'Nombre:',
            'email' => 'Email:'
        ];
    }

    public function email_existe($attribute, $params)
    {
        $emails = ["brahian609@hotmail.com", "nn@gmail.com"];

        if(in_array($this->email, $emails)) {
            $this->addError($attribute, "El email ingresado ya existe");
            return true;
        }else{
            return false;
        }
    }

}

?>