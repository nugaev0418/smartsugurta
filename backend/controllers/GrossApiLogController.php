<?php

namespace backend\controllers;

use common\models\GrossApiLog;
use backend\models\GrossApiLogSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * GrossApiLogController — Gross integratsiyasi so'rov/javoblarini faqat
 * ko'rish uchun (tahrirlash/o'chirish yo'q, bu log yozuvi).
 */
class GrossApiLogController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new GrossApiLogSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    protected function findModel($id)
    {
        if (($model = GrossApiLog::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
