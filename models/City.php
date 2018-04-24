<?php

namespace app\models;

use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "city".
 *
 * @property int $id
 * @property string $title
 *
 * @property Wallet[] $wallets
 */
class City extends CActiveRecord
{
    /**
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['title'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $aRules = [
            self::SCENARIO_CREATE => [
                [['title'], 'required'],
                [['title'], 'unique'],
            ],
            self::SCENARIO_DEFAULT => [
                [['title'], 'string', 'max' => 100],
                [['title'], 'filter', 'filter' => function($value) {
                    return str_replace(" ", "_",
                        trim(ucwords(strtolower(htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8'))))
                    );
                }],
                [['title'], 'match', 'pattern' => '/^[A-za-z_-]+$/ui'],
            ]
        ];

        return ArrayHelper::merge( $aRules[$this->getScenario()], $aRules[self::SCENARIO_DEFAULT] );
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'city';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWallets()
    {
        return $this->hasMany(Wallet::className(), ['city_id' => 'id']);
    }
}
