<?php

namespace backend\controllers;

use backend\component\EuroAsiaService;
use backend\models\EuroAsia;
use backend\models\Pages;
use backend\queue\PaynetQueue;
use common\models\Botuser;
use common\models\History;
use common\models\Payment;
use common\models\Police;
use common\models\SeasonalInsurance;
use common\models\Text;
use common\models\User;
use DateTime;
use Yii;
use yii\base\ErrorException;
use yii\helpers\Url;
use yii\web\Controller;
use function PHPUnit\Framework\isNull;

class BotController extends Controller
{
    public $enableCsrfValidation = false;
    public $chat_id;
    public $text;
    public $data;
    public $telegram;
    public $firstname;
    public $lastname;
    public $username;

    const PAYMENT_CHANNEL       = '-1003501874314',
          ADMIN_ID              = '3673579',
          ORDER_CHANNEL         = '-1003782162980';

    protected ?array $_dataCache = null;


    public function actionStart()
    {
        $this->telegram = Yii::$app->telegram;

//        dd($this->setWebhook());


        $this->data = $this->telegram->getData();
        $this->text = $this->telegram->Text();
        $this->firstname = $this->telegram->FirstName();
        $this->lastname = $this->telegram->LastName();
        $this->username = $this->telegram->Username();
        $this->chat_id = isset($message['chat']['id']) ? $message['chat']['id'] : '';
        $this->chat_id = $this->telegram->ChatID();

//        $this->sendMessage(123);
//        exit();

//        $this->sendMessage(json_encode($this->data));

        try {
            if (is_numeric($this->chat_id)) {
                $this->setHistory();
            }

            if (!$this->isUser()) {
                $this->addUser();
            }


            switch ($this->text) {
                case '/start':
                    if ($this->lang) {
                        $this->showMainPage();
                    } else {
                        $this->showLangPage();
                    }
                    break;
                case $this->getMText('Cancel'):
                    $this->showMainPage();
                    break;
                case $this->getMText('Main menu'):
                    $this->showMainPage();
                    break;
                case $this->getMText('Language selection');
                    $this->changeLang();
                    break;
                case $this->getMText("BEGIN OSAGO BUTTON"):
                    $this->clearDatas();
                    $this->showPhonePage();
                    break;
                case $this->getMText("Wallet"):
                    $this->showWalletPage();
                    break;
                default:
                    switch ($this->page) {
                        case Pages::LANG:
                            $this->handleLangPage();
                            break;
                        case Pages::PHONE:
                            $this->handlePhonePage();
                            break;
                        case Pages::LISENCE_NUMBER:
                            $this->handleLisenceNumberPage();
                            break;
                        case Pages::TEXPASS_SERIA:
                            $this->handleTexPassSeriaPage();
                            break;
                        case Pages::TEXPASS_NUMBER:
                            $this->handleTexPassNumberPage();
                            break;
                        case Pages::OWNER_PASS:
                            $this->handleOwnerPassPage();
                            break;
                        case Pages::DRIVER_RESTRICTION_TYPE:
                            $this->handleDriverRestrictionPage();
                            break;
                        case Pages::DRIVER_PAGE:
                            $this->handleDriverPage();
                            break;
                        case Pages::POLICE_SEASON_TYPE:
                            $this->handlePoliceSeasonPage();
                            break;
                        case Pages::START_AT:
                            $this->handleStartAtPage();
                            break;
                        case Pages::PAYMENT_TYPE:
                            $this->handlePaymentTypePage();
                            break;
                        case Pages::CONFIRM_PAGE:
                            $this->handleConfirmPage();
                            break;

                        case Pages::WALLET_PAGE:
                            $this->handleWalletPage();
                            break;
                        case Pages::WITHDRAW_TYPE_PAGE:
                            $this->handleWithdrawTypePage();
                            break;
                        case Pages::WITHDRAW_ACCOUNT_PAGE:
                            $this->handleWithdrawAccountPage();
                            break;
                        case Pages::WITHDRAW_AMOUNT_PAGE:
                            $this->handleWithdrawAmountPage();
                            break;
                    }
                    break;
            }
        }catch (ErrorException $e){
            Yii::error($e->getMessage());
            $this->response->statusCode = 200;
        }
        return 'ok';
    }







    // ******** SHOW PAGES **** //
    public function showMainPage($text = false){
        try {
            $this->page = Pages::MAIN;

            if (!$text){
                $text = $this->getMText('hello');
            }

            $option = [
                [
                    $this->telegram->buildKeyboardButton($this->getMText("BEGIN OSAGO BUTTON")),
                    $this->telegram->buildKeyboardButton($this->getMText('Wallet')),
                ],
//                [
//                    $this->telegram->buildKeyboardButton($this->getMText('Get to know the price')),
//                    $this->telegram->buildKeyboardButton($this->getMText('My insurances')),
//                ],
                [
                    $this->telegram->buildKeyboardButton($this->getMText('Language selection')),
                ],
            ];
            $this->sendMessageWithKeyborad($text, $option);
            exit();
        }catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }
    public function showLangPage(){
        $this->page = Pages::LANG;

        $text = "Iltimos tilni tanlagan.\nПожалуйста выберите язык.";
        $option = [
            [
                $this->telegram->buildKeyboardButton("O'zbekcha"),
                $this->telegram->buildKeyboardButton("Русский"),
            ],
        ];
        $this->sendMessageWithKeyborad($text, $option);

    }

    public function showPhonePage(){
        $this->page = Pages::PHONE;

        $text = $this->getMText('enter phone');
        $option = [
            [
                $this->telegram->buildKeyboardButton($this->getMText("📲 Send number"), $request_contact = true),
            ],
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ]
        ];
        $this->sendMessageWithKeyborad($text, $option);
    }

    public function showLisenceNumberPage(){

        $this->page = Pages::LISENCE_NUMBER;

        $text = $this->getMText("ask lisence number");

        $option = [
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ],
        ];
        $this->sendMessageWithKeyborad($text, $option);

    }

    public function showTexPassSeriaPage(){

        $this->page = Pages::TEXPASS_SERIA;

        $text = $this->getMText("ask texpasseria");

        $option = [
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ],
        ];
        $this->sendMessageWithKeyborad($text, $option);

    }

    public function showTexPassNumberPage(){

        $this->page = Pages::TEXPASS_NUMBER;

        $text = $this->getMText("ask texpasnumber");

        $option = [
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ],
        ];
        $this->sendMessageWithKeyborad($text, $option);

    }

    public function showOwnerPassPage(){

        $this->page = Pages::OWNER_PASS;

        $text = $this->getMText("ask owner pass");

        $option = [
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ],
        ];
        $this->sendMessageWithKeyborad($text, $option);

    }

    public function showPoliceSeasonPage()
    {
        $this->page = Pages::POLICE_SEASON_TYPE;

        $option = [
            [
                $this->telegram->buildKeyboardButton($this->getMText('1 year')),
                $this->telegram->buildKeyboardButton($this->getMText('6 months')),
                $this->telegram->buildKeyboardButton($this->getMText('20 days')),
            ],
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ]
        ];
        $this->sendMessageWithKeyborad($this->getMText('Choose season police'), $option);
    }

    public function showDriverRestrictionPage()
    {
        $this->page = Pages::DRIVER_RESTRICTION_TYPE;

        $option = [
            [
                $this->telegram->buildKeyboardButton($this->getMText('Limited')),
                $this->telegram->buildKeyboardButton($this->getMText('Not limited')),
            ],
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ]
        ];
        $this->sendMessageWithKeyborad($this->getMText('Choose driver restriction type'), $option);
    }

    public function showDriverPage($no_driver_button = false, $message = false){

        $this->page = Pages::DRIVER_PAGE;

        $text = $message != false ? $message : $this->getMText("ask driver");

        $option = [];
        if($no_driver_button){
            $option[] = [
                $this->telegram->buildKeyboardButton($this->getMText("No other drivers")),
            ];
        }

        $option[] = [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ];
        $this->sendMessageWithKeyborad($text, $option);

    }

    public function showStartAtPage()
    {
        $this->page = Pages::START_AT;

        $today = date("d.m.Y");

        $option = [
            [
                $this->telegram->buildKeyboardButton($today),
            ],
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ]
        ];

        $text = sprintf($this->getMText("start at text"), $today);
        $this->sendMessageWithKeyborad($text, $option);
    }

    public function showPaymentTypePage()
    {
        $this->page = Pages::PAYMENT_TYPE;

        $option = [
            [
                $this->telegram->buildKeyboardButton(EuroAsia::GATEWAY_CLICK),
                $this->telegram->buildKeyboardButton(EuroAsia::GATEWAY_PAYME),
            ],
            [
                $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
            ]
        ];
        $this->sendMessageWithKeyborad($this->getMText('Choose a payment method'), $option);
    }

    public function showConfirmPage()
    {

        try {

            $this->page = Pages::CONFIRM_PAGE;

            $option = [
                [
                    $this->telegram->buildKeyboardButton($this->getMText("Continue ✅")),
                ],
                [
                    $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
                ]
            ];

            $vehicleData = $this->vehicleData;
            $autoNumber = $this->lisenceNumber;
            $texPassSeria = $this->texPassSeria;
            $texPassNumber = $this->texPassNumber;
            $texPass = $texPassSeria . $texPassNumber;



            if ($vehicleData['ownerType'] == 'ORGANIZATION'){
                $arizachi = $vehicleData['name'];
            }

            switch ($vehicleData['ownerType']){
                case 'ORGANIZATION':
                    $arizachi = $vehicleData['name'];
                    break;
                case 'PERSON':
                    $arizachi = $vehicleData['firstName'] . " " . $vehicleData['lastName'] . " " . $vehicleData['middleName'];
                    break;
            }

            $phone = $this->phone;
            $sugurtaDavri = $this->startAt;
            $muddat =  $this->policeSeason;
            $sugurta_muddati = $muddat['days'];

            $tugash_sanasi = date('d.m.Y', strtotime($this->startAt . ' + ' . ($muddat['days'] - 1) . ' days'));

            $haydovchilar = '';
            if ($this->drivers != ''){
                foreach ($this->drivers as $driver){
                    $haydovchilar .= $driver['firstName'] .  ' ' . $driver['lastName'] .  ' - ' . $driver['seria'] .  ' ' . $driver['number'] . "\n";
                }
            }





            $service = new EuroAsiaService();
            $driverRestriction = $this->driverRestriction == 'Limited' ? true : false;
            $dto = $service->getCalculateOsagoDTO(
                [],
                $muddat['id'],
                $driverRestriction,
                $this->vehicleData['useTerritoryRegionId'],
                $this->vehicleData['vehicleGroupId']
            );

            if ($dto->success){
                $jami_summa = $this->formatMoney((float)$dto->premium / 100);
            }else{
                $this->sendMessage(1);
                $jami_summa = 'Aniqlanmadi!';
            }

            $text = sprintf($this->getMText("confirm texts"),
                $autoNumber,
                $texPass,
                $arizachi,
                $phone,
                $sugurtaDavri,
                $sugurta_muddati,
                $tugash_sanasi,
                $haydovchilar,
                $jami_summa
            );
            $this->sendMessageWithKeyborad($text, $option);

            $this->sendMessageWithID(EuroAsia::ORDER_CHANNEL_ID, $text);


        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }


    }

    public function showWalletPage()
    {

        try {

            $this->page = Pages::WALLET_PAGE;

            $option = [
                [
                    $this->telegram->buildKeyboardButton($this->getMText("Withdraw")),
                ],
                [
                    $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
                ]
            ];

            $user = Botuser::find()->where(['chat_id' => $this->chat_id])->one();

            $balance = $user->balance;

            $text = sprintf($this->getMText("Withdraw balance"), $balance);

            $this->sendMessageWithKeyborad($text, $option);


        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }


    }

    public function showWithdrawTypePage()
    {

        try {

            $this->page = Pages::WITHDRAW_TYPE_PAGE;

            $option = [
                [
                    $this->telegram->buildKeyboardButton($this->getMText("to phone")),
                    $this->telegram->buildKeyboardButton($this->getMText("to card")),
                ],
                [
                    $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
                ]
            ];

            $text = $this->getMText("Choose Withdraw type");

            $this->sendMessageWithKeyborad($text, $option);


        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }


    }

    public function showWithdrawAccountPage()
    {

        try {

            $this->page = Pages::WITHDRAW_ACCOUNT_PAGE;

            $option = [
                [
                    $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
                ]
            ];

            switch ($this->withdrawType){
                case Payment::TO_PHONE:
                    $text = $this->getMText("Enter phone account number");
                    break;
                case Payment::TO_CARD:
                    $text = $this->getMText("Enter card account number");
                    break;
            }

            $this->sendMessageWithKeyborad($text, $option);


        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }


    }

    public function showWithdrawAmountPage()
    {

        try {

            $this->page = Pages::WITHDRAW_AMOUNT_PAGE;

            $option = [
                [
                    $this->telegram->buildKeyboardButton($this->getMText("Cancel")),
                ]
            ];

            $text = $this->getMText("Enter amount");

            $this->sendMessageWithKeyborad($text, $option);


        }catch (\Exception $e){
            Yii::error($e->getMessage());
        }


    }

    // ******** SHOW PAGES **** //



    // ***** Handlers ***** //

    public function handleLangPage()
    {
        switch ($this->text) {
            case "O'zbekcha":
                $this->lang = 'uz';
                $this->showMainPage();
                break;
            case "Русский":
                $this->lang = 'ru';
                $this->showMainPage();
                break;
            default:
                $this->showLangPage();
        }
    }
    public function handlePhonePage()
    {
        if (isset($this->data['message']['contact'])) {
            $phone = $this->data['message']['contact']['phone_number'];
            $this->phone = $phone;
            $this->showLisenceNumberPage();

        } elseif (preg_match('/^\+?\d{9,12}$/', $this->text)) {
            // The input is a valid number within the specified length.
            $phone = $this->text;
            $this->phone = $phone;
            $this->showLisenceNumberPage();
        } else {
            $this->sendMessage($this->getMText('phone ask again'));
        }
    }

    public function handleLisenceNumberPage()
    {
        if (!is_null($this->text)) {
            $this->lisenceNumber = strtoupper($this->text);
            $this->showTexPassSeriaPage();
        }else{
            $this->showLisenceNumberPage();
        }

    }

    public function handleTexPassSeriaPage()
    {
        if (!is_null($this->text)) {
            $value = strtoupper(trim($this->text));

            if (preg_match('/^[A-Z]{3}$/', $value)) {
                $this->texPassSeria = $value;
                $this->showTexPassNumberPage();
                return;
            }
        }

        $this->showTexPassSeriaPage();

    }

    public function handleTexPassNumberPage()
    {
        if (!is_null($this->text) && is_numeric($this->text) && strlen($this->text) == 7) {
            $this->texPassNumber = $this->text;

            $service = new EuroAsiaService();

            $dto = $service->getVehicleOwnerDTO(
                $this->texPassSeria,
                $this->texPassNumber,
                $this->lisenceNumber
            );

            if (!$dto->success) {
                // xato ishlovi
                $this->showMainPage($this->getMText('Not found transport'));
            }else{
                $this->vehicleData = $dto;
                if ($dto->ownerType == 'PERSON'){
                    $this->showOwnerPassPage();
                }else{
                    $this->showDriverRestrictionPage();
                }
            }
        }else{
            $this->showTexPassNumberPage();
        }

    }

    public function handleOwnerPassPage()
    {

        if (!is_null($this->text) && $this->checkPassport($this->text)) {
            $eai = new EuroAsiaService();

            $seria = strtoupper(substr($this->text, 0, 2));
            $number = substr($this->text, 2, 7);

            $pinfl = $this->vehicleData['pinfl'];
            $dto = $eai->getPersonByPinflDTO($seria, $number, $pinfl);

            if (!$dto->success){
                $this->showMainPage($this->getMText('Owner found transport'));
            }else{
                $this->ownerData = $dto;
                $this->showDriverRestrictionPage();
            }

        }else{
            $this->showOwnerPassPage();
        }

    }

    public function handlePoliceSeasonPage()
    {
        $message = $this->getKeywordText($this->text);
        if (in_array($message, ['1 year', '6 months', '20 days'])){

            $seasons = [
                ['id' => '8465a831-850f-4445-a995-ef71195094ab', 'days' => 365],
                ['id' => '9848096e-cc12-4dbd-893b-41f2cdfc9a0e', 'days' => 180],
                ['id' => '0d546748-0ba6-43bc-9ce2-1b977ad9e494', 'days' => 20],
            ];
            switch ($message){
                case '1 year':
                    $this->policeSeason = $seasons[0];
                break;
                case '6 months':
                    $this->policeSeason = $seasons[1];
                break;
                case '20 days':
                    $this->policeSeason = $seasons[2];
                break;
            }

            $this->showStartAtPage();
        }else{
            $this->showPoliceSeasonPage();
        }
    }

    public function handleDriverRestrictionPage()
    {
        if (in_array($this->getKeywordText($this->text), ['Limited', 'Not limited'])){
            $this->driverRestriction = $this->getKeywordText($this->text);
            switch ($this->getKeywordText($this->text)) {
                case "Limited":
                    $this->drivers = '';
                    $this->showDriverPage();
                    break;
                case "Not limited":
                    $this->showPoliceSeasonPage();
                    break;
            }
        }else{
            $this->showDriverRestrictionPage();
        }
    }

    public function handlePaymentTypePage()
    {

        switch ($this->text) {
            case EuroAsia::GATEWAY_PAYME:
            case EuroAsia::GATEWAY_CLICK:
                $this->paymentType = $this->text;
                $this->showConfirmPage();
                break;
            default:
                $this->showPaymentTypePage();
        }
    }

    public function handleStartAtPage()
    {
        if (empty($this->text)) {
            $this->showStartAtPage();
            return;
        }

        $startDate = $this->extractValidDate($this->text);

        if (!isset($startDate['success']) || !$startDate['success']) {
            $this->showStartAtPage();
            return;
        }

        $this->startAt = $startDate['date'];
        $this->showPaymentTypePage();
    }


    public function handleDriverPage()
    {

        if (!is_null($this->text) && $this->getKeywordText($this->text) != 'No other drivers'){

            $passData = $this->parsePassportData($this->text);
            if ($passData['success']){
                $seria = $passData['series'];
                $number = $passData['number'];
                $birthdate = self::toIsoDate($passData['birth']);

                $eai = new EuroAsiaService();

                $dto = $eai->getPersonByBirthdateDTO($seria, $number, $birthdate);

                if (!$dto->success){
                    $this->sendMessage($this->getMText('Driver found transport'));
                }else{
                    $drivers = $this->drivers != '' ? $this->drivers : [];
                    $drivers[] = $dto;
                    $this->drivers = $drivers;

                    $count = count($drivers);

                    $driverName = $dto->firstName . ' ' . $dto->lastName;

                    if ($count < 5){
                        $text = sprintf($this->getMText('Drivers saved'), $count, $driverName);
                        $this->showDriverPage(true, $text);
                    }else{
                        $this->showPoliceSeasonPage();
                    }
                }
            }else{
                $this->showDriverPage(true);
            }


        } else {
            $this->showPoliceSeasonPage();
        }


    }

    public function handleConfirmPage()
    {
        if ($this->getKeywordText($this->text) == 'Continue ✅'){


            $drivers = [];
            if ($this->drivers != ''){
                foreach ($this->drivers as $driver) {
                    $drivers[] = [
                        "passportBirthdate" => $driver['birthDate'],
                        "passportNumber" => $driver['number'],
                        "passportSeria" => $driver['seria'],
                        "relativeId" => ""
                    ];
                }
            }



            $vehicle = [
                'licenseNumber' => $this->lisenceNumber,
                'techPassportNumber' => $this->texPassNumber,
                'techPassportSeria' => $this->texPassSeria
            ];




            if ($this->vehicleData['ownerType'] == 'PERSON'){
                $owner = [
                    'isInsurant' => false,
                    'type' => 'PERSON',
                    'person' => [
                        'passportNumber' => $this->ownerData['number'],
                        'passportSeria' => $this->ownerData['seria']
                    ]
                ];

                $insurant = [
                    'type' => 'PERSON',
                    'phoneNumber' => $this->phone,
                    'person' => [
                        'passportNumber' => $this->ownerData['number'],
                        'passportSeria' => $this->ownerData['seria'],
                        'passportBirthdate' => $this->ownerData['birthDate']
                    ],
                    'districtId' =>  $this->ownerData['districtId']
                ];
            }else{


                $owner = [
                    'isInsurant' => true,
                    'type' => 'ORGANIZATION',
                    'organization' => [
                        'inn' => $this->vehicleData['inn'],
                    ]
                ];

                $insurant = [
                    'type' => 'ORGANIZATION',
                    'phoneNumber' => $this->phone,
                    'organization' => [
                        'inn' => $this->vehicleData['inn'],
                    ],
                ];
            }






            $seasonalInsuranceId = $this->policeSeason['id'];
            $billingGateway = $this->paymentType;
            $startAt = self::toIsoDate($this->startAt);
            $driverRestriction = $this->driverRestriction == 'Limited' ? true : false;


            $data = [
                'vehicle' => $vehicle,
                'owner' => $owner,
                'insurant' => $insurant,
                'drivers' => $drivers,
                'billingGateway' => $billingGateway,
                'driverRestriction' => $driverRestriction,
                'seasonalInsuranceId' => $seasonalInsuranceId,
                'startAt' => $startAt,
            ];


            $eai = new EuroAsiaService();
            $dto = $eai->createOsagoDTO($data);


            if ($dto->success){

                $botuser = Botuser::find()->where(['chat_id' => $this->chat_id])->one();
                $season = SeasonalInsurance::find()->where(['seasonId' => $seasonalInsuranceId])->one();


                $police = new Police();
                $police->policeId = $dto->policyId;
                $police->user_id = $botuser->id;
                $police->startAt = date('Y-m-d', strtotime($this->startAt));
                $police->paymentLink = $dto->paymentLink;
                $police->paymentId = $dto->paymentId;
                $police->gateway = $billingGateway;
                $police->amount = 64000;
                $police->driverRestriction = $driverRestriction;
                $police->season_id = $season->id;
                $police->save(false);


                $text = sprintf($this->getMText('Your insurance is ready'), $police->id, $dto->paymentLink);
                $this->showMainPage($text);
            }else{
                $this->sendMessage('Nimadir xato boldi');
            }

        }
        else{
            $text = $this->getMText('Please press one of the buttons.');
            $this->sendMessage($text);
        }
    }

    public function handleWalletPage()
    {
        switch ($this->getKeywordText($this->text)){
            case 'Withdraw':
                $this->showWithdrawTypePage();
                break;
            default:
                $this->showWalletPage();
        }
    }

    public function handleWithdrawTypePage()
    {
        switch ($this->getKeywordText($this->text)){
            case 'to phone':
                $this->withdrawType = Payment::TO_PHONE;
                $this->showWithdrawAccountPage();
                break;
            case 'to card':
                $this->withdrawType = Payment::TO_CARD;
                $this->showWithdrawAccountPage();
                break;
            default:
                $this->showWithdrawTypePage();
        }
    }

    public function handleWithdrawAccountPage()
    {
        $phone_pattern = '/^(?:\+998|998)?(90|91|93|94|95|97|98|99|33|88)\d{7}$/';
        $card_pattern = '/^(8600|9860)\d{12}$/';

        $pattern = $this->withdrawType == Payment::TO_PHONE ? $phone_pattern : $card_pattern;

        if (preg_match($pattern, $this->text)){
            $this->withdrawAccaunt = $this->text;
            $this->showWithdrawAmountPage();
        }else{
            $this->showWithdrawAccountPage();
        }
    }

    public function handleWithdrawAmountPage()
    {
        if ($this->isAmount()){
            if ($this->paymentStatus()){
                if ($this->text <= $this->getUserBalance()){
                    $user = Botuser::findOne(['chat_id' => $this->chat_id]);
                    // User hisobidan pulni yechish
                    $this->minusBalance();

                    // Payment yaratish
                    $payment = new Payment();
                    $payment->user_id = $user->id;
                    $payment->type = $this->withdrawType;
                    $payment->account = $this->withdrawAccaunt;
                    $payment->amount = $this->text;
                    $payment->save();

                    $text = sprintf($this->getMText('Sent payment message'), $payment->id, $payment->account, $this->formatMoney($payment->amount));

                    $data = [
                        'user_id' => $user->id,
                        'payment_order_id' => $payment->id,
                        'account_number' => $payment->account,
                        'amount' => $payment->amount,
                        'payment_type' => $payment->type,
                        'paynet_id' => 3,
                    ];

                    $queue_name = "paynetQueue";
                    Yii::$app->$queue_name->delay(1)->push(new PaynetQueue($data));

                    $this->showMainPage($text);

                }else{
                    $this->sendMessage($this->getMText('Not enough funds!'));
                }
            }else{
                $this->sendMessage($this->getMText('not working payment'));
            }
        }else{
            $this->showWithdrawAmountPage();
        }
    }


    // ***** End Handlers ***** //









    public function __get($name)
    {
        return $this->getKeyValue($name);
    }

    public function __set($name, $value)
    {
        $this->setKeyValue($name, $value);
    }

    protected function getDataArray(): array
    {
        if ($this->_dataCache !== null) {
            return $this->_dataCache;
        }

        $user = Botuser::find()
            ->select(['data'])
            ->where(['chat_id' => $this->chat_id])
            ->one();

        return $this->_dataCache = $user && $user->data
            ? json_decode($user->data, true)
            : [];
    }

    protected function saveData(array $data): void
    {
        $this->_dataCache = $data;

        $user = Botuser::findOne(['chat_id' => $this->chat_id]);
        $user->data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $user->save(false);
    }

    protected function getKeyValue(string $key, $default = '')
    {
        $data = $this->getDataArray();
        return $data[$key] ?? $default;
    }

    protected function setKeyValue(string $key, $value): void
    {
        $data = $this->getDataArray();
        $data[$key] = $value;
        $this->saveData($data);
    }





    public function changeLang(){

        switch ($this->lang){
            case 'uz': $this->lang = 'ru'; break;
            case 'ru': $this->lang = 'uz'; break;
        }
        $this->showMainPage();
    }

    public function setHistory(){
        $model = new History();
        $model->chat_id = $this->chat_id;
        $model->message = $this->text;
        $model->save();
    }

    public function isUser(){
        if (is_null(Botuser::find()->where(['chat_id' => $this->chat_id])->one())){
            return false;
        }
        return true;
    }

    public function addUser(){
        $model = new Botuser();
        $model->chat_id = $this->chat_id;
        $model->fname = $this->firstname;
        $model->lname = $this->lastname;
        $model->username = $this->username;
        $model->save();
    }

    public function clearDatas()
    {
        $this->drivers = '';
        $this->phone = '';
        $this->ownerData = '';
        $this->lisenceNumber = '';
        $this->texPassSeria = '';
        $this->texPassNumber = '';
        $this->vehicleData = '';
        $this->driverRestriction = '';
        $this->policeSeason = '';
        $this->startAt = '';
        $this->paymentType = '';

    }


    public function getMText($keyword)
    {
        $text = Text::findOne(['keyword' => $keyword]);
        if (!is_null($text)){
            $text = $text->toArray();
            $lang = $this->lang;
            if (isset($text[$lang])){
                return $text[$lang];
            }
        }

        return '';
    }

    public function getKeywordText($text)
    {
        $lang = $this->lang;
        if (!empty(Text::findOne([$lang => $text]))){
            $text = Text::findOne([$lang => $text])->toArray();
            if (isset($text['keyword'])){
                return $text['keyword'];
            }
        }

        return '';
    }

    public static function toIsoDate($date)
    {
        $dt = \DateTime::createFromFormat('d.m.Y', $date, new \DateTimeZone('UTC'));
        return $dt ? $dt->format('Y-m-d\TH:i:s.v\Z') : null;
    }

    function formatMoney($number)
    {
        return number_format($number, 0, '.', ' ');
    }

    function checkPassport($passport)
    {
        $value = strtoupper(trim($passport));

        if (preg_match('/^[A-Z]{2}\d{7}$/', $value)) {
            return true;
        }
        return false;
    }

    public function  parsePassportData(string $text): array
    {
        // 1️⃣ Normalize
        $text = strtoupper(trim($text));

            // 2️⃣ Regex
        $pattern = '/
            (?P<series>[A-Z]{2})      # AD, AF, AG
            \s*                       # bo‘sh joy bo‘lishi mumkin
            (?P<number>\d{7})         # 1234567
            \s+                       # kamida 1 bo‘sh joy
            (?P<date>
                (?:\d{2}[\s.,]\d{2}[\s.,]\d{4}|\d{8})
            )
        /x';

        if (!preg_match($pattern, $text, $m)) {
            return ['success' => false];
        }

        // 3️⃣ Sanani tozalash
        $rawDate = preg_replace('/[^0-9]/', '', $m['date']); // 11112025

        $day   = substr($rawDate, 0, 2);
        $month = substr($rawDate, 2, 2);
        $year  = substr($rawDate, 4, 4);

        $birthDate = "$day.$month.$year";

        return [
            'success' => true,
            'series'  => $m['series'],
            'number'  => $m['number'],
            'birth'   => $birthDate,
        ];
    }

    public function extractValidDate(string $text): array
    {
        // 1️⃣ Sanani topamiz
        if (!preg_match('/(\d{2}[\s.,]\d{2}[\s.,]\d{4}|\d{8})/', $text, $m)) {
            return ['success' => false, 'error' => 'Date not found'];
        }

        // 2️⃣ Faqat raqam qoldiramiz
        $raw = preg_replace('/\D/', '', $m[0]); // masalan: 11122025

        if (strlen($raw) !== 8) {
            return ['success' => false, 'error' => 'Invalid date length'];
        }

        $day   = (int)substr($raw, 0, 2);
        $month = (int)substr($raw, 2, 2);
        $year  = (int)substr($raw, 4, 4);

        // 3️⃣ Sana realmi?
        if (!checkdate($month, $day, $year)) {
            return ['success' => false, 'error' => 'Invalid calendar date'];
        }

        $date = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));
        $date->setTime(0, 0, 0);

        // 4️⃣ Bugungi sana
        $today = new DateTime('today');

        if ($date < $today) {
            return ['success' => false, 'error' => 'Date is in the past'];
        }

        return [
            'success' => true,
            'date'    => $date->format('d.m.Y'),
        ];
    }

    public function isAmount(): bool
    {
        return (bool) preg_match('/^[1-9]\d{3,5}$/', $this->text);
    }

    public function paymentStatus()
    {
        return true;
    }

    public function getUserBalance()
    {
        $user = Botuser::findOne(['chat_id' => $this->chat_id]);
        return is_null($user) ? 0 : $user->balance;
    }

    public function minusBalance()
    {
        $user = Botuser::findOne(['chat_id' => $this->chat_id]);
        $user->balance -= $this->text;
        $user->save(false);
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

    public function sendMessageAdmin($text)
    {
        try {
            $telegram = Yii::$app->telegram;
            $content = ['chat_id' => self::ADMIN_ID, 'parse_mode' => 'html', 'text' => $text, /*'disable_web_page_preview' => true*/];
            $telegram->sendMessage($content);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function sendMessageWithID($chat_id, $text)
    {
        try {
            $telegram = Yii::$app->telegram;
            $content = ['chat_id' => $chat_id, 'parse_mode' => 'html', 'text' => $text, 'disable_web_page_preview' => true];
            $telegram->sendMessage($content);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function sendMessageWithKeyborad($text, $option)
    {
        try {
            $telegram = Yii::$app->telegram;
            $keyb = $telegram->buildKeyBoard($option, $onetime=false, $resize = true);

            $content = [
                'chat_id' => $this->chat_id,
                'reply_markup' => $keyb,
                'text' => $text,
                'parse_mode' => 'html',
            ];

            $telegram->sendMessage($content);
        } catch (ErrorException $e) {
            Yii::error($e->getMessage());
            throw new ErrorException($e);
        }
    }

    public function sendMessageWithInlineKeyboard($text, $option, $chat_id = null)
    {
        if (is_null($chat_id)){
            $chat_id = $this->chat_id;
        }

        $keyb = $this->telegram->buildInlineKeyBoard($option);
        $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb, 'parse_mode' => 'html', 'text' => $text];

        $this->telegram->sendMessage($content);
    }

    public function sendMessageWithInlineKeyboardAdmin($text, $option)
    {
        $admin_id = self::ADMIN_ID;
        $this->sendMessageWithInlineKeyboard($text, $option, $admin_id);
    }

    public function setWebhook(){
        $url = Url::base('https') .'/'. Yii::$app->controller->id .'/'. $this->action->id;
        return Yii::$app->telegram->setWebhook($url);
    }
}
