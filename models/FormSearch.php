<?php
namespace app\models;

use Yii;
use yii\base\Model;

class FormSearch extends Model
{
    public $q;

    public function rules()
    {
        return[
            ['q', 'match', 'pattern' => '/^[0-9a-záéíóúñ\s]+$/i', 'message' => 'Solo se aceptan letras y números'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'q' => 'Buscar:'
        ];
    }

}

?>