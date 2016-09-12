<?php

/*
 * Author : Kevin Richardson
 * September 2016
 */
include_once 'StockDbInterface.php';

class StockService {
    /*
     * to accommodate the variable nature of test data held in memory and to allow successful testing of this class, 
     * $stockDbInterface is required to be public
     * see README.TXT > StockService, StockDbTestData, StockTest_Service for additional details
     * 
     * In the real world where the data is held on database, this would be protected
     */

    public $stockDbInterface = false;

    public function __construct() {

        $this->stockDbInterface = new StockDbInterface();
    }

    public function calculate_DividendYield($stock, $tickerPrice) {
        /*
         * To realise Requirement 1.a.i. calculate the dividend yield
         * and Table 2. Formula : Dividend Yield
         * 
         * Param $stock expected as defined by StockDbModel_Stocks::$structure
         * Param $tickerPrice as real value > 0
         */
        if (!isset($tickerPrice) || $tickerPrice < 0) {
            $error = '<p>Invalid tickerPrice in StockService->calculate_DividendYield()</p>';
            throw new Exception($error);
        }

        $error = array();
        if (!isset($stock['Type']) || !in_array($stock['Type'], StockDb_config::$stockTypes)) {
            $error[] = 'Type';
        }

        $yield = 0;
        switch ($stock['Type']) {
            case'Common':
                if (!isset($stock['LastDividend']) || $stock['LastDividend'] < 0) {
                    $error[] = 'LastDividend';
                }
                $yield = count($error) ? 0 : $stock['LastDividend'] / $tickerPrice;
                break;
            case'Preferred':
                if (!isset($stock['FixedDividend']) || $stock['FixedDividend'] < 0) {
                    $error[] = 'FixedDividend';
                }
                if (!isset($stock['ParValue']) || $stock['ParValue'] < 0) {
                    $error[] = 'ParValue';
                }
                $yield = count($error) ? 0 : $stock['FixedDividend'] * $stock['ParValue'] / $tickerPrice;
                break;
        }

        if (count($error)) {
            $message = '<p>Invalid Stocks elements [' . implode(', ', $error) . '] in StockService->calculate_DividendYield()</p>';
            throw new Exception($message);
        }

        return round($yield, 2);
    }

    public function calculate_Stock_PERatio($stockSymbol, $tickerPrice = false) {
        /*
         * To realise Requirement 1.a.ii. calculate the P/E Ratio
         * 
         * Param $stockSymbol expected as one of StockDb_config::$stockSymbols
         * Param $tickerPrice as real value > 0, or false if time limited stock price is to be used
         */
        $params = array(
            'StockSymbol' => $stockSymbol,
        );
        try {
            $stocks = $this->stockDbInterface->getStocks($params);
        } catch (Exception $e) {
            $error = '<p>Invalid request in StockService->calculate_Stock_PERatio()</p>' . $e->getMessage();
            throw new Exception($error);
        }

        $stock = $stocks[0];

        // if no ticker price provided, assume time limited stock price
        $tickerPrice = $tickerPrice ? $tickerPrice : $this->calculate_TimeLimitedStockPrice($stock['StockSymbol']);

        return $this->calculate_PERatio($stock['LastDividend'], $tickerPrice);
    }

    public function recordTrade($stockSymbol, $quantityOfShares, $buySellIndicator, $price, $timestamp = false) {
        /*
         * To realise Requirement 1.a.iii. record a trade, with timestamp, quantity of shares, buy or sell indicator and price
         * and Table 2. Formula : P/E Ratio
         * 
         * Param $stockSymbol expected as one of StockDb_config::$stockSymbols
         * Params $quantityOfShares, $buySellIndicator, $price as real value > 0
         * Param $timestamp expected in format of StockDb_config::$timestampFormat, or false if to be set in function
         */
        $trade = array(
            'StockSymbol' => $stockSymbol,
            'QuantityOfShares' => $quantityOfShares,
            'BuySellIndicator' => $buySellIndicator,
            'Price' => $price,
        );
        if ($timestamp) {  // if no timestamp provided, value will be assigned in StockDbModel_Trades->validate_add()
            $trade['Timestamp'] = $timestamp;
        }

        try {
            return $this->stockDbInterface->recordTrade($trade);
        } catch (Exception $e) {
            $error = '<p>Invalid request in StockService->recordTrade()</p>' . $e->getMessage();
            throw new Exception($error);
        }
    }

    public function calculate_TimeLimitedStockPrice($stockSymbol, $fromTime = false, $inLastMinutes = 15, $useLastTraded = false) {
        /*
         * To realise Requirement 1.a.iv. Calculate Stock Price based on trades recorded in past 15 minutes
         * 
         * Param $stockSymbol expected as one of StockDb_config::$stockSymbols
         * Param $fromTime expected in format of StockDb_config::$timestampFormat, or false if to be set as now
         * Param $inLastMinutes expected as integer value > 0, default is 15
         * Param $useLastTraded expected as boolean, default is false
         */
        try {  // get time limited trades
            $trades = $this->getTimeLimitedTrades($stockSymbol, $fromTime, $inLastMinutes, $useLastTraded);
        } catch (Exception $e) {
            $error = '<p>Invalid request in StockService->calculate_TimeLimitedStockPrice()</p>' . $e->getMessage();
            throw new Exception($error);
        }

        return $this->calculate_StockPrice($trades);
    }

    public function calculate_GBCEAllShareIndex($relativeTo = 'yesterday 12:15', $fromTime = false, $inLastMinutes = 15, $useLastTraded = false) {
        /*
         * To realise Requirement 1.b. Calculate the GBCE All Share Index using the geometric mean of prices for all stocks
         * 
         * Default basis is to calculate index relative to yesterday 12:15 (test data starts from yesterday 12:00)
         * and is based only on prices recorded on trades in last 15 minutes from relative (for base) and now (for index)
         * By altering function parameters appropriately, historical values / alternative relative base can be evaluated
         * 
         * Base index is 1000
         * 
         * Param $relativeTo expected as one of valid date / time string - if invalid will default to now
         * Param $fromTime expected in format of StockDb_config::$timestampFormat, or false if to be set as now
         * Param $inLastMinutes expected as integer value > 0, default is 15
         * Param $useLastTraded expected as boolean, default is false
         */
        $relativeTo = strtotime($relativeTo); // if invalid $relativeTo, default to now
        $relativeTo = $relativeTo ? date(StockDb_config::$timestampFormat, $relativeTo) : date(StockDb_config::$timestampFormat);

        try {
            $basePrice = $this->calculate_AllStockPrices($relativeTo, $inLastMinutes, $useLastTraded);
            $indexPrice = $this->calculate_AllStockPrices($fromTime, $inLastMinutes, $useLastTraded);
        } catch (Exception $e) {
            $error = '<p>Invalid request in StockService->calculate_GBCEAllShareIndex()</p>' . $e->getMessage();
            throw new Exception($error);
        }

        $index = $basePrice ? intval($indexPrice / $basePrice * 1000) : 0;

        return $index;
    }

    /*
     * The following functions exposed as public for testing purposes only
     */

    public function calculate_PERatio($lastDividend, $tickerPrice) {
        /*
         * To realise Table 2. Formula : P/E Ratio
         * 
         * Param $lastDividend expected as real value > 0
         * Param $tickerPrice expected as real value > 0
         */
        if (!$lastDividend) {
            return 0;
        }
        $peRatio = round($tickerPrice / $lastDividend, 2);

        return $peRatio;
    }

    public function calculate_GeometricMean($prices) {
        /*
         * To realise Table 2. Formula : Geometric Mean
         * 
         * Param $prices expected as array of price values
         * 
         * Using the stated method can quickly produce an overflow, therefore the method has been re-worked as the arithmetic means of logarithms
         */
        $priceCount = 0;
        $priceLog = 0;

        foreach ($prices as $price) {

            if ($price) {
                $priceCount++;
                $priceLog+=log($price);
            }
        }

        return $priceCount ? exp((1 / $priceCount) * $priceLog) : 0;
    }

    public function calculate_StockPrice($trades) {
        /*
         * To realise Table 2. Formula : Stock Price
         * 
         * Param $trades expected as array of trades as returned by $this->stockDbInterface->getTrades()
         */
        $totalValue = 0;
        $totalQuantity = 0;

        foreach ($trades as $trade) {

            $tradeValue = $trade['Price'] * $trade['QuantityOfShares'];
            $totalValue+=$tradeValue;
            $totalQuantity+=$trade['QuantityOfShares'];
        }

        return $totalQuantity ? intval($totalValue / $totalQuantity) : 0;
    }

    public function getTimeLimitedTrades($stockSymbol, $fromTime = false, $inLastMinutes = 15, $useLastTraded = false) {
        /*
         * Param $stockSymbol expected as one of StockDb_config::$stockSymbols
         * Param $fromTime expected in format of StockDb_config::$timestampFormat, or false if to be set as now
         * Param $inLastMinutes expected as integer value > 0, default is 15
         * Param $useLastTraded expected as boolean, default is false
         */
        $high = $fromTime ? $fromTime : date(StockDb_config::$timestampFormat);
        $low = date(StockDb_config::$timestampFormat, strtotime($inLastMinutes . ' minutes ago', strtotime($high)));
        $params = array(
            'StockSymbol' => $stockSymbol,
            'Timestamp_low' => $low,
            'Timestamp_high' => $high,
        );
        try {  // get time limited trades
            $trades = $this->stockDbInterface->getTrades($params);

            // if no trades in last minutes && $useLastTraded, use last previous trade, else none
            if ((!$trades || !count($trades)) && $useLastTraded) {
                unset($params['Timestamp_low']);
                $trades = $this->stockDbInterface->getTrades($params);
                
                if (isset($trades[0])){
                    $trades=array($trades[0]);
                }
            }
        } catch (Exception $e) {
            $error = '<p>Invalid request in StockService->getTimeLimitedTrades()</p>' . $e->getMessage();
            throw new Exception($error);
        }

        return $trades;
    }

    public function calculate_AllStockPrices($fromTime, $inLastMinutes, $useLastTraded) {
        /*
         * Param $fromTime expected in format of StockDb_config::$timestampFormat
         * Param $inLastMinutes expected as integer value > 0
         * Param $useLastTraded expected as boolean
         */
        $stockPrices = array();

        try {
            foreach (StockDb_config::$stockSymbols as $stockSymbol) {

               $stockPrices[] = $this->calculate_TimeLimitedStockPrice($stockSymbol, $fromTime, $inLastMinutes, $useLastTraded);
            }
        } catch (Exception $e) {
            $error = '<p>Invalid request in StockService->calculate_ShareIndexPrices()</p>' . $e->getMessage();
            throw new Exception($error);
        }

        return $this->calculate_GeometricMean($stockPrices);
    }

}
