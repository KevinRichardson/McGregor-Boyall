<?php

/*
 * Author : Kevin Richardson
 * September 2016
 */

include_once 'StockDbModels.php';

class StockDbTestData {

    protected $stockDatabase = array();
    protected $stocksModel;
    protected $tradesModel;

    public function __construct() {

        $this->stocksModel = new StockDbModel_Stocks();
        $this->tradesModel = new StockDbModel_Trades();

        $this->buildTestData();
    }

    public function getStockDatabase() {

        return $this->stockDatabase;
    }

    protected function buildTestData() {

        $this->buildTestStocks();
        $this->buildTestTrades();
    }

    protected function buildTestStocks() {
        /*
         * from Table1. Sample data from the Global Beverage Corporation Exchange
         */
        $this->addTestStock('TEA', 'Common', 0, false, 100);
        $this->addTestStock('POP', 'Common', 8, false, 100);
        $this->addTestStock('ALE', 'Common', 23, false, 60);
        $this->addTestStock('GIN', 'Preferred', 8, 0.02, 100);
        $this->addTestStock('JOE', 'Common', 13, false, 250);
    }

    protected function addTestStock($stockSymbol, $type, $lastDividend, $fixedDividend, $parValue) {
        /*
         * adds test stock for given params
         */
        $stock = array(
            'StockSymbol' => $stockSymbol,
            'Type' => $type,
            'LastDividend' => $lastDividend,
            'FixedDividend' => $fixedDividend,
            'ParValue' => $parValue,
        );
        $this->stockDatabase = $this->stocksModel->addToTable($this->stockDatabase, $stock);
    }

    protected function buildTestTrades($start = 'yesterday noon', $period = 300) {
        /*
         * mimic trades over period (in minutes) from start at minute intervals across all test stocks
         * default is over 5 hour period from noon yesterday
         */
        $start = strtotime($start);
        for ($minuteOffset = 0; $minuteOffset < $period; $minuteOffset++) {

            $time = $start + $minuteOffset * 60;
            foreach ($this->stockDatabase['Stocks'] as $stock) {

                $this->mimicTestTrade($time, $stock);
            }
        }
    }

    protected function mimicTestTrade($time, $stock) {
        /*
         * create test trade for given stock
         * introduces random buy / sell and values within tolerance on quantity and price 
         * and minimum trade quantity
         * 50/50 buy / sell split
         * max price set at 150% par 
         * - no attempted reflection on how market operates, but to aid verification of Table 2. Formula calculations
         */
        $q_random = rand(0, 100);
        $p_random = rand(0, 50);

        $quantityOfShares = intval(StockDb_config::$minimumTradeQuantity + $q_random * 100);
        $buySellIndicator = $q_random < 25 ? 'buy' : 'sell';
        $price = intval($stock['ParValue'] * (1 + ($q_random / 100)));

        $this->addTestTrade($time, $stock['StockSymbol'], $quantityOfShares, $buySellIndicator, $price);
    }

    protected function addTestTrade($time, $stockSymbol, $quantityOfShares, $buySellIndicator, $price) {
        /*
         * adds test trade for given params
         */
        $timestamp = date('Y-m-d H:i:s', $time);
        $trade = array(
            'StockSymbol' => $stockSymbol,
            'Timestamp' => $timestamp,
            'QuantityOfShares' => $quantityOfShares,
            'BuySellIndicator' => $buySellIndicator,
            'Price' => $price,
        );
        $this->stockDatabase = $this->tradesModel->addToTable($this->stockDatabase, $trade);
    }

}
