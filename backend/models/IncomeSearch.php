<?php

namespace backend\models;

use common\models\Income;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class IncomeSearch extends Income
{
    public $created_at_from;
    public $created_at_to;

    public function rules(): array
    {
        return [
            [['id', 'user_id', 'amount'], 'integer'],
            [['reason', 'created_at', 'created_at_from', 'created_at_to'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Income::find()->with('user');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id'      => $this->id,
            'user_id' => $this->user_id,
            'amount'  => $this->amount,
        ]);

        $query->andFilterWhere(['like', 'reason', $this->reason])
              ->andFilterWhere(['>=', 'created_at', $this->created_at_from ? $this->created_at_from . ' 00:00:00' : null])
              ->andFilterWhere(['<=', 'created_at', $this->created_at_to   ? $this->created_at_to   . ' 23:59:59' : null]);

        return $dataProvider;
    }
}
