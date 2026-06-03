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
        $periods = [
            'day'   => date('Y-m-d H:i:s', strtotime('-1 day')),
            'week'  => date('Y-m-d H:i:s', strtotime('-1 week')),
            'month' => date('Y-m-d H:i:s', strtotime('-1 month')),
        ];

        $policeStats  = array_map([$this, 'policeStats'],  $periods);
        $paymentStats = array_map([$this, 'paymentStats'], $periods);

        $topUsersMonth   = $this->topPayingUsers(date('Y-m-d H:i:s', strtotime('-1 month')));
        $topUsersAllTime = $this->topPayingUsers();

        return $this->render('index', compact('policeStats', 'paymentStats', 'topUsersMonth', 'topUsersAllTime'));
    }

    private function policeStats(string $from): array
    {
        $row = (new Query())
            ->select([
                'count'         => 'COUNT(*)',
                'paid_count'    => 'SUM(CASE WHEN payment_status = 1 THEN 1 ELSE 0 END)',
                'paid_amount'   => 'SUM(CASE WHEN payment_status = 1 THEN amount ELSE 0 END)',
                'unpaid_amount' => 'SUM(CASE WHEN payment_status = 0 THEN amount ELSE 0 END)',
            ])
            ->from('police')
            ->where(['>=', 'created_at', $from])
            ->one();

        return [
            'count'         => (int)($row['count'] ?? 0),
            'paid_count'    => (int)($row['paid_count'] ?? 0),
            'paid_amount'   => (int)($row['paid_amount'] ?? 0),
            'unpaid_amount' => (int)($row['unpaid_amount'] ?? 0),
        ];
    }

    private function topPayingUsers(?string $from = null): array
    {
        $query = (new Query())
            ->select([
                'user_id'  => 'p.user_id',
                'count'    => 'COUNT(*)',
                'total'    => 'SUM(p.amount)',
                'fname'    => 'b.fname',
                'lname'    => 'b.lname',
                'username' => 'b.username',
                'phone'    => 'b.phone',
            ])
            ->from(['p' => 'police'])
            ->leftJoin(['b' => 'botuser'], 'b.id = p.user_id')
            ->where(['p.payment_status' => 1]);

        if ($from) {
            $query->andWhere(['>=', 'p.created_at', $from]);
        }

        return $query
            ->groupBy('p.user_id')
            ->orderBy(['total' => SORT_DESC])
            ->limit(10)
            ->all();
    }

    private function paymentStats(string $from): array
    {
        $row = (new Query())
            ->select([
                'count'          => 'COUNT(*)',
                'success_count'  => 'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END)',
                'success_amount' => 'SUM(CASE WHEN status = 1 THEN amount ELSE 0 END)',
                'process_amount' => 'SUM(CASE WHEN status = 0 THEN amount ELSE 0 END)',
                'cancel_count'   => 'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END)',
            ])
            ->from('payment')
            ->where(['>=', 'created_at', $from])
            ->one();

        return [
            'count'          => (int)($row['count'] ?? 0),
            'success_count'  => (int)($row['success_count'] ?? 0),
            'success_amount' => (int)($row['success_amount'] ?? 0),
            'process_amount' => (int)($row['process_amount'] ?? 0),
            'cancel_count'   => (int)($row['cancel_count'] ?? 0),
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
