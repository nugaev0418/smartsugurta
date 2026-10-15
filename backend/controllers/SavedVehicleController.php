<?php

namespace backend\controllers;

use backend\models\SavedVehicleSearch;
use backend\queue\ErspLookupJob;
use common\models\SavedVehicle;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * SavedVehicleController — "Mening avtolarim" bo'limida foydalanuvchilar
 * saqlagan avtomobillarni va ularning ERSP'dan keshlangan OSAGO polisalarini
 * admin panelda ko'rish uchun (GrossApiLogController bilan bir xil naqsh).
 * Qo'shimcha: ERSP tekshiruvini qayta ishga tushirish (recheck) va noto'g'ri/
 * keraksiz yozuvni o'chirish.
 */
class SavedVehicleController extends Controller
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'recheck' => ['POST'],
                ],
            ],
        ]);
    }

    public function actionIndex()
    {
        $searchModel = new SavedVehicleSearch();
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

    /**
     * MyVehiclesStageHandler::triggerCheck() bilan bir xil mantiq — faqat
     * cooldown o'tgan bo'lsa ErspLookupJob navbatga qo'yiladi. `chatId`
     * berilgani uchun natija tayyor bo'lganda avtomobil egasiga ham
     * (botdagidek) xabar yuboriladi.
     */
    public function actionRecheck($id)
    {
        $model = $this->findModel($id);

        if (!$model->canCheckNow()) {
            Yii::$app->session->setFlash('warning', 'Hali tekshirish mumkin emas — cooldown tugamagan.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model->ersp_check_status = SavedVehicle::ERSP_STATUS_CHECKING;
        $model->ersp_checked_at = date('Y-m-d H:i:s');
        $model->save(false);

        Yii::$app->erspQueue->push(new ErspLookupJob([
            'savedVehicleId' => $model->id,
            'chatId' => $model->botuser ? (string) $model->botuser->chat_id : null,
        ]));

        Yii::$app->session->setFlash('success', 'ERSP tekshiruvi navbatga qo\'yildi.');

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        Yii::$app->session->setFlash('success', 'Avtomobil yozuvi o\'chirildi.');

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = SavedVehicle::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
