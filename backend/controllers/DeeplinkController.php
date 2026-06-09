<?php

namespace backend\controllers;

use common\models\Deeplink;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class DeeplinkController extends Controller
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
            'query'      => Deeplink::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('index', compact('dataProvider'));
    }

    public function actionCreate()
    {
        $model = new Deeplink();

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Deeplink yaratildi.");
            return $this->redirect(['index']);
        }

        return $this->render('create', compact('model'));
    }

    public function actionDelete(int $id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', "Deeplink o'chirildi.");
        return $this->redirect(['index']);
    }

    private function findModel(int $id): Deeplink
    {
        $model = Deeplink::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Deeplink topilmadi.");
        }
        return $model;
    }
}
