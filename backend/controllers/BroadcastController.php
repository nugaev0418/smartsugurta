<?php

namespace backend\controllers;

use backend\queue\BroadcastDeleteJob;
use common\models\Broadcast;
use common\models\BroadcastLog;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class BroadcastController extends Controller
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
            'query' => Broadcast::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', compact('dataProvider'));
    }

    public function actionDelete(int $id)
    {
        $broadcast = $this->findModel($id);

        $logs = BroadcastLog::find()
            ->where(['broadcast_id' => $id])
            ->andWhere(['not', ['telegram_message_id' => null]])
            ->all();

        foreach ($logs as $log) {
            Yii::$app->broadcastQueue->push(new BroadcastDeleteJob([
                'chat_id'             => (int)$log->chat_id,
                'telegram_message_id' => (int)$log->telegram_message_id,
            ]));
        }

        Yii::$app->session->setFlash('success', count($logs) . " ta foydalanuvchidan xabar o'chirilmoqda.");
        return $this->redirect(['index']);
    }

    private function findModel(int $id): Broadcast
    {
        $model = Broadcast::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Broadcast topilmadi.");
        }
        return $model;
    }
}
