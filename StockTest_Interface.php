<?php

/*
 * Author : Kevin Richardson
 * September 2016
 */

include_once 'StockDbInterface.php';

class StockTest_Interface {

    protected $stockDbInterface = false;

    public function __construct() {

        $this->stockDbInterface = new StockDbInterface();
    }

    public function test_getStocks() {
        /*
         * test get all & individual stocks
         */
        $stocks = $this->stockDbInterface->getStocks();
        echo '<p>All stock :</p>';
        $this->reportArrays($stocks);

        $stocks = $this->stockDbInterface->getStocks(array('StockSymbol' => 'TEA'));
        echo '<p>TEA stock :</p>';
        $this->reportArrays($stocks);

        $stocks = $this->stockDbInterface->getStocks(array('StockSymbol' => 'POP'));
        echo '<p>POP stock :</p>';
        $this->reportArrays($stocks);

        $stocks = $this->stockDbInterface->getStocks(array('StockSymbol' => 'ALE'));
        echo '<p>ALE stock :</p>';
        $this->reportArrays($stocks);

        $stocks = $this->stockDbInterface->getStocks(array('StockSymbol' => 'GIN'));
        echo '<p>GIN stock :</p>';
        $this->reportArrays($stocks);

        $stocks = $this->stockDbInterface->getStocks(array('StockSymbol' => 'JOE'));
        echo '<p>JOE stock :</p>';
        $this->reportArrays($stocks);
    }

    public function test_getStocks_errors() {
        /*
         * test get stock error handlers
         */
        $params = array();      // empty params
        $this->test_getStock_error($params, 'empty params');

        $params = array('StockSymbol' => 'xxx');     // invalid StockSymbol
        $this->test_getStock_error($params, 'invalid StockSymbol');
    }

    protected function test_getStock_error($params, $testLabel) {

        try {
            echo '<p>TEST CASE : ' . $testLabel . '</p>';
            $stocks = $this->stockDbInterface->getStocks($params);
            echo '<p>*** ERROR - test failed</p><hr>';
        } catch (Exception $error) {
            echo 'SUCCESS : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_getTrades_all() {

        $trades = $this->stockDbInterface->getTrades();

        $this->reportArrays($trades);
    }

    public function test_getTrades_StockSymbol($stockSymbol = 'TEA') {

        $params = array('StockSymbol' => $stockSymbol);
        $testLabel = 'All Trades for stock ' . $stockSymbol;
        $this->test_getTrade_success($params, $testLabel);
    }

    public function test_getTrades_Timestamp($type = 'low', $low = false, $high = false) {

        switch ($type) {
            case'low':
                $low = $low ? $low : date('Y-m-d H:i:s', strtotime('yesterday noon +285 minutes'));
                $params = array('Timestamp_low' => $low);
                $testLabel = 'All Trades on or after ' . $low;
                break;
            case'high':
                $high = $high ? $high : date('Y-m-d H:i:s', strtotime('yesterday noon +13 minutes'));
                $params = array('Timestamp_high' => $high);
                $testLabel = 'All Trades before ' . $high;
                break;
            case'range':
                $low = $low ? $low : date('Y-m-d H:i:s', strtotime('yesterday noon +124 minutes'));
                $high = $high = $high ? $high : date('Y-m-d H:i:s', strtotime('yesterday noon +133 minutes'));
                $params = array('Timestamp_low' => $low, 'Timestamp_high' => $high);
                $testLabel = 'All Trades on or after ' . $low . ' and before ' . $high;
                break;
        }
        $this->test_getTrade_success($params, $testLabel);
    }

    public function test_getTrades_BuySell($indicator = 'buy') {

        $params = array('BuySellIndicator' => $indicator);
        $testLabel = 'All ' . $indicator . ' Trades';
        $this->test_getTrade_success($params, $testLabel);
    }

    public function test_getTrades_combined($type = 1, $stockSymbol = 'TEA', $indicator = 'buy', $low = false, $high = false) {
        /*
         * not exhaustive, but a good stab
         * all trades listed after for comparison - this would normally be generated independently and compared automatically
         */
        $low = $low ? $low : date('Y-m-d H:i:s', strtotime('yesterday noon +245 minutes'));
        $high = $high ? $high : date('Y-m-d H:i:s', strtotime('yesterday noon +293 minutes'));

        switch ($type) {
            case 1:
                $params = array('StockSymbol' => $stockSymbol, 'Timestamp_low' => $low);
                $testLabel = 'All Trades for stock ' . $stockSymbol . ' on or after ' . $low;
                break;
            case 2:
                $params = array('StockSymbol' => $stockSymbol, 'Timestamp_high' => $high);
                $testLabel = 'All Trades for stock ' . $stockSymbol . ' before ' . $high;
                break;
            case 3:
                $params = array('StockSymbol' => $stockSymbol, 'Timestamp_low' => $low, 'Timestamp_high' => $high);
                $testLabel = 'All Trades for stock ' . $stockSymbol . ' on or after ' . $low . ' and before ' . $high;
                break;
            case 4:
                $params = array('StockSymbol' => $stockSymbol, 'BuySellIndicator' => $indicator);
                $testLabel = 'All ' . $indicator . ' Trades for stock ' . $stockSymbol;
                break;
            case 5:
                $params = array('Timestamp_low' => $low, 'BuySellIndicator' => $indicator);
                $testLabel = 'All ' . $indicator . ' Trades on or after ' . $low;
                break;
            case 6:
                $params = array('Timestamp_low' => $low, 'Timestamp_high' => $high, 'BuySellIndicator' => $indicator);
                $testLabel = 'All ' . $indicator . ' Trades on or after ' . $low . ' and before ' . $high;
                break;
            case 7:
                $params = array('StockSymbol' => $stockSymbol, 'Timestamp_low' => $low, 'Timestamp_high' => $high, 'BuySellIndicator' => $indicator);
                $testLabel = 'All ' . $indicator . ' Trades for stock ' . $stockSymbol . ' on or after ' . $low . ' and before ' . $high;
                break;
        }
        $this->test_getTrade_success($params, $testLabel);
        echo '**************************  END   ***<br><br><br>';
        echo '**************************  COMPARE   ***<br><hr><br>Compare with all trades...<br>';
        $this->test_getTrades_all();
    }

    protected function test_getTrade_success($params, $testLabel) {

        echo '<p>' . $testLabel . ' : </p>';
        try {
            $trades = $this->stockDbInterface->getTrades($params);
            $this->reportArrays($trades);
            echo '<hr>';
        } catch (Exception $error) {
            echo '<br>**** FAILED : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_getTrades_errors() {

        // test get trade error handlers
        // empty params
        $params = array();
        $this->test_Trade_error($params, 'empty params');

        // invalid StockSymbol
        $params = array('StockSymbol' => 'xxx');
        $this->test_Trade_error($params, 'invalid StockSymbol');

        // invalid Timestamp_low
        $params = array('Timestamp_low' => 'xxx');
        $this->test_Trade_error($params, 'invalid Timestamp_low');

        // invalid Timestamp_high
        $params = array('Timestamp_high' => 'xxx');
        $this->test_Trade_error($params, 'invalid Timestamp_high');

        // invalid BuySellIndicator
        $params = array('BuySellIndicator' => 'xxx');
        $this->test_Trade_error($params, 'invalid BuySellIndicator');

        // invalid combined
        $params = array('StockSymbol' => 'xxx', 'Timestamp_low' => 'xxx', 'Timestamp_high' => 'xxx', 'BuySellIndicator' => 'xxx', 'Price' => -0.01);
        $this->test_Trade_error($params, 'invalid combined');
    }

    protected function test_Trade_error($params, $testLabel) {

        try {
            echo '<p>TEST CASE : ' . $testLabel . '</p>';
            $stocks = $this->stockDbInterface->getTrades($params);
            echo '<p>*** ERROR - test failed</p><hr>';
        } catch (Exception $error) {
            echo 'SUCCESS : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_recordTrade() {

        try {
            $trade = array('StockSymbol' => 'TEA', 'QuantityOfShares' => 1111, 'BuySellIndicator' => 'buy', 'Price' => 111,);
            $this->stockDbInterface->recordTrade($trade);
            $trade = array('StockSymbol' => 'ALE', 'QuantityOfShares' => 2222, 'BuySellIndicator' => 'sell', 'Price' => 222,);
            $this->stockDbInterface->recordTrade($trade);
            $trade = array('StockSymbol' => 'GIN', 'QuantityOfShares' => 3333, 'BuySellIndicator' => 'buy', 'Price' => 333,);
            $this->stockDbInterface->recordTrade($trade);
            $trade = array('StockSymbol' => 'POP', 'QuantityOfShares' => 4444, 'BuySellIndicator' => 'sell', 'Price' => 444,);
            $this->stockDbInterface->recordTrade($trade);
            $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 5555, 'BuySellIndicator' => 'buy', 'Price' => 555,);
            $this->stockDbInterface->recordTrade($trade);
            $trade = array('StockSymbol' => 'POP', 'QuantityOfShares' => 6666, 'BuySellIndicator' => 'sell', 'Price' => 666,);
            $this->stockDbInterface->recordTrade($trade);
            $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 7777, 'BuySellIndicator' => 'sell', 'Price' => 777,);
            $this->stockDbInterface->recordTrade($trade);
            echo '<br>SUCCESS : <br>';
            $this->test_getTrades_Timestamp('low', date('Y-m-d H:i:s', strtotime('yesterday noon + 299 minutes')));
        } catch (Exception $error) {
            echo '<br>**** FAILED : ' . $error->getMessage() . '<hr>';
        }
    }

    public function test_recordTrade_errors() {

        $testLabel = 'No StockSymbol';
        $trade = array('QuantityOfShares' => 7777, 'BuySellIndicator' => 'sell', 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Invalid StockSymbol';
        $trade = array('StockSymbol' => '', 'QuantityOfShares' => 7777, 'BuySellIndicator' => 'sell', 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'No QuantityOfShares';
        $trade = array('StockSymbol' => 'JOE', 'BuySellIndicator' => 'sell', 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Invalid QuantityOfShares';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 111, 'BuySellIndicator' => 'sell', 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'No BuySellIndicator';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 7777, 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Invalid BuySellIndicator';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 7777, 'BuySellIndicator' => 'xxx', 'Price' => 777,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'No Price';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 7777, 'BuySellIndicator' => 'sell',);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Invalid Price';
        $trade = array('StockSymbol' => 'JOE', 'QuantityOfShares' => 7777, 'BuySellIndicator' => 'sell', 'Price' => -1,);
        $this->test_recordTrade_error($trade, $testLabel);

        $testLabel = 'Combined';
        $trade = array('StockSymbol' => 'JOE', 'BuySellIndicator' => 'xxx', 'Price' => 22,);
        $this->test_recordTrade_error($trade, $testLabel);

        $this->test_getTrades_Timestamp('low', date('Y-m-d H:i:s', strtotime('yesterday noon + 299 minutes')));
    }

    protected function test_recordTrade_error($trade, $testLabel) {

        try {
            echo '<p>TEST CASE : ' . $testLabel . '</p>';
            $this->stockDbInterface->recordTrade($trade);
            echo '<p>*** ERROR - test failed</p><hr>';
        } catch (Exception $error) {
            echo 'SUCCESS : ' . $error->getMessage() . '<hr>';
        }
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
