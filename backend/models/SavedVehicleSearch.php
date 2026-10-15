<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\SavedVehicle;

/**
 * SavedVehicleSearch represents the model behind the search form of `common\models\SavedVehicle`.
 */
class SavedVehicleSearch extends SavedVehicle
{
    public $chat_id;

    public function rules()
    {
        return [
            [['id', 'botuser_id', 'chat_id'], 'integer'],
            [['gov_number', 'ersp_check_status'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params, $formName = null)
    {
        $query = SavedVehicle::find()->joinWith('botuser');

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
            'saved_vehicle.id' => $this->id,
            'saved_vehicle.botuser_id' => $this->botuser_id,
            'saved_vehicle.ersp_check_status' => $this->ersp_check_status,
            'botuser.chat_id' => $this->chat_id,
        ]);

        $query->andFilterWhere(['like', 'saved_vehicle.gov_number', $this->gov_number]);

        return $dataProvider;
    }
}
