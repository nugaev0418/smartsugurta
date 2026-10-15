<?php

namespace backend\controllers;

use common\models\MarketingCommissionRate;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class MarketingCommissionRateController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
        ];
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query'      => MarketingCommissionRate::find()->orderBy(['provider_id' => SORT_ASC, 'region' => SORT_ASC]),
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('index', compact('dataProvider'));
    }

    public function actionCreate()
    {
        $model = new MarketingCommissionRate();

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Komissiya stavkasi qo\'shildi.');
            return $this->redirect(['index']);
        }

        return $this->render('create', compact('model'));
    }

    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Komissiya stavkasi yangilandi.');
            return $this->redirect(['index']);
        }

        return $this->render('update', compact('model'));
    }

    public function actionDelete(int $id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Komissiya stavkasi o\'chirildi.');
        return $this->redirect(['index']);
    }

    private function findModel(int $id): MarketingCommissionRate
    {
        $model = MarketingCommissionRate::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Stavka topilmadi.");
        }
        return $model;
    }
}
