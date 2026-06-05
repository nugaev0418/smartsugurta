<?php

namespace backend\controllers;

use common\models\Setting;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class SettingController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $model = Setting::find()->one();
        if (!$model) {
            $model = new Setting();
        }

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            if ($model->save()) {
                Yii::$app->session->setFlash('success', "Sozlamalar muvaffaqiyatli saqlandi.");
            } else {
                Yii::$app->session->setFlash('danger', "Saqlashda xatolik yuz berdi.");
            }
            return $this->redirect(['index']);
        }

        return $this->render('index', ['model' => $model]);
    }
}
