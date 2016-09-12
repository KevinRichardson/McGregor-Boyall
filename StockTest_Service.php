<?php

/*
 * Author : Kevin Richardson
 * September 2016
 */

include_once 'StockDbModels.php';
include_once 'StockService.php';

class StockTest_Service {

    protected $stockService = false;

    public function __construct() {

        $this->stockService = new StockService();
    }

    public function test_calculate_DividendYield() {

        $stocks = $this->stockService->stockDbInterface->getStocks();
        $tickerPrices = array(100, 150, 200, 250, 300);

        echo '<style>td,th{padding:5px;text-align:right;}</style><table>';
        echo '<thead><tr><th>Stock</th><th>Type</th><th>Last Dividend</th><th>Fixed Dividend</th><th>Par Value</th><th>Price</th><th>Calculated yield</th><th>Expected common</th><th>Expected Preferred</th><th>Result</th></tr></thead><tbody>';

        try {
            foreach ($stocks as $stock) {

                foreach ($tickerPrices as $tickerPrice) {

                    $yield = $this->stockService->calculate_DividendYield($stock, $tickerPrice);

                    $expectedCommon = round($stock['LastDividend'] / $tickerPrice, 2);
                    $expectedPreferred = round($stock['FixedDividend'] * $stock['ParValue'] / $tickerPrice, 2);

                    $success = $stock['Type'] == 'Common' ? $yield == $expectedCommon : $yield == $expectedPreferred;
                    $success = $success ? 'Passed' : '*** Failed';

                    echo '<tr><td>' . $stock['StockSymbol'] . '</td><td>' . $stock['Type'] . '</td><td>' . $stock['LastDividend'] . '</td><td>' . $stock['FixedDividend'] . '</td><td>' . $stock['ParValue'] . '</td><td>' . $tickerPrice . '</td><td>' . $yield . '</td><td>' . $expectedCommon . '</td><td>' . $expectedPreferred . '</td><td>' . $success . '</td></tr>';
                }
            }
        } catch (Exception $e) {

            echo '</tbody></table><br>*** ERROR : <br>' . $e->getMessage();
        }
        echo '</tbody></table>';
    }

    public function test_calculate_DividendYield_errors() {

        $stock = array(// invalid stock type
            'Type' => 'Common',
            'LastDividend' => 100,
            'FixedDividend' => 100,
            'ParValue' => 100,
        );
        $tickerPrice = -1;      // invalid ticker price
        $this->test_DividendYield_error('invalid ticker price', $stock, $tickerPrice);

        $stock = array(// invalid stock type
            'Type' => 'xxx',
            'LastDividend' => 100,
            'FixedDividend' => 100,
            'ParValue' => 100,
        );
        $tickerPrice = 100;
        $this->test_DividendYield_error('invalid stock type', $stock, $tickerPrice);

        $stock = array(// unset last dividend
            'Type' => 'Common',
            'FixedDividend' => 100,
            'ParValue' => 100,
        );
        $tickerPrice = 100;
        $this->test_DividendYield_error('unset last dividend', $stock, $tickerPrice);

        $stock = array(// invalid last dividend
            'Type' => 'Common',
            'LastDividend' => -1,
            'FixedDividend' => 100,
            'ParValue' => 100,
        );
        $tickerPrice = 100;
        $this->test_DividendYield_error('invalid last dividend', $stock, $tickerPrice);

        $stock = array(// unset fixed dividend
            'Type' => 'Preferred',
            'LastDividend' => 100,
            'ParValue' => 100,
        );
        $tickerPrice = 100;
        $this->test_DividendYield_error('unset fixed dividend', $stock, $tickerPrice);

        $stock = array(// invalid fixed dividend
            'Type' => 'Preferred',
            'LastDividend' => 100,
            'FixedDividend' => -1,
            'ParValue' => 100,
        );
        $tickerPrice = 100;
        $this->test_DividendYield_error('invalid fixed dividend', $stock, $tickerPrice);

        $stock = array(// unset par value
            'Type' => 'Preferred',
            'LastDividend' => 100,
            'FixedDividend' => 100,
        );
        $tickerPrice = 100;
        $this->test_DividendYield_error('unset par value', $stock, $tickerPrice);

        $stock = array(// invalid par value
            'Type' => 'Preferred',
            'LastDividend' => 100,
            'FixedDividend' => 100,
            'ParValue' => -1,
        );
        $tickerPrice = 100;
        $this->test_DividendYield_error('invalid par value', $stock, $tickerPrice);
    }

    protected function test_DividendYield_error($testLabel, $stock, $tickerPrice = false) {

        try {
            echo '<p>TEST CASE : ' . $testLabel . '</p>';
            $yield = $this->stockService->calculate_DividendYield($stock, $tickerPrice);
            echo '<p>*** ERROR - test failed</p><hr>';
        } catch (Exception $error) {
            echo 'SUCCESS : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_getTimeLimitedTrades() {

        $fromTime = date(StockDb_config::$timestampFormat);
        $relativeTo = date(StockDb_config::$timestampFormat, strtotime('yesterday 12:15'));

        try {
            $this->buildTestTrades();   // first 'top up' test data with trades to now with average prices different from base

            foreach (StockDb_config::$stockSymbols as $stockSymbol) {

                echo '<br><hr>' . $stockSymbol . ' Trades 15 minutes to ' . $fromTime;
                $trades = $this->stockService->getTimeLimitedTrades($stockSymbol, $fromTime);
                $this->reportArrays($trades);

                echo '<br><hr>' . $stockSymbol . ' Trades 15 minutes to ' . $relativeTo;
                $trades = $this->stockService->getTimeLimitedTrades($stockSymbol, $relativeTo);
                $this->reportArrays($trades);
            }
        } catch (Exception $e) {
            echo '<br>*** ERROR <br>' . $e->getMessage();
        }
    }

    public function test_calculate_TimeLimitedStockPrice() {

        $this->buildTestTrades();   // first set up test trades

        $fromTime = date(StockDb_config::$timestampFormat);
        $inLastMinutes = 15;
        $low = date(StockDb_config::$timestampFormat, strtotime($inLastMinutes . ' minutes ago', strtotime($fromTime)));

        echo '<table><thead><tr><th>Stock</th><th>Calculated price</th><th>Total period value</th><th>Total period quantity</th><th>Expected Price</th><th>Result</th></tr><thead><tbody>';
        foreach (StockDb_config::$stockSymbols as $stockSymbol) {

            $high = date(StockDb_config::$timestampFormat);  // set time frame
            $low = date(StockDb_config::$timestampFormat, strtotime('15 minutes ago', strtotime($high)));

            $stockPrice = $this->stockService->calculate_TimeLimitedStockPrice($stockSymbol, $fromTime, $inLastMinutes);

            $totalValue = 0;
            $totalQuantity = 0;
            $params = array(
                'StockSymbol' => $stockSymbol,
                'Timestamp_low' => $low,
                'Timestamp_high' => $fromTime,
            );
            $trades = $this->stockService->stockDbInterface->getTrades($params);

            foreach ($trades as $trade) {

                $tradeValue = $trade['Price'] * $trade['QuantityOfShares'];
                $totalValue+=$tradeValue;
                $totalQuantity+=$trade['QuantityOfShares'];
            }

            $expectedPrice = $totalQuantity ? intval($totalValue / $totalQuantity) : 0;
            $success = $expectedPrice == $stockPrice ? 'Passed' : '** Failed';

            echo '<tr><td>' . $stockSymbol . '</td><td>' . $stockPrice . '</td><td>' . $totalValue . '</td><td>' . $totalQuantity . '</td><td>' . $expectedPrice . '</td><td>' . $success . '</td></tr>';
        }
        echo '</tbody></table><br>*** Compare with trades: <br>';

        $trades = $this->stockService->stockDbInterface->getTrades();
        $this->reportArrays($trades);
    }

    public function test_calculate_PERatio() {

        $lastDividends = array(0, 100, 150, 200);
        $tickerPrices = array(111, 222, 333, 444);

        echo '<style>th,td{padding:5px;text-align:right;}</style><table><thead><th>Ticker price</th><th>Last dividend</th><th>Calculated</th><th>Expected</th><th>Result</th></thead><tbody>';
        foreach ($lastDividends as $lastDividend) {
            foreach ($tickerPrices as $tickerPrice) {

                $peRatio = $this->stockService->calculate_PERatio($lastDividend, $tickerPrice);
                $expectedRatio = $lastDividend ? round($tickerPrice / $lastDividend, 2) : 0;

                $success = $peRatio == $expectedRatio ? 'Passed' : '** Failed';

                echo '<tr><td>' . $tickerPrice . '</td><td>' . $lastDividend . '</td><td>' . $peRatio . '</td><td>' . $expectedRatio . '</td><td>' . $success . '</td></tr>';
            }
        }
        echo '</tbody></table>';
    }

    public function test_calculate_Stock_PERatio() {

        $this->buildTestTrades();
        $stocks = $this->stockService->stockDbInterface->getStocks();
        $tickerPrices = array(1, 100, false);

        echo '<style>td,th{padding:5px;text-align:right;}</style><table>';
        echo '<thead><tr><th>Stock</th><th>Price</th><th>Calculated ratio</th><th>Exp ratio</th><th>Result</th></tr></thead><tbody>';

        try {
            foreach ($stocks as $stock) {

                foreach ($tickerPrices as $tickerPrice) {

                    $ratio = $this->stockService->calculate_Stock_PERatio($stock['StockSymbol'], $tickerPrice);

                    if ($tickerPrice) {

                        $expectedRatio = $stock['LastDividend'] ? round($tickerPrice / $stock['LastDividend'], 2) : 0;
                    } else {

                        $tickerPrice = $this->stockService->calculate_TimeLimitedStockPrice($stock['StockSymbol']);
                        $expectedRatio = $stock['LastDividend'] ? round($tickerPrice / $stock['LastDividend'], 2) : 0;
                        $tickerPrice = 'time limited price used (' . $tickerPrice . ')';
                    }

                    $success = $expectedRatio == $ratio ? 'Passed' : '*** Failed';
                    echo '<tr><td>' . $stock['StockSymbol'] . '</td><td>' . $tickerPrice . '</td><td>' . $ratio . '</td><td>' . $expectedRatio . '</td><td>' . $success . '</td></tr>';
                }
            }
        } catch (Exception $e) {

            echo '</tbody></table><br>*** ERROR : <br>' . $e->getMessage();
        }
        echo '</tbody></table>';
    }

    public function test_calculate_Stock_PERatio_errors() {

        $stockSymbol = 'xxx';     // invalid stock symbol
        $this->test_Stock_PERatio_error('invalid stock symbol', $stockSymbol);
    }

    protected function test_Stock_PERatio_error($testLabel, $stockSymbol, $tickerPrice = false) {

        try {
            echo '<p>TEST CASE : ' . $testLabel . '</p>';
            $ratio = $this->stockService->calculate_Stock_PERatio($stockSymbol, $tickerPrice);
            echo '<p>*** ERROR - test failed</p><hr>';
        } catch (Exception $error) {
            echo 'SUCCESS : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_recordTrade() {

        try {
            $this->stockService->recordTrade('TEA', 1111, 'buy', 111);
            $this->stockService->recordTrade('ALE', 2222, 'buy', 222);
            $this->stockService->recordTrade('GIN', 3333, 'buy', 333);
            $this->stockService->recordTrade('POP', 4444, 'buy', 444);
            $this->stockService->recordTrade('JOE', 5555, 'buy', 555);
            $this->stockService->recordTrade('POP', 6666, 'buy', 666);
            $this->stockService->recordTrade('JOE', 7777, 'buy', 777);
            echo '<br>SUCCESS : <br>';

            $params = array(
                'Timestamp_low' => date('Y-m-d H:i:s', strtotime('yesterday noon + 299 minutes')),
            );
            $trades = $this->stockService->stockDbInterface->getTrades($params);
            $this->reportArrays($trades);
        } catch (Exception $error) {
            echo '<br>**** FAILED : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_recordTrade_errors() {

        $testLabel = 'Invalid StockSymbol';
        $trade = array('StockSymbol' => '', 'QuantityOfShares' => 7777, 'BuySellIndicator' => 'sell', 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Invalid QuantityOfShares';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 111, 'BuySellIndicator' => 'sell', 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Invalid BuySellIndicator';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 7777, 'BuySellIndicator' => 'xxx', 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Invalid Price';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 7777, 'BuySellIndicator' => 'sell', 'Price' => -1,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Combined';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => -2, 'BuySellIndicator' => 'xxx', 'Price' => 22,);
        $this->test_recordTrade_error($trade, $testLabel);

        $params = array(
            'Timestamp_low' => date('Y-m-d H:i:s', strtotime('yesterday noon + 299 minutes')),
        );
        $trades = $this->stockService->stockDbInterface->getTrades($params);
        $this->reportArrays($trades);
    }

    protected function test_recordTrade_error($trade, $testLabel) {

        try {
            echo '<p>TEST CASE : ' . $testLabel . '</p>';
            $this->stockService->recordTrade($trade['StockSymbol'], $trade['QuantityOfShares'], $trade['BuySellIndicator'], $trade['Price']);
            echo '<p>*** ERROR - test failed</p><hr>';
        } catch (Exception $error) {
            echo 'SUCCESS : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_calculate_GeometricMean() {

        try {
            $trades = $this->stockService->stockDbInterface->getTrades();

            $tradeCount = 0;
            $tradePriceLog = 0;
            $tradePriceTotal = 0;

            $prices = array();

            foreach ($trades as $trade) {

                if (isset($trade['Price']) && $trade['Price']) {
                    $tradeCount++;
                    $tradePriceLog+=log($trade['Price']);
                    $tradePriceTotal+=$trade['Price'];
                    $prices[] = $trade['Price'];
                }
            }

            $mean = $this->stockService->calculate_GeometricMean($prices);

            $expectedMean = $tradeCount ? exp((1 / $tradeCount) * $tradePriceLog) : 0;
            $arithmenticMean = $tradePriceTotal / $tradeCount;

            $success = $mean == $expectedMean ? 'Passed' : '** Failed';
            echo '<br>' . $success;
            echo '<br>Calculated : ' . $mean . '   Expected : ' . $expectedMean;
            echo '<br>Arithmentic Mean : ' . $arithmenticMean;
        } catch (Exception $error) {
            echo 'ERROR : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_calculate_GBCEAllShareIndex($priceChange = 'up') {

        try {
            $this->buildTestTrades($priceChange);   // first 'top up' test data with trades to now with average prices different from base
            $index = $this->stockService->calculate_GBCEAllShareIndex();

            $fromTime = date(StockDb_config::$timestampFormat);
            $relativeTo = date(StockDb_config::$timestampFormat, strtotime('yesterday 12:15'));

            $indexPrice = array();
            $basePrice = array();

            foreach (StockDb_config::$stockSymbols as $stockSymbol) {

                $trades = $this->stockService->getTimeLimitedTrades($stockSymbol, $fromTime);
                $indexPrice[] = $this->stockService->calculate_StockPrice($trades);

                $trades = $this->stockService->getTimeLimitedTrades($stockSymbol, $relativeTo);
                $basePrice[] = $this->stockService->calculate_StockPrice($trades);
            }
            $expectedIndex = intval($this->stockService->calculate_GeometricMean($indexPrice) / $this->stockService->calculate_GeometricMean($basePrice) * 1000);
        } catch (Exception $e) {
            echo '<br>*** ERROR <br>' . $e->getMessage();
        }

        $success = $index == $expectedIndex ? 'Passed' : '** Failed';
        echo '<br>' . $success;
        echo '<br>Calculated Index =  ' . $index . '   Expected Index = ' . $expectedIndex;
    }

    protected function pricesFromTrades($trades) {

        $prices = array();
        foreach ($trades as $trade) {
            $prices[] = $trade['Price'];
        }
        return $prices;
    }

    protected function buildTestTrades($priceChange = 'up', $start = '5 hours ago', $period = 300) {

        // mimic trades over period (in minutes) from start at minute intervals across all test stocks
        // default is over 5 hour period from 5 hours ago 
        $stocks = $this->stockService->stockDbInterface->getStocks();

        $start = strtotime($start);
        for ($minuteOffset = 0; $minuteOffset < $period; $minuteOffset++) {

            $time = $start + $minuteOffset * 60;
            foreach ($stocks as $stock) {

                $this->mimicTestTrade($time, $stock, $priceChange);
            }
        }
    }

    protected function mimicTestTrade($time, $stock, $priceChange = 'up') {
        /*
         * create test trade for given stock
         * introduces random buy / sell and values within tolerance on quantity and price 
         * and minimum trade quantity
         * 50/50 buy / sell split
         * max price set at 150% par  + / - 25% depending on $priceChange value
         * - no attempted reflection on how market operates, but to aid verification of Table 2. Formula calculations
         */

        $q_random = rand(0, 100);
        $p_random = rand(0, 50);

        $quantityOfShares = intval(StockDb_config::$minimumTradeQuantity + $q_random * 100);
        $buySellIndicator = $q_random < 25 ? 'buy' : 'sell';

        $multiplier = $priceChange == 'up' ? $q_random + 25 : $q_random - 25;
        $price = intval($stock['ParValue'] * (1 + ($multiplier / 100)));

        $timestamp = date('Y-m-d H:i:s', $time);
        $this->stockDatabase = $this->stockService->recordTrade($stock['StockSymbol'], $quantityOfShares, $buySellIndicator, $price, $timestamp);
    }

    protected function reportArrays($items) {

        if (!$items || !count($items)) {
            return '<br>Empty result set!<br>';
        }

        $thead = array('index');
        $keys = array_keys($items);
        foreach ($items[$keys[0]] as $key => $value) {

            $thead[] = $key;
        }

        $rows = array();
        $count = 0;

        foreach ($items as $item) {

            $row = array($count);
            foreach ($thead as $key) {

                if ($key != 'index') {
                    $row[] = isset($item[$key]) && $item[$key] ? $item[$key] : '';
                }
            }
            $rows[] = '<tr><td>' . implode('</td><td>', $row) . '</td></tr>';
            $count++;
        }

        $thead = '<thead><tr><th>' . implode('</th><th>', $thead) . '</th></tr></thead>';
        $table = '<table>' . $thead . '<tbody>' . implode('', $rows) . '</tbody></table>';

        $style = '<style>td,th{padding:5px;text-align:right;}</style>';

        echo $style . $table;
    }

}
