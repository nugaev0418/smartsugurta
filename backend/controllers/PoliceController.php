<?php

namespace backend\controllers;

use backend\component\EuroAsiaService;
use backend\models\EuroAsia;
use common\eleirbag\Telegram;
use common\models\Botuser;
use common\models\Police;
use backend\models\PoliceSearch;
use CURLFile;
use Psy\VersionUpdater\Downloader\CurlDownloader;
use Yii;
use yii\base\ErrorException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PoliceController implements the CRUD actions for Police model.
 */
class PoliceController extends Controller
{
    public $telegram;
    public $chat_id;
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
     * Lists all Police models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new PoliceSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Police model.
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
     * Creates a new Police model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Police();

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
     * Updates an existing Police model.
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
     * Deletes an existing Police model.
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
     * Finds the Police model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Police the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Police::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionCheck()
    {
        $this->telegram = Yii::$app->telegram;

        $tenDaysAgo = date('Y-m-d H:i:s', strtotime('-10 days'));

        $polices = Police::find()
            ->where(['status' => 0, 'provider_id' => Police::PROVIDER_EAI])
            ->andWhere(['>=', 'created_at', $tenDaysAgo])
            ->all();




        foreach ($polices as $police) {

            echo $police->id . PHP_EOL;
            $eai = new EuroAsiaService();
            $dto = $eai->getPoliceByIdDTO($police->policeId);


            if ($dto->success) {
                $police->status = $dto->status == 'ACTIVE' ? 1 : 0;
                $police->pdfUrl = $dto->pdfUrl;
                $police->paymentId = $dto->paymentId;
                $police->payment_status = $dto->paymentStatus == 'COMPLETED' ? 1 : 0;
                $police->amount = $dto->amount / 100;

                $police->save();

                if ($police->status){
                    $user = Botuser::find()->where(['id'=>$police->user_id])->one();
                    $this->chat_id = $user->chat_id;

                    if (EuroAsia::download($police->policeId)){
                        $filePath = new CURLFile(Yii::getAlias('policeFiles/' . $police->policeId . '.pdf'));
                        $text = "<b>✅ Sug'urtangiz tayyor bo'ldi! / Ваша страховка готова!\n\n@smartsugurtabot</b>";
                        $this->sendDocument($filePath, $text);

                        $this->addBonuse($user, $police->amount);

                        //send to channel
                        $this->sendDocument($filePath, $text, BotController::ORDER_CHANNEL);
                    }else{
                        $this->sendMessage("Nimadir xato Operator bilan bog'laning");
                    }
                }
            }
        }
    }

    public function actionGross()
    {
        $this->telegram = Yii::$app->telegram;

        $tenDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));

        $polices = Police::find()
            ->where(['status' => 0, 'provider_id' => Police::PROVIDER_GROSS])
            ->andWhere(['>=', 'created_at', $tenDaysAgo])
            ->all();




        foreach ($polices as $police) {

            echo $police->id . PHP_EOL;
            $result = $this->grossCheckApi($police->anketa_id);


            if ($result) {
                $police->status = 1;
                $police->pdfUrl = "https://ersp.e-osgo.uz/site/export-to-pdf?id={$police->policeId}";
                $police->payment_status = 1;

                $police->save();

                if ($police->status){
                    $user = Botuser::find()->where(['id'=>$police->user_id])->one();
                    $this->chat_id = $user->chat_id;

                    if (self::grossDownload($police->policeId)){
                        $filePath = new CURLFile(Yii::getAlias('policeFiles/' . $police->policeId . '.pdf'));
                        $text = "<b>✅ Sug'urtangiz tayyor bo'ldi! / Ваша страховка готова!\n\n@smartsugurtabot</b>";
                        $this->sendDocument($filePath, $text);

                        $this->addBonuse($user, $police->amount);

                        //send to channel
                        $this->sendDocument($filePath, $text, BotController::ORDER_CHANNEL);
                    }else{
                        $this->sendMessage("Nimadir xato Operator bilan bog'laning");
                    }
                }
            }
        }
    }

    public static function grossDownload($id)
    {
        $pdfUrl = "https://ersp.e-osgo.uz/site/export-to-pdf?id={$id}";
        $savePath = 'policeFiles/' . $id . '.pdf';

        $fp = fopen($savePath, 'w+');

        $ch = curl_init($pdfUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);

        if ($result === false) {
            echo "Xatolik: " . curl_error($ch);
        }

        curl_close($ch);
        fclose($fp);

        return true;
    }


    public function grossCheckApi($anketa_id)
    {
        $url = "https://osago.gross.uz/epolis/check_oplata.php?anketa_id={$anketa_id}";

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [],
            CURLOPT_COOKIE => '',
            CURLOPT_SSL_VERIFYPEER => false, // agar SSL xatolik bo'lsa
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return false;
        } else {

            if ($response === 'SUCCESS'){
                return true;
            }else{
                return false;
            }
        }
    }

    public function sendMessage($text)
    {
        try {
            $telegram = Yii::$app->telegram;
            $content = ['chat_id' => $this->chat_id, 'parse_mode' => 'html', 'text' => $text, /*'disable_web_page_preview' => true*/];
            $telegram->sendMessage($content);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function sendDocument($file, $text, $chat_id = null)
    {
        try {
            if (!is_null($chat_id)) {
                $this->chat_id = $chat_id;
            }
            $telegram = Yii::$app->telegram;
            $content = ['chat_id' => $this->chat_id, 'parse_mode' => 'html', 'caption' => $text, 'document' => $file /*'disable_web_page_preview' => true*/];
            $telegram->sendDocument($content);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function addBonuse($user, $amount)
    {
        $bonus = $amount * 0.07;
        $user->balance += $bonus;


        $user->save();



        $text = "🎉 Tabriklayman!
Sizning hisobingizga <b>%s</b> so'm bonus qo'shildi!

Chiqarib olish uchun <b>🏧 Hamyon</b> tugmasini bosing.

🎉 Поздравляем!
На ваш счет зачислен бонус в размере <b>%s</b> сумов!

Нажмите кнопку <b>🏧 Кошелек</b> для вывода средств.";

        $bonus = $this->formatMoney($bonus);

        $text = sprintf($text, $bonus, $bonus);

        $this->sendMessage($text);
    }

    function formatMoney($number)
    {
        return number_format($number, 0, '.', ' ');
    }
}
