<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Paynet $model */

$this->title = Yii::t('app', 'Update Paynet: {name}', [
    'name' => $model->name ?: $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => 'Paynet tokenlar', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name ?: $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="paynet-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
