<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "currency".
 *
 * @property int $id
 * @property string $key
 * @property string $title
 * @property string $rate
 *
 * @property Wallet[] $wallets
 */
class Currency extends CActiveRecord
{
    public $scale = 4;
    public $capacity = 10000;
    public static $baseCurrencyKey = "usd";

    /**
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['key', 'title', 'rate'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $aRules = [
            self::SCENARIO_CREATE => [
                [['key','title','rate'], 'required'],
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
                [['rate'], 'number'],
            ]
        ];

        return ArrayHelper::merge( $aRules[$this->getScenario()], $aRules[self::SCENARIO_DEFAULT] );
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'currency';
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
            'rate' => 'Rate',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWallets()
    {
        return $this->hasMany(Wallet::className(), ['currency_key' => 'key']);
    }

    /**
     * @param $rateWallet
     * @param $rate
     * @return bool
     */
    private function _compareRates($rateWallet, $rate)
    {
        if (!function_exists( "bccomp")) {
            return $rateWallet == $rate;
        }

        return (0 === bccomp($rateWallet, $rate, 5));
    }

    /**
     * Конвертация суммы операции в базовую валюту
     * [Сумма в базовой валюте]=[Сумма в валюте операции]/[Курс валюты перевода]
     *
     * @param $sumOperationCurrency
     * @return string
     */
    public function exchangeSumToBase($sumOperationCurrency)
    {
        $sumOperationCurrency = floatval($sumOperationCurrency);
        $rateOperationCurrency = floatval($this->rate);

        if (!function_exists( "bcdiv") || !function_exists( "bcmul")) {
            return $sumOperationCurrency / $rateOperationCurrency;
        }

        return round(bcdiv($sumOperationCurrency, $rateOperationCurrency, $this->scale * 2), $this->scale);
    }

    /**
     * Ковертация суммы операции в валюты кошелька
     * [Сумма в валюте кошелька]=[Сумма в валюте перевода]*[Курс(Валюта кошелька/валюта перевода)])]
     *
     * @param $sumOperationCurrency
     * @param $rateOperationCurrency
     * @return float|string
     */
    public function exchangeSumToWalletCurrency($sumOperationCurrency, $rateOperationCurrency)
    {
        $rateWallet = floatval($this->rate);
        $sumOperationCurrency = floatval($sumOperationCurrency);
        if ($this->_compareRates($rateWallet, $rateOperationCurrency)) {
            return $sumOperationCurrency;
        }

        if (!function_exists( "bcdiv") || !function_exists( "bcmul")) {
            $rate = $rateWallet / $rateOperationCurrency;
            return round($sumOperationCurrency * $rate, $this->scale);
        }

        $rate = bcdiv($rateWallet, $rateOperationCurrency, $this->scale * 2);
        return round(bcmul($sumOperationCurrency, $rate, $this->scale * 2), $this->scale);
    }
}
