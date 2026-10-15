<?php

namespace backend\controllers;

use backend\component\MarketingReportService;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class MarketingDashboardController extends Controller
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
        [$from, $to, $touchType] = $this->resolveFilters();

        $service = new MarketingReportService();
        $funnel = $service->getFunnel($from, $to);
        $conversion = $service->getConversion($from, $to, $touchType);

        return $this->render('index', [
            'from' => $from,
            'to' => $to,
            'touchType' => $touchType,
            'funnel' => $funnel,
            'conversion' => $conversion,
        ]);
    }

    public function actionExportCsv(): Response
    {
        [$from, $to, $touchType] = $this->resolveFilters();

        $service = new MarketingReportService();
        $csv = $service->exportCsv($service->getConversion($from, $to, $touchType));

        return Yii::$app->response->sendContentAsFile(
            "\xEF\xBB\xBF" . $csv,
            "marketing_{$from}_{$to}_{$touchType}.csv",
            ['mimeType' => 'text/csv']
        );
    }

    private function resolveFilters(): array
    {
        $from = Yii::$app->request->get('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to = Yii::$app->request->get('to') ?: date('Y-m-d');
        $touchType = Yii::$app->request->get('touchType') === 'last' ? 'last' : 'first';

        return [$from, $to, $touchType];
    }
}
