<?php

namespace backend\controllers;

use common\models\Setting;
use Yii;
use yii\db\Expression;
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

            // Uzbekinvest pauza: son -> shuncha daqiqaga yoqish (vaqt DB NOW() asosida), 'end' -> tugatish,
            // bo'sh -> o'zgarishsiz. uzbekinvest_pause_until ataylab rules'da yo'q (to'g'ridan-to'g'ri post qilinmaydi).
            if ($model->pause_minutes === 'end') {
                $model->uzbekinvest_pause_until = null;
            } elseif (in_array((int)$model->pause_minutes, [15, 30, 60, 120], true)) {
                $model->uzbekinvest_pause_until = new Expression('DATE_ADD(NOW(), INTERVAL ' . (int)$model->pause_minutes . ' MINUTE)');
            }
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
