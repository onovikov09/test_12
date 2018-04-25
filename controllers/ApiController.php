<?php

namespace app\controllers;

use app\models\City;
use app\models\Country;
use app\models\Currency;
use app\models\Wallet;
use app\models\WalletLog;
use Yii;
use yii\web\Controller;

/**
 * @SWG\Swagger(
 *     basePath="/api",
 *     schemes={"http"},
 *     host="exn.local",
 *     produces={"application/json"},
 *     consumes={"application/x-www-form-urlencoded"},
 *     @SWG\Info(
 *         version="1.0.0",
 *         title="Wallets",
 *         description="Transfer wallet to wallet",
 *     ),
 *     @SWG\Definition(
 *         definition="ErrorModel",
 *         type="object",
 *         required={"code", "message"},
 *         @SWG\Property(
 *             property="code",
 *             type="integer",
 *             format="int32"
 *         ),
 *         @SWG\Property(
 *             property="message",
 *             type="string"
 *         )
 *     )
 * )
 */
class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @SWG\Get(path="/wallets?offset={offset}&limit={limit}",
     *     tags={"Wallet"},
     *     summary="Get a list of wallets with a limit and offset.",
     *     @SWG\Parameter(
     * 			name="offset",
     * 			in="path",
     * 			type="integer",
     * 			description="Offset"
     * 		),
     *     @SWG\Parameter(
     * 			name="limit",
     * 			in="path",
     * 			type="integer",
     * 			description="Limit",
     * 		),
     *     @SWG\Response(
     *         response = 200,
     *         description = "Wallets list response",
     *     ),
     *     @SWG\Response(
     *     response="default",
     *     description="an ""unexpected"" error"
     *   )
     * )
     */
    public function actionWallets_get($offset = 0, $limit = 10)
    {
        return $this->asJson(Wallet::find()->offset($offset)->limit($limit)->orderBy(null)->all());
    }

    /**
     * @SWG\Get(path="/wallets_log?wallet_id={wallet_id}&offset={offset}&limit={limit}&dt_start={dt_start}&dt_end={dt_end}",
     *     tags={"WalletLog"},
     *     summary="Get a list of wallet log.",
     *     @SWG\Parameter(
     * 			name="wallet_id",
     * 			in="path",
     * 			type="integer",
     * 			description="The ID of the wallet log"
     * 		),
     *     @SWG\Parameter(
     * 			name="offset",
     * 			in="path",
     * 			type="integer",
     * 			description="Offset",
     *          default= 0
     * 		),
     *     @SWG\Parameter(
     * 			name="limit",
     * 			in="path",
     * 			type="integer",
     * 			description="Limit",
     *          default= 5
     * 		),
     *     @SWG\Parameter(
     * 			name="dt_start",
     * 			in="path",
     * 			type="string",
     *          default="",
     * 			description="Date start period",
     * 		),
     *     @SWG\Parameter(
     * 			name="dt_end",
     * 			in="path",
     * 			type="string",
     *          default="",
     * 			description="Date end period",
     * 		),
     *     @SWG\Response(
     *         response = 200,
     *         description = "Wallet_log list response",
     *     ),
     *     @SWG\Response(
     *     response="default",
     *     description="an ""unexpected"" error"
     *   )
     * )
     */
    public function actionWallets_log_get($wallet_id = false, $dt_start = false, $dt_end = false, $offset = 0, $limit = 5)
    {
        $walletLog = WalletLog::find()->leftJoin('wallet', 'wallet.id = wallet_log.wallet_to')
            ->select([
                'currency_sum', 'usd_sum', 'dt', 'description', 'wallet_to', 'wallet_log.currency_key', 'wallet_log.id',
                'wallet.full_name'
            ])
            ->andFilterWhere([">=", "dt", $dt_start])
            ->andFilterWhere(["<=", "dt", ($dt_end ? $dt_end : new \yii\db\Expression("NOW()"))])
            ->andFilterWhere(["wallet_to" => $wallet_id]);

        //несмотря на то, что индекс создан, оптимизатор на маленьком объеме данных использует full scan
        //чтобы принудительно заставить его использовать индекс нужно раскоментировать строку нижу, но это плохая практика
        //->from(new \yii\db\Expression('{{%wallet_log}} FORCE INDEX (dt_wallet_to)'))

        return $this->asJson($walletLog->limit($limit)->offset($offset)->asArray()->all());
    }

    /**
     * @SWG\Post(path="/wallets",
     *     tags={"Wallet"},
     *     summary="Register a new wallet.",
     *     @SWG\Parameter(
     *          name="full_name",
     *          in="formData",
     *          required=true,
     *          type="string",
     *          description="User full name"
     *      ),
     *     @SWG\Parameter(
     *          name="country_id",
     *          in="formData",
     *          required=true,
     *          type="integer",
     *          description="Country id"
     *      ),
     *     @SWG\Parameter(
     *          name="city_id",
     *          in="formData",
     *          required=true,
     *          type="integer",
     *          description="City id"
     *      ),
     *     @SWG\Parameter(
     *          name="currency_key",
     *          in="formData",
     *          required=true,
     *          type="string",
     *          description="Currency key"
     *      ),
     *     @SWG\Response(
     *         response = 201,
     *         description = "Wallet item response",
     *     ),
     *     @SWG\Response(
     *     response="default",
     *     description="an ""unexpected"" error"
     *   )
     * )
     */
    public function actionWallets_post()
    {
        $countryId = Yii::$app->request->post("country_id", false);
        $cityId = Yii::$app->request->post("city_id", false);
        $currencyKey = Yii::$app->request->post("currency_key", false);

        if (!$countryId || !$cityId || !$currencyKey
            || !$wallet = Wallet::createWallet(Yii::$app->request->post()))
        {
            return $this->_sendError();
        }

        Yii::$app->response->statusCode = 201;
        return $this->asJson($wallet);
    }

    /**
     * @SWG\Put(path="/wallets?wallet_to={wallet_to}",
     *     tags={"Wallet"},
     *     summary="Update wallet by number.",
     *     @SWG\Parameter(
     * 			name="wallet_to",
     * 			in="path",
     * 			required=true,
     * 			type="string",
     * 			description="The unique number of the wallet for replenishment",
     * 		),
     *     @SWG\Parameter(
     * 			name="wallet_from",
     * 			in="formData",
     * 			type="string",
     * 			description="The unique number of the wallet, from where the transfer",
     * 		),
     *     @SWG\Parameter(
     *          name="sum",
     *          in="formData",
     *          required=true,
     *          type="number",
     *          description="Operation sum"
     *      ),
     *     @SWG\Parameter(
     *          name="currency_key",
     *          in="formData",
     *          required=true,
     *          type="string",
     *          description="Currency key"
     *      ),
     *     @SWG\Response(
     *         response = 200,
     *         description = "Wallet item response",
     *     ),
     * )
     */
    public function actionWallets_put($wallet_to)
    {
        $numberFrom = Yii::$app->request->post("wallet_from", false);
        $currencyKey = Yii::$app->request->post("currency_key", false);
        $sum = Yii::$app->request->post("sum", 0);

        $walletTo = Wallet::findOne(["number" => $wallet_to]);
        if (!$currencyKey || !$sum || !$walletTo || !$walletTo->doOperation($sum, $currencyKey, $numberFrom)) {
            return $this->_sendError($walletTo ? $walletTo->getFirstErrors() : "Wallet to not found.");
        }

        return $this->asJson($walletTo);
    }

    /**
     * @SWG\Put(path="/currency?key={key}",
     *     tags={"Currency"},
     *     summary="Set the exchange rate.",
     *     @SWG\Parameter(
     * 			name="key",
     * 			in="path",
     * 			required=true,
     * 			type="string",
     * 			description="The unique currency key",
     * 		),
     *     @SWG\Parameter(
     * 			name="rate",
     * 			in="formData",
     * 			type="number",
     * 			description="Rate",
     * 		),
     *     @SWG\Parameter(
     * 			name="title",
     * 			in="formData",
     * 			type="string",
     * 			description="Currency name",
     * 		),
     *     @SWG\Response(
     *         response = 200,
     *         description = "Currency item response"
     *     ),
     * )
     */
    public function actionCurrency_put($key)
    {
        $params = Yii::$app->request->post();
        $params["key"] = $key;
        $currency = Currency::findOrCreate(["key" => $key], $params);
        if (!$currency || !$currency->updateModel($params)) {
            return $this->_sendError($currency->getFirstErrors());
        }

        return $this->asJson($currency);
    }

    /**
     * @SWG\Post(path="/currency",
     *     tags={"Currency"},
     *     summary="Register a new currency.",
     *     @SWG\Parameter(
     *          name="key",
     *          in="formData",
     *          required=true,
     *          type="string",
     *          description="Unique currency key"
     *      ),
     *     @SWG\Parameter(
     * 			name="rate",
     * 			in="formData",
     * 			required=true,
     * 			type="number",
     * 			description="Rate",
     * 		),
     *     @SWG\Parameter(
     *          name="title",
     *          in="formData",
     *          required=true,
     *          type="string",
     *          description="Currency name"
     *      ),
     *     @SWG\Response(
     *         response = 201,
     *         description = "Currency item response"
     *     ),
     *     @SWG\Response(
     *     response="default",
     *     description="an ""unexpected"" error"
     *   )
     * )
     */
    public function actionCurrency_post()
    {
        $currencyKey = Yii::$app->request->post("key", false);
        $currencyTitle = Yii::$app->request->post("title", false);
        if (!$currencyKey || !$currencyTitle || $currency = Currency::findOne(["key" => $currencyKey])) {
            return $this->_sendError($currency ? "Currency already exists." : "");
        }

        $currency = Currency::createModel(Yii::$app->request->post());
        if ($currency->hasErrors()) {
            return $this->_sendError($currency->getFirstErrors());
        }

        Yii::$app->response->statusCode = 201;
        return $this->asJson($currency);
    }

    /**
     * @SWG\Post(path="/country",
     *     tags={"Country"},
     *     summary="Register a new country.",
     *     @SWG\Parameter(
     *          name="key",
     *          in="formData",
     *          required=true,
     *          type="string",
     *          description="Unique country key"
     *      ),
     *     @SWG\Parameter(
     *          name="title",
     *          in="formData",
     *          required=true,
     *          type="string",
     *          description="Country title"
     *      ),
     *     @SWG\Response(
     *         response = 201,
     *         description = "Country item response"
     *     ),
     *     @SWG\Response(
     *     response="default",
     *     description="an ""unexpected"" error"
     *   )
     * )
     */
    public function actionCountry_post()
    {
        $countryKey = Yii::$app->request->post("key", false);
        if (!$countryKey || $country = Country::findOne(["key" => $countryKey])) {
            return $this->_sendError($country ? "Country already exists." : "");
        }

        $country = Country::createModel(Yii::$app->request->post());
        if ($country->hasErrors()) {
            return $this->_sendError($country->getFirstErrors());
        }

        Yii::$app->response->statusCode = 201;
        return $this->asJson($country);
    }

    /**
     * @SWG\Post(path="/city",
     *     tags={"City"},
     *     summary="Register a new city.",
     *     @SWG\Parameter(
     *          name="title",
     *          in="formData",
     *          required=true,
     *          type="string",
     *          description="City title"
     *      ),
     *     @SWG\Response(
     *         response = 201,
     *         description = "City item response"
     *     ),
     *     @SWG\Response(
     *     response="default",
     *     description="an ""unexpected"" error"
     *   )
     * )
     */
    public function actionCity_post()
    {
        $cityTitle = Yii::$app->request->post("title", false);
        if (!$cityTitle || $city = City::findOne(["title" => $cityTitle])) {
            return $this->_sendError($city ? "City already exists." : "");
        }

        $city = City::createModel(Yii::$app->request->post());
        if ($city->hasErrors()) {
            return $this->_sendError($city->getFirstErrors());
        }

        Yii::$app->response->statusCode = 201;
        return $this->asJson($city);
    }

    /**
     * Возвращает сумму записей
     *
     * @param array $ids
     * @return \yii\web\Response
     */
    public function actionWallets_log_sum_get()
    {
        $ids = Yii::$app->request->get('ids', []);
        $walletLog = WalletLog::find()->where(["in", "id", $ids])->orderBy(null);

        return $this->asJson([
            number_format(round($walletLog->sum("currency_sum"), 2), 2, '.', ' '),
            number_format(round($walletLog->sum("usd_sum"), 2), 2, '.', ' ')
        ]);
    }

    /**
     * Return error
     *
     * @param string $mesage
     * @return \yii\web\Response
     */
    private function _sendError($mesage = "Check params.")
    {
        Yii::$app->response->statusCode = 400;
        return $this->asJson(["error" => ["message" => $mesage]]);
    }
}