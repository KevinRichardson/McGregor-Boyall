<?php

/*
 * Author : Kevin Richardson
 * September 2016
 */

include_once 'StockDbModels.php';
include_once 'StockDbTestData.php';

class StockDbInterface {

    protected $stocksModel;
    protected $tradesModel;

    public function __construct() {

        $this->stocksModel = new StockDbModel_Stocks();
        $this->tradesModel = new StockDbModel_Trades();

        // this to mimic database data
        $testData = new StockDbTestData();
        $this->stockDatabase = $testData->getStockDatabase();
    }

    public function getStocks($params = false) {
        /*
         * Param $params expected as false or variant of model-specific search filter:
         * array(
         *    'StockSymbol' => StockDb_config::$stockSymbols,
         * );
         * 
         * if $params==false, then all stocks will be returned, else filtered as per $params
         */

        return $this->getStockData($params, 'stocksModel', 'getStocks');
    }

    public function getTrades($params = false) {
        /*
         * Param $params expected as false or variant of model-specific search filter:
         * array(
         *    'StockSymbol' => StockDb_config::$stockSymbols,
         *    'Timestamp_low' => StockDb_config::$timestampFormat,
         *    'Timestamp_high' => StockDb_config::$timestampFormat,
         *    'BuySellIndicator' => StockDb_config::$buySellIndicators,
         * );
         * 
         * if $params==false, then all stocks will be returned, else filtered as per $params
         */

        return $this->getStockData($params, 'tradesModel', 'getTrades');
    }

    public function recordTrade($trade) {
        /*
         * Param $trade expected as defined by StockDbModel_Trades::$structure
         */
        $this->stockDatabase = $this->tradesModel->addToTable($this->stockDatabase, $trade);
    }

    protected function getStockData($params, $model, $fnName) {
        /*
         * Param $params expected as false or variant of :
         * (model == 'stocksModel')
         * array(
         *    'StockSymbol' => StockDb_config::$stockSymbols,
         * );
         * or
         * (model == 'tradesModel')
         * array(
         *    'StockSymbol' => StockDb_config::$stockSymbols,
         *    'Timestamp_low' => StockDb_config::$timestampFormat,
         *    'Timestamp_high' => StockDb_config::$timestampFormat,
         *    'BuySellIndicator' => StockDb_config::$buySellIndicators,
         * );
         * or false
         * Param $model expected as string as one of ('stocksModel', 'tradesModel')
         * Param $fnName expected as string as name of calling function
         */
        try {
            return $this->{$model}->find($this->stockDatabase, $params);
        } catch (Exception $e) {

            $e = $e->getMessage();
            $error = '<p>Invalid params, StockDbManager->' . $fnName . '()</p>' . $e;

            throw new Exception($error);
        }
    }

}
