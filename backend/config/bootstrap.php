<?php

Yii::$container->set(\yii\widgets\LinkPager::class, [
    'class'          => \yii\bootstrap5\LinkPager::class,
    'prevPageLabel'  => '<i class="ti ti-chevron-left"></i>',
    'nextPageLabel'  => '<i class="ti ti-chevron-right"></i>',
    'firstPageLabel' => '<i class="ti ti-chevrons-left"></i>',
    'lastPageLabel'  => '<i class="ti ti-chevrons-right"></i>',
    'options'        => ['class' => 'pagination mb-0'],
]);
