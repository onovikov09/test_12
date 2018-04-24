<?php

namespace app\models;

use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "country".
 *
 * @property int $id
 * @property string $key
 * @property string $title
 *
 * @property Wallet[] $wallets
 */
class Country extends CActiveRecord
{
    /**
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['key', 'title'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $aRules = [
            self::SCENARIO_CREATE => [
                [['key', 'title'], 'required'],
                [['key'], 'unique'],
            ],
            self::SCENARIO_DEFAULT => [
                [['key'], 'string', 'min' => 3, 'max' =>3],
                [['title'], 'string', 'max' => 100],
                [['key'], 'filter', 'filter' => function($value) {
                    return trim(strtolower(htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8')));
                }],
                [['title'], 'filter', 'filter' => function($value) {
                    return trim(htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8'));
                }],
                [['key'], 'match', 'pattern' => '/^[a-z]+$/ui'],
            ]
        ];

        return ArrayHelper::merge( $aRules[$this->getScenario()], $aRules[self::SCENARIO_DEFAULT] );
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'country';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => 'Key',
            'title' => 'Title',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWallets()
    {
        return $this->hasMany(Wallet::className(), ['country_id' => 'id']);
    }
}
