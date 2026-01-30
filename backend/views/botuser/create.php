<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Botuser $model */

$this->title = Yii::t('app', 'Create Botuser');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Botusers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="botuser-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
