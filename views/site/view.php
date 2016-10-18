<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\data\Pagination;
use yii\widgets\LinkPager;
?>

<a href="<?= Url::toRoute('site/create') ?>">Crear Contacto</a>

<h3>Lista de Contactos</h3>

<?php $f = ActiveForm::begin([
    "method" => "get",
    "action" => Url::toRoute("site/view"),
    "enableClientValidation" => true
]);
?>

<div class="form-group">
    <?= $f->field($form, "q")->input("search") ?>
</div>

<?= Html::submitButton("Buscar", ["class" => "btn btn-primary"]) ?>

<?php $f->end() ?>

<h3><?= $search ?></h3>

<table class="table table-bordered">
    <thead>
    <tr>
        <th>Id</th>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>Email</th>
        <th>Fecha Creación</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($model as $row) {
        ?>
        <tr>
            <td><?= $row->id ?></td>
            <td><?= $row->name_first ?></td>
            <td><?= $row->name_last ?></td>
            <td><?= $row->email ?></td>
            <td><?= $row->created_at ?></td>
            <td>
                <a href="<?= Url::toRoute(['site/update', 'id' => $row->id]) ?>">
                    <i class="glyphicon glyphicon-edit"></i>
                </a>
                <?php
                echo Html::a('<i class="glyphicon glyphicon-trash"></i>',
                     Url::toRoute(["site/delete", "id" => $row->id]),
                     ['title'=>Yii::t('app','Revoke social worker'),
                     'data-confirm'=>'¿Desea eliminar el registro '.$row->id.'?']);
                ?>
            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>
<?= LinkPager::widget([
    "pagination" => $pages,
]) ?>
