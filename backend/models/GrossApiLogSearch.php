<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\GrossApiLog;

/**
 * GrossApiLogSearch represents the model behind the search form of `common\models\GrossApiLog`.
 */
class GrossApiLogSearch extends GrossApiLog
{
    public function rules()
    {
        return [
            [['id', 'chat_id', 'attempt', 'police_id'], 'integer'],
            [['run_id', 'label', 'success', 'created_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params, $formName = null)
    {
        $query = GrossApiLog::find();

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
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'chat_id' => $this->chat_id,
            'attempt' => $this->attempt,
            'police_id' => $this->police_id,
            'success' => $this->success,
        ]);

        $query->andFilterWhere(['like', 'run_id', $this->run_id]);
        $query->andFilterWhere(['like', 'label', $this->label]);

        return $dataProvider;
    }
}
