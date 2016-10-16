<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>

<h1>Formulario</h1>
<h3><?= $mensaje ?></h3>
<?= Html::beginForm(
        Url::toRoute("site/request"),
        "get",
        ['class' => 'form-inline']
    );
?>

<div class="form-group">
    <?= Html::label("Ingresa tu Nombre", "nombre") ?>
    <?= Html::textInput("nombre", "", ["class" => "form-control"]) ?>
</div>

<?= Html::submitButton("Enviar", ["class" => "btn btn-primary"]) ?>

<?= Html::endForm() ?>