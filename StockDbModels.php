<?php

/*
 * Author : Kevin Richardson
 * September 2016
 */

class StockDb_config {

    //enumerations
    public static $stockSymbols = array('TEA', 'POP', 'ALE', 'GIN', 'JOE');
    public static $stockTypes = array('Common', 'Preferred');
    public static $buySellIndicators = array('buy', 'sell');
    //constants
    public static $minimumTradeQuantity = 1000;
    public static $minimumTradePrice = 0;
    public static $timestampFormat = 'Y-m-d H:i:s';

}

class StockDbModel {

    protected $tableName = '';
    protected $structure = array();

    public function __construct($params) {

        if (!isset($params['tableName']) || !$params['tableName']) {
            $e = new Exception('<p>No tableName in params, StockDbModel->__construct().</p>');
        }

        $this->tableName = $params['tableName'];
    }

    public function getTableName() {

        return $this->tableName;
    }

    public function getStructure() {

        return $this->structure;
    }

    public function addToTable($db, $model) {
        /*
         * add new model to end of table after validation
         * 
         * Param $db expected as array to be added to
         * Param $model expected as per model-specific structure (StockDbModel::$structure)
         */

        try {
            $model = $this->validate_add($model);
        } catch (Exception $ex) {
            $error = '<p>model not validated for table ' . $this->getTableName() . '->insert :</p>' . $ex->getMessage();
            throw new Exception($error);
        }
        $db[$this->getTableName()][] = $model;

        return $db;
    }

    public function find($db, $params = false) {
        /*
         * find all or filtered table rows
         * 
         * Param $db expected as array to be searched
         * Param $params expected as false or variant of model-specific search filter
         */

        if ($params === false) {  // full table required?            
            return $db[$this->tableName];  // yes - return full table
        }


        try {  // no - return filtered rows after validation of request
            $params = $this->validate_find($params);
        } catch (Exception $ex) {
            $error = '<p>params not valid for table ' . $this->getTableName() . '->find() :</p>' . $ex->getMessage();
            throw new Exception($error);
        }

        return $this->find_filtered($db, $params);
    }

    protected function validate_add($model) {
        /*
         * validate model before adding to table
         * 
         * Param $model expected as per model-specific structure (StockDbModel::$structure)
         */

        return $model;
    }

    protected function validate_find($params) {
        /*
         * validate find params before filtering
         * 
         * Param $params expected as variant of model-specific search filter
         */

        return $params;
    }

    protected function find_filtered($db, $params) {
        /*
         * find filtered table rows
         * 
         * Param $db expected as array to be searched
         * Param $params expected as false or variant of model-specific search filter
         */

        $filtered = array();

        foreach ($db[$this->tableName] as $item) {

            if ($this->is_addToFiltered($params, $item)) {
                $filtered[] = $item;
            }
        }

        return $filtered;
    }

    protected function is_addToFiltered($params, $item) {
        /*
         * does item meet filter params? 
         * specific to each model
         * 
         * Param $params expected as variant of model-specific search filter
         * Param $item expected as per model-specific structure (StockDbModel::$structure)
         */

        return false;
    }

    protected function validate_emptyParams($params, $error, $fnName) {
        /*
         * commonly used
         * 
         * Param $params expected as variant of model-specific search filter
         * Param $error expected as string
         * Param $fnName expected as string as name of calling function
         */

        if (!is_array($params) || !count($params)) {

            $error.='<p>Empty params, ' . $this->getTableName() . '->' . $fnName . '().</p>';

            throw new Exception($error);
        }
    }

    protected function accumulateError($error, $newError, $delimiter = ', ') {
        /*
         * commonly used
         * 
         * Param $error expected as string
         * Param $newError expected as string
         * Param $delimiter expected as string, default = ', '
         */

        $error.=$error ? $delimiter : '';
        $error.=$newError;

        return $error;
    }

}

class StockDbModel_Stocks extends StockDbModel {

    public function __construct() {

        $params = array(
            'tableName' => 'Stocks',
        );
        parent::__construct($params);

        $this->structure = array(
            'StockSymbol' => '',
            'Type' => '',
            'LastDividend' => '',
            'FixedDividend' => '',
            'ParValue' => '',
        );
    }

    protected function validate_add($model) {
        /*
         * Validate Stocks model before adding to table
         * Must have valid StockSymbol, Type, LastDividend, FixedDividend, ParValue
         * 
         * Param $model expected as per model-specific structure (StockDbModel_Stocks::$structure)
         */

        $error = '';

        if (!isset($model['StockSymbol']) || !in_array($model['StockSymbol'], StockDb_config::$stockSymbols)) {
            $error = $this->accumulateError($error, 'StockSymbol');
        }

        if (!isset($model['Type']) || !in_array($model['Type'], StockDb_config::$stockTypes)) {
            $error = $this->accumulateError($error, 'Type');
        }

        if (!isset($model['LastDividend'])) {
            $model['LastDividend'] = 0;
        } elseif ($model['LastDividend'] < 0) {
            $error = $this->accumulateError($error, 'LastDividend');
        }

        if (!isset($model['FixedDividend'])) {
            $model['FixedDividend'] = false;
        } elseif ($model['FixedDividend'] < 0) {
            $error = $this->accumulateError($error, 'FixedDividend');
        }

        if (!isset($model['ParValue']) || $model['ParValue'] < 0) {
            $error = $this->accumulateError($error, 'ParValue');
        }

        if ($error) {
            $message = '<p>Invalid Stocks elements [' . $error . ']</p>';
            throw new Exception($message);
        }

        return $model;
    }

    protected function validate_find($params) {
        /*
         * Validate Stock find params before filtering
         * Must not be empty and have valid StockSymbol
         * 
         * Param $params expected as per model-specific search filter
         */

        $error = '';

        $this->validate_emptyParams($params, $error, 'validate_find'); // empty?

        if (!isset($params['StockSymbol'])) {    // single stock required?
            $error = $this->accumulateError($error, 'StockSymbol');
        } elseif (!in_array($params['StockSymbol'], StockDb_config::$stockSymbols)) {  // valid stock?
            $error = $this->accumulateError($error, 'StockSymbol');
        }

        if ($error) {
            $message = '<p>Invalid find params [' . $error . ']</p>';
            throw new Exception($message);
        }

        return $params;
    }

    protected function is_addToFiltered($params, $stock) {
        /*
         * does stock item meet filter params? 
         * 
         * Param $params expected as variant of model-specific search filter
         * Param $item expected as per model-specific structure (StockDbModel_Stocks::$structure)
         */

        $addToFiltered = true;

        $addToFiltered = $addToFiltered && (!isset($params['StockSymbol']) || $stock['StockSymbol'] == $params['StockSymbol']);

        return $addToFiltered;
    }

}

class StockDbModel_Trades extends StockDbModel {

    public function __construct() {

        $params = array(
            'tableName' => 'Trades',
        );
        parent::__construct($params);

        $this->structure = array(
            'StockSymbol' => '',
            'Timestamp' => '',
            'QuantityOfShares' => 0,
            'BuySellIndicator' => '',
            'Price' => 0,
        );
    }

    public function validate_add($model) {
        /*
         * Validate Trades model before adding to table
         * Must have valid StockSymbol, QuantityOfShares, BuySellIndicator, Price, Timestamp
         * Set Timestamp if not set
         * 
         * Param $model expected as per model-specific structure (StockDbModel_Trades::$structure)
         */

        $error = '';

        if (!isset($model['StockSymbol']) || !in_array($model['StockSymbol'], StockDb_config::$stockSymbols)) {
            $error = $this->accumulateError($error, 'StockSymbol');
        }

        if (!isset($model['QuantityOfShares']) || $model['QuantityOfShares'] < StockDb_config::$minimumTradeQuantity) {
            $error = $this->accumulateError($error, 'QuantityOfShares');
        }

        if (!isset($model['BuySellIndicator']) || !in_array($model['BuySellIndicator'], StockDb_config::$buySellIndicators)) {
            $error = $this->accumulateError($error, 'BuySellIndicator');
        }

        if (!isset($model['Price']) || $model['Price'] < StockDb_config::$minimumTradePrice) {
            $priceError = isset($model['Price']) ? 'Price (< ' . StockDb_config::$minimumTradePrice . ')' : 'Price';
            $error = $this->accumulateError($error, $priceError);
        }

        if (isset($model['Timestamp'])) {
            $time = strtotime($model['Timestamp']);         // need valid timestamp 
            $timestampError = !$time || $time > time();     // that is not future-dated
            $error = $timestampError ? $this->accumulateError($error, 'Timestamp') : $error;
        }

        if ($error) {
            $message = '<p>Invalid Trades elements [' . $error . ']</p>';
            throw new Exception($message);
        }

        // set timestamp
        if (!isset($model['Timestamp'])) {
            $model['Timestamp'] = date(StockDb_config::$timestampFormat);
        }

        return $model;
    }

    public function validate_find($params) {
        /*
         * Validate Trades find params before filtering
         * Must not be empty and have at least one of valid StockSymbol, Timestamp_low, Timestamp_high, BuySellIndicator
         * 
         * Param $params expected as per model-specific search filter
         */

        $error = '';

        $this->validate_emptyParams($params, $error, 'validate_find');

        if (isset($params['StockSymbol']) && !in_array($params['StockSymbol'], StockDb_config::$stockSymbols)) {
            $error = $this->accumulateError($error, 'StockSymbol');
        }

        if (isset($params['Timestamp_low']) && !strtotime($params['Timestamp_low'])) {
            $error = $this->accumulateError($error, 'Timestamp_low');
        }

        if (isset($params['Timestamp_high']) && !strtotime($params['Timestamp_high'])) {
            $error = $this->accumulateError($error, 'Timestamp_high');
        }

        if (isset($params['BuySellIndicator']) && !in_array($params['BuySellIndicator'], StockDb_config::$buySellIndicators)) {
            $error = $this->accumulateError($error, 'BuySellIndicator');
        }

        if ($error) {
            $message = '<p>Invalid find params [' . $error . ']</p>';
            throw new Exception($message);
        }

        return $params;
    }

    protected function is_addToFiltered($params, $trade) {
        /*
         * does trade item meet filter params? 
         * when filtering on time range, is valid when Timestamp_low <= Timestamp < Timestamp_high
         * 
         * Param $params expected as variant of model-specific search filter
         * Param $item expected as per model-specific structure (StockDbModel_Trades::$structure)
         */

        $addToFiltered = true;

        $addToFiltered = $addToFiltered && (!isset($params['StockSymbol']) || $trade['StockSymbol'] == $params['StockSymbol']);
        $addToFiltered = $addToFiltered && (!isset($params['Timestamp_low']) || strtotime($trade['Timestamp']) >= strtotime($params['Timestamp_low']));
        $addToFiltered = $addToFiltered && (!isset($params['Timestamp_high']) || strtotime($trade['Timestamp']) < strtotime($params['Timestamp_high']));
        $addToFiltered = $addToFiltered && (!isset($params['BuySellIndicator']) || $trade['BuySellIndicator'] == $params['BuySellIndicator']);

        return $addToFiltered;
    }

}
