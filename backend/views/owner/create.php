<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Owner $model */

$this->title = Yii::t('app', 'Create Owner');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Owners'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="owner-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
