<?php

namespace backend\controllers;

use common\models\LoginForm;
use Yii;
use yii\db\Query;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
                'layout' => '@backend/views/layouts/blank',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $stats = [
            'day'   => $this->policeStats(date('Y-m-d H:i:s', strtotime('-1 day'))),
            'week'  => $this->policeStats(date('Y-m-d H:i:s', strtotime('-1 week'))),
            'month' => $this->policeStats(date('Y-m-d H:i:s', strtotime('-1 month'))),
        ];

        return $this->render('index', compact('stats'));
    }

    private function policeStats(string $from): array
    {
        $row = (new Query())
            ->select([
                'count'         => 'COUNT(*)',
                'paid_amount'   => 'SUM(CASE WHEN payment_status = 1 THEN amount ELSE 0 END)',
                'unpaid_amount' => 'SUM(CASE WHEN payment_status = 0 THEN amount ELSE 0 END)',
            ])
            ->from('police')
            ->where(['>=', 'created_at', $from])
            ->one();

        return [
            'count'         => (int)($row['count'] ?? 0),
            'paid_amount'   => (int)($row['paid_amount'] ?? 0),
            'unpaid_amount' => (int)($row['unpaid_amount'] ?? 0),
        ];
    }

    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
