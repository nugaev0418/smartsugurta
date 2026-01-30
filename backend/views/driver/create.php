<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Driver $model */

$this->title = Yii::t('app', 'Create Driver');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Drivers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="driver-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
