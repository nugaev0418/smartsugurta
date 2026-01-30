<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Relative $model */

$this->title = Yii::t('app', 'Create Relative');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Relatives'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="relative-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
