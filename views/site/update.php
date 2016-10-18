<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
?>

    <a href="<?= Url::toRoute("site/view") ?>">Ir a la lista de contactos</a>

    <h1>Editar contacto con id <?= Html::encode($_GET["id"]) ?></h1>

    <h3><?= $msg ?></h3>

<?php $form = ActiveForm::begin([
    "method" => "post",
    'enableClientValidation' => true,
]);
?>

<?= $form->field($model, "id")->input("hidden")->label(false) ?>

    <div class="form-group">
        <?= $form->field($model, "name_first")->input("text") ?>
    </div>

    <div class="form-group">
        <?= $form->field($model, "name_last")->input("text") ?>
    </div>

    <div class="form-group">
        <?= $form->field($model, "email")->input("text") ?>
    </div>

<?= Html::submitButton("Actualizar", ["class" => "btn btn-primary"]) ?>

<?php $form->end() ?>