<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "wallet".
 *
 * @property int $id
 * @property string $number
 * @property string $full_name
 * @property string $amount
 * @property string $currency_key
 * @property int $country_id
 * @property int $city_id
 *
 * @property Currency $currencyKey
 * @property Country $country
 * @property City $city
 */
class Wallet extends CActiveRecord
{
    /**
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['full_name', 'currency_key', 'country_id', 'city_id'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wallet';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $aRules = [
            self::SCENARIO_CREATE => [
                [['full_name', 'number', 'currency_key', 'country_id', 'city_id'], 'required'],
                [['number'], 'unique'],
            ],
            self::SCENARIO_DEFAULT => [
                [['amount'], 'number'],
                [['country_id', 'city_id'], 'integer'],
                [['number'], 'string', 'min' => 25, 'max' => 25],
                [['full_name'], 'string', 'max' => 100],
                [['currency_key'], 'string', 'min' => 3, 'max' => 3],
                [['currency_key'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::className(),
                    'targetAttribute' => ['currency_key' => 'key']],
                [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::className(),
                    'targetAttribute' => ['country_id' => 'id']],
                [['city_id'], 'exist', 'skipOnError' => true, 'targetClass' => City::className(),
                    'targetAttribute' => ['city_id' => 'id']],
            ]
        ];

        return ArrayHelper::merge( $aRules[$this->getScenario()], $aRules[self::SCENARIO_DEFAULT] );
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'number' => 'Number',
            'full_name' => 'Full Name',
            'amount' => 'Amount',
            'currency_key' => 'Currency Key',
            'country_id' => 'Country ID',
            'city_id' => 'City ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurrencyKey()
    {
        return $this->hasOne(Currency::className(), ['key' => 'currency_key']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Country::className(), ['id' => 'country_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::className(), ['id' => 'city_id']);
    }

    /**
     * @param $params
     * @return Wallet|bool
     * @throws \yii\base\Exception
     */
    public static function createWallet($params)
    {
        $wallet = new Wallet(["scenario" => Wallet::SCENARIO_CREATE]);
        $wallet->number = Yii::$app->getSecurity()->generateRandomString(25);
        if (!$wallet->load(["Wallet" => $params]) || !$wallet->save()) {
            return false;
        }

        return $wallet;
    }

    /**
     * @param $numberFrom
     * @return bool
     */
    public static function isTransfer($numberFrom = false)
    {
        return false !== $numberFrom;
    }

    /**
     * @param $currencySum
     * @param $currencyKey
     * @param bool $walletFrom
     * @return bool
     * @throws \yii\db\Exception
     */
    public function saveChanges($currencySum, $currencyKey, $walletFrom = false)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {

            $this->save(false);
            WalletLog::logOperation("refill", $currencySum, $currencyKey, $this, $walletFrom);

            if ($walletFrom) {
                $walletFrom->save(false);
                WalletLog::logOperation("transfer", $currencySum, $currencyKey, $this, $walletFrom);
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->addError("id", "Operation failed.");
            return false;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->addError("id", "Operation failed.");
            return false;
        }

        return true;
    }

    /**
     * @param $delta
     * @return bool
     */
    public function calculateBalance($delta)
    {
        if (!$delta) {
            return false;
        }

        $this->amount += $delta;

        return $this->validate();
    }

    /**
     * @param $currencySum
     * @param $currencyKey
     * @return bool|float|string
     */
    public function convertMoneyToWalletCurrency($currencySum, $currencyKey)
    {
        if (!$currencyKey || !$currencySum) {
            return false;
        }

        if ($currencyKey == $this->currency_key) {
            return $currencySum;
        }

        $currency = Currency::findOne(["key" => $currencyKey]);
        if (!$currency || !$currency->rate || !($this->currencyKey || $this->currencyKey->rate)) {
            $this->addError("id", ($currency ? "The exchange rate is not set." : "Currency not found."));
            return false;
        }

        return $this->currencyKey->exchangeSumToWalletCurrency($currencySum, $currency->rate);
    }

    /**
     * @param $currencySum
     * @param $currencyKey
     * @return bool|float|string
     */
    public function convertMoneyToBase($currencySum, $currencyKey)
    {
        if (!$currencyKey || !$currencySum) {
            return false;
        }

        if (Currency::$baseCurrencyKey == strtolower($currencyKey)) {
            return $currencySum;
        }

        $currency = Currency::findOne(["key" => $currencyKey]);
        if (!$currency || !$currency->rate) {
            $this->addError("id", ($currency ? "The exchange rate is not set." : "Currency not found."));
            return false;
        }

        return $currency->exchangeSumToBase($currencySum);
    }

    /**
     * @param $sum
     * @param $currencyKey
     * @param $numberFrom
     * @return bool
     * @throws \yii\db\Exception
     */
    public function doOperation($sum, $currencyKey, $numberFrom)
    {
        if (!extension_loaded('bcmath')) {
            $this->addError("id", "The extension for working with money is not set.");
            return false;
        }

        if (!Wallet::isTransfer($numberFrom)) {

            $sumPlus = $this->convertMoneyToWalletCurrency($sum, $currencyKey);
            if (!$sumPlus || !$this->calculateBalance($sumPlus)
                || !$this->saveChanges($sum, $currencyKey))
            {
                return false;
            }

            return true;
        }

        if (!$walletFrom = Wallet::findOne(["number" => $numberFrom])) {
            $this->addError("id", "Wallet from not found.");
            return false;
        }

        $sumMinus = $walletFrom->convertMoneyToWalletCurrency($sum, $currencyKey);
        if (!$sumMinus || $sumMinus > $walletFrom->amount) {
            $this->addError("id", ($walletFrom->errors ? $walletFrom->getFirstErrors() : "Insufficient funds."));
            return false;
        }

        $sumPlus = $this->convertMoneyToWalletCurrency($sum, $currencyKey);
        if (!$this->calculateBalance($sumPlus) || !$walletFrom->calculateBalance(-$sumMinus)
            || !$this->saveChanges($sum, $currencyKey, $walletFrom))
        {
            return false;
        }

        return true;
    }
}
