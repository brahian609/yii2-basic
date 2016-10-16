<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<h1>Crear Contacto</h1>
<h3><?= $msg ?></h3>

<?php $form = ActiveForm::begin([
    "method" => "post",
    "enableClientValidation" => true,
]);
?>

<div class="form-group">
    <?= $form->field($model, "name_first")->input("text") ?>
</div>

<div class="form-group">
    <?= $form->field($model, "name_last")->input("text") ?>
</div>

<div class="form-group">
    <?= $form->field($model, "email")->input("email") ?>
</div>

<?= Html::submitButton("Enviar", ["class" => "btn btn-primary"]) ?>

<?php $form->end(); ?>