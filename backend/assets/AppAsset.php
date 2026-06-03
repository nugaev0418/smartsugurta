<?php

namespace backend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        '//cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css',
        '//cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
        'css/site.css',
    ];
    public $js = [
        '//cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js',
    ];
    public $jsOptions = ['position' => \yii\web\View::POS_END];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
