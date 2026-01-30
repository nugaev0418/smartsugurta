<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\History $model */

$this->title = Yii::t('app', 'Create History');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Histories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="history-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
