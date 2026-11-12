<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Police;

/**
 * PoliceSearch represents the model behind the search form of `common\models\Police`.
 */
class PoliceSearch extends Police
{
    public $created_at_from;
    public $created_at_to;
    public $totalAmount = 0;
    public $licenseNumber;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'status', 'amount', 'driverRestriction', 'season_id', 'provider_id', 'anketa_id', 'payment_status'], 'integer'],
            [['policeId', 'startAt', 'endAt', 'pdfUrl', 'paymentId', 'paymentLink', 'gateway', 'created_at', 'updated_at', 'created_at_from', 'created_at_to', 'licenseNumber'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $formName = null)
    {
        $query = Police::find()->with(['season', 'vehicles']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ]
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'endAt' => $this->endAt,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'gateway' => $this->gateway,
            'provider_id' => $this->provider_id,
            'anketa_id' => $this->anketa_id,
            'amount' => $this->amount,
            'driverRestriction' => $this->driverRestriction,
            'season_id' => $this->season_id,
            'updated_at' => $this->updated_at,
        ]);

        // Avtomobil davlat raqami bo'yicha (bo'shliq/registr farqisiz) — subquery, qatorlar dublikat bo'lmasligi uchun
        $plate = strtoupper(preg_replace('/\s+/', '', (string)$this->licenseNumber));
        if ($plate !== '') {
            $query->andWhere(['id' => \common\models\Vehicle::find()
                ->select('police_id')
                ->where(['like', 'UPPER(REPLACE(licenseNumber, " ", ""))', $plate])]);
        }

        $query->andFilterWhere(['like', 'policeId', $this->policeId])
            ->andFilterWhere(['like', 'pdfUrl', $this->pdfUrl])
            ->andFilterWhere(['like', 'paymentId', $this->paymentId])
            ->andFilterWhere(['like', 'paymentLink', $this->paymentLink])
            ->andFilterWhere(['=', 'DATE(startAt)', $this->startAt ?: null])
            ->andFilterWhere(['>=', 'created_at', $this->created_at_from ? $this->created_at_from . ' 00:00:00' : null])
            ->andFilterWhere(['<=', 'created_at', $this->created_at_to ? $this->created_at_to . ' 23:59:59' : null]);

        $this->totalAmount = (clone $query)->sum('amount') ?? 0;

        return $dataProvider;
    }
}
