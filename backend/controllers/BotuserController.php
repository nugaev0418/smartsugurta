<?php

namespace backend\controllers;

use common\models\Botuser;
use common\models\Payment;
use common\models\Police;
use backend\models\BotuserSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * BotuserController implements the CRUD actions for Botuser model.
 */
class BotuserController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Botuser models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new BotuserSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Botuser model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Botuser model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Botuser();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Botuser model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Botuser model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Botuser model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Botuser the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Botuser::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionInfo(?int $id = null, ?int $chatId = null): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $id
            ? Botuser::findOne($id)
            : Botuser::findOne(['chat_id' => $chatId]);

        if (!$user) {
            return $this->asJson(['error' => 'Foydalanuvchi topilmadi']);
        }

        $id = $user->id;

        $policeCount       = (int) Police::find()->where(['user_id' => $id])->count();
        $paidPoliceCount   = (int) Police::find()->where(['user_id' => $id, 'payment_status' => 1])->count();
        $paymentCount      = (int) Payment::find()->where(['user_id' => $id])->count();
        $successPmtCount   = (int) Payment::find()->where(['user_id' => $id, 'status' => Payment::STATUS_SUCCESS])->count();
        $totalWithdrawn    = (int)(Payment::find()->where(['user_id' => $id, 'status' => Payment::STATUS_SUCCESS])->sum('amount') ?? 0);

        return $this->asJson([
            'id'                    => $user->id,
            'name'                  => trim($user->fname . ' ' . $user->lname),
            'username'              => $user->username,
            'phone'                 => $user->phone,
            'balance'               => (int) $user->balance,
            'police_count'          => $policeCount,
            'paid_police_count'     => $paidPoliceCount,
            'payment_count'         => $paymentCount,
            'success_payment_count' => $successPmtCount,
            'total_withdrawn'       => $totalWithdrawn,
            'joined_at'             => $user->created_at,
        ]);
    }
}
