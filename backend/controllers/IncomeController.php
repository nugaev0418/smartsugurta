<?php

namespace backend\controllers;

use backend\models\IncomeSearch;
use common\models\Income;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class IncomeController extends Controller
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
        $searchModel  = new IncomeSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', compact('searchModel', 'dataProvider'));
    }

    public function actionView(int $id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    public function actionDelete(int $id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    private function findModel(int $id): Income
    {
        $model = Income::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Yozuv topilmadi.");
        }
        return $model;
    }
}
