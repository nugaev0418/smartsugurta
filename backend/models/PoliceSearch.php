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
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'status', 'amount', 'driverRestriction', 'season_id'], 'integer'],
            [['policeId', 'startAt', 'endAt', 'pdfUrl', 'paymentId', 'paymentLink', 'gateway', 'created_at', 'updated_at'], 'safe'],
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
        $query = Police::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
            'status' => $this->status,
            'amount' => $this->amount,
            'driverRestriction' => $this->driverRestriction,
            'season_id' => $this->season_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'policeId', $this->policeId])
            ->andFilterWhere(['like', 'pdfUrl', $this->pdfUrl])
            ->andFilterWhere(['like', 'paymentId', $this->paymentId])
            ->andFilterWhere(['like', 'paymentLink', $this->paymentLink])
            ->andFilterWhere(['like', 'gateway', $this->gateway]);

        return $dataProvider;
    }
}
