<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\SeasonalInsurance $model */

$this->title = Yii::t('app', 'Create Seasonal Insurance');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Seasonal Insurances'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="seasonal-insurance-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
