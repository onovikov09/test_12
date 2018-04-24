<?php

namespace app\controllers;

use app\models\Wallet;
use app\models\WalletLog;
use yii\web\Controller;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            "error" => [
                "class" => "yii\web\ErrorAction",
            ],
        ];
    }

    /**
     * Страница отчета
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render("index");
    }

    /**
     * Страница UI api
     *
     * @return string
     */
    public function actionApi()
    {
        return $this->render("api_wrap");
    }

    /**
     * Экспорт в csv
     *
     * @param $table
     * @param int $offset
     * @param int $limit
     * @param bool $wallet_id
     * @param bool $dt_start
     * @param bool $dt_end
     */
    public function actionExport($table, $count, $wallet_id = false, $dt_start = false, $dt_end = false)
    {
        $titles = [
            'grid_wallet' => ['id кошелька', 'Номер кошелька', 'Имя Фамилия', 'Остаток', 'Валюта кошелька'],
            'grid_wallet_log' => [
                'Ид кошелька зачисления',
                'Ид кошелька списания',
                'Валюта кошелька',
                'Сумма операции в валюте кошелька',
                'Сумма операции в USD',
                'Дата выполнения операции',
                'Описание операции'
            ]
        ];

        $this->layout = false;

        if (!$table || !isset($titles[$table]) || ( 'grid_wallet_log' == $table && !$wallet_id)) {
            echo "Не переданы необходимые параметры!"; exit;
        }

        if ('grid_wallet' == $table) {
            $data = Wallet::find()->select(['id', 'number', 'full_name', 'amount', 'currency_key'])
                ->limit($count)->orderBy(null)->asArray()->all();
        } else {

            $data = WalletLog::find()->where([">=", "dt", $dt_start ? $dt_start : 0])->andWhere(["wallet_to" => $wallet_id])
                ->andWhere(["<=", "dt", ($dt_end ? $dt_end : new \yii\db\Expression("NOW()"))])
                ->select(['wallet_to', 'wallet_from', 'currency_key', 'currency_sum', 'usd_sum', 'dt', 'description'])
                ->limit($count)->orderBy(null)->asArray()->all();
        }

        if (empty($data)) {
            echo "Нет данных для отчета!"; exit;
        }

        $this->_download_send_headers("export.csv");
        echo $this->_array2csv($data, $titles[$table]); exit;
    }

    /**
     * @param $filename
     */
    private function _download_send_headers($filename)
    {
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }

    /**
     * @param array $array
     * @param $titles
     * @return null|string
     */
    private function _array2csv(array &$array, $titles)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, $titles, ';');
        foreach ($array as $row) {
            fputcsv($df, $row, ';');
        }
        fclose($df);
        return ob_get_clean();
    }

}
