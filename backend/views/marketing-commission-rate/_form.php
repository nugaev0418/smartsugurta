<?php

use common\models\MarketingCommissionRate;
use common\models\Police;
use yii\widgets\ActiveForm;

/** @var common\models\MarketingCommissionRate $model */
/** @var yii\widgets\ActiveForm $form */

?>
<?= $form->field($model, 'provider_id')->dropDownList(Police::getProviderList()) ?>
<?= $form->field($model, 'region')->dropDownList(MarketingCommissionRate::getRegionList()) ?>
<?= $form->field($model, 'percent')->textInput(['type' => 'number', 'step' => '0.01', 'min' => 0, 'max' => 100]) ?>
