<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Police $model */

$this->title = Yii::t('app', 'Create Police');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Polices'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="police-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
