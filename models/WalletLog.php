<?php

namespace app\models;

/**
 * This is the model class for table "wallet_log".
 *
 * @property int $id
 * @property int $wallet_to
 * @property int $wallet_from
 * @property string $currency_key
 * @property string $currency_sum
 * @property string $usd_sum
 * @property string $dt
 * @property string $description
 */
class WalletLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wallet_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wallet_to', 'wallet_from'], 'integer'],
            [['currency_sum', 'usd_sum'], 'required'],
            [['currency_sum', 'usd_sum'], 'number'],
            [['dt'], 'safe'],
            [['currency_key'], 'string', 'max' => 3],
            [['description'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'wallet_to' => 'Wallet To',
            'wallet_from' => 'Wallet From',
            'currency_key' => 'Currency Key',
            'currency_sum' => 'Currency Sum',
            'usd_sum' => 'Usd Sum',
            'dt' => 'Dt',
            'description' => 'Description',
        ];
    }

    /**
     * @param $operation
     * @param $currencySum
     * @param $currencyKey
     * @param $walletTo
     * @param bool $walletFrom
     * @return mixed
     */
    public static function logOperation($operation, $currencySum, $currencyKey, $walletTo, $walletFrom = false)
    {
        $walletLog = new static();
        if ("refill" == $operation) {
            $walletLog->wallet_to = $walletTo->id;
            $walletLog->description = "Replenishment for " . strval($currencySum) . " " . $currencyKey;
            $walletLog->currency_sum = $walletTo->convertMoneyToWalletCurrency($currencySum, $currencyKey);
            $walletLog->currency_key = $walletTo->currency_key;
            $walletLog->usd_sum = $walletTo->convertMoneyToBase($currencySum, $currencyKey);
            if ($walletFrom) {
                $walletLog->wallet_from = $walletFrom->id;
                $walletLog->description = "Transfer " . strval($currencySum) . " " . $currencyKey . " from "
                    . $walletFrom->full_name . " (" . $walletFrom->number . ")";
            }

            return $walletLog->save();
        }

        $walletLog->wallet_to = $walletFrom->id;
        $walletLog->wallet_from = $walletTo->id;
        $walletLog->currency_sum = -$walletFrom->convertMoneyToWalletCurrency($currencySum, $currencyKey);
        $walletLog->usd_sum = -$walletFrom->convertMoneyToBase($currencySum, $currencyKey);
        $walletLog->currency_key = $walletFrom->currency_key;
        $walletLog->description = "Transfer " . strval(-$currencySum) . " " . $currencyKey . " to "
            . $walletTo->full_name . " (" . $walletTo->number . ")";

        return $walletLog->save();
    }
}
