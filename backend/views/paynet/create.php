<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Paynet $model */

$this->title = Yii::t('app', 'Create Paynet');
$this->params['breadcrumbs'][] = ['label' => 'Paynet tokenlar', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="paynet-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
