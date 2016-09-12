# McGregor-Boyall

/*
 * Author : Kevin Richardson
 * September 2016
 */

The solution to 'Assignment – Super Simple Stocks' is presented as four functional libraries and two test units which are described below:

Functional Libraries

The functional libraries are delivered in four layers:
StockService which exposes the assignment 'Requirements 1.a.(i). to 1.a.(iv) and 1.b.' as services
StockDbInterface which acts as interface between the service and data (psuedo database) layers
StockDbModels which realises the StockDbInterface methods, managing the handling of data with the psuedo database
StockDbTestData which creates the psuedo database - populating the psuedo table 'Stocks' with data from 'Table1. Sample data from the Global Beverage Corporation Exchange' and psuedo table 'Stocks' with randomly generated data



Library StockService
Exposes the assignment 'Requirements 1.a.(i). to 1.a.(iv) and 1.b.' as services
.

Public functions
All public functions will throw an Exception on an invalid request

function calculate_DividendYield
- Satisfies 'Requirement 1.a.i. calculate the dividend yield' for given stock

function calculate_Stock_PERatio
- Satisfies 'Requirement 1.a.ii. calculate the P/E Ratio' for given stock
- If no ticker price is passed in function call,  a 'time limited price' (function calculate_TimeLimitedStockPrice) is used in the calculation

function recordTrade
- Satisfies 'Requirement 1.a.iii. record a trade, with timestamp, quantity of shares, buy or sell indicator and price'
- Adds validated trade to pseudo database

function calculate_TimeLimitedStockPrice
- Satisfies 'Requirement 1.a.iv. Calculate Stock Price based on trades recorded in past 15 minutes'
- Selects all trades for given stock in given time period, then calculates the price based on 'Table 2. Formula : Stock Price'

function calculate_GBCEAllShareIndex
- Satisfies 'Requirement 1.b. Calculate the GBCE All Share Index using the geometric mean of prices for all stocks'
- Calculates a geometric mean price across all stocks for two specified time periods – the base 'relative to' period and the calculation reference period
- Returns ratio of these two mean prices indexed to a base of 1000

Protected functions (these are currently exposed as 'public' to aid testing of this class)

function calculate_PERatio

- Satisfies 'Table 2. Formula : P / E Ratio'
- Returns value based on passed values

function calculate_GeometricMean

- Satisfies 'Table 2. Formula : Geometric Mean'
- Returns value based on passed array of values
- Using the stated method can quickly produce an overflow, therefore the method has been re-worked as the arithmetic means of logarithms

function calculate_StockPrice
- Satisfies 'Table 2. Formula : Stock Price'
- Returns value based on passed array of values 

function getTimeLimitedTrades

- Fetches all trades for given stock in given time period

function calculate_AllStockPrices
- Calculates the geometric mean prices accumulated over all stocks for a given time period


-


Library StockDbInterface
Acts as interface between the service and data (psuedo database) layers
.


Public functions
All public functions will throw an Exception on an invalid request

function getStocks
- Fetches stocks from the pseudo database
- Filtering of data will be made as per the parameters passed to the function

function getTrades
- Fetches trades from the pseudo database
- Filtering of data will be made as per the parameters passed to the function

function recordTrade
- Adds given trade to the end of the 'Trades' table of the pseudo database
- Passed trade will be validated before being added


Protected functions

function getStockData
- Central management of the fetching of data from the pseudo database


-
Library StockDbModels
Realises the StockDbInterface methods, managing the handling of data with the psuedo database.
Under normal working circumstances, data would be held on a relational database. Under this assignment, data tables are held in memory. As such, the management of data is not based around normal database queries, but accessing data via arrays.

Comprised of four classes:
StockDb_config which holds static enumerations and constants associated with the database
StockDbModel base model that manages generic data-handling of the pseudo database
StockDbModel_Stocks extension of StockDbModel for managing the 'Stocks' table  of the pseudo database
StockDbModel_Trades extension of StockDbModel for managing the 'Trades' table  of the pseudo database

StockDb_config
Database configuration items referenced throughout

StockDbModel
Base pseudo database entity

Public functions
All public functions will throw an Exception on an invalid request where appropriate

function getTableName
- Returns model's table name

function getStructure
- Returns model's structure

function addToTable
- On successful validation, adds model to end of table

function find
- Will return all or a filtered subset of the table entries based on passed parameters
- If filtering required, filter parameters will be validated first

Protected functions

function validate_add
- Shell function to validate model before adding to table

function validate_find
- Shell function to validate filter parameters before filtering table

function find_filtered
- Creates list of filtered items to pass back to function find

function is_addToFiltered
- Shell function to verify if item meets filtering criteria

function validate_emptyParams
- Commonly used : throws error if empty parameters have been passed

function accumulateError
- Commonly used : accumulates string error message

StockDbModel_Stocks
Pseudo database entity specific to 'Stocks' table

Protected functions

function validate_add
- Validates model before adding to 'Stocks' table

function validate_find
- Validates filter parameters before filtering 'Stocks' table

function is_addToFiltered
- Verifies if 'Stocks' item meets filtering criteria

StockDbModel_Trades
Pseudo database entity specific to 'Trades' table

Protected functions

function validate_add
- Validates model before adding to 'Trades' table

function validate_find
- Validates filter parameters before filtering 'Trades' table

function is_addToFiltered
- Verifies if 'Trades' item meets filtering criteria


-




Library StockDbTestData
Creates the psuedo database with relevant data to enable testing at a higher level. 


Public functions


function getStockDatabase
- Returns the created test database


Protected functions


function buildTestData
- Builds all base test data – 'Stocks' and 'Trades' tables


functions buildTestStocks and addTestStock
- Builds all base 'Stocks' test data – based on 'Table1. Sample data from the Global Beverage Corporation Exchange'


functions buildTestTrades, mimicTestTrade and addTestTrade
- Builds all base 'Trades' test data
- Creates trades across all stock types at minute intervals over 5 hour period from noon yesterday
- Each trade has some randomness built in : quantity of shares, buy/sell indicator and price


-

Test Units
The test units have been written as system tests and – as far as possible with the pseudo database - as the framework for acceptance testing.

The unit StockTest_Interface is used to test functional library StockDbInterface and unit StockTest_Service to test StockService.

All functional tests in each unit are named as 'test_{fnName}' where fnName corresponds to the public function declared in the associated functional library.

The output of each test should be self-explanatory.

Under normal working conditions, it would be expected that independently verified test results would be provided for each calculation function – likely to be in csv (or equivalent) format. The test functions would be written to automatically verify that these expected results would be met and to highlight any anomalies encountered.

Any additional notes of interest for each of the test units are mentioned below.

Unit StockTest_Interface

functions test_{fnName}_errors
A number of these test functions are used to test the error handling capabilities of the corresponding function declared in the associated functional library.

function test_recordTrade
The test output shows all trades made from 16:59 yesterday – i.e. the last five trades made yesterday and the 7 additional added by this test.

function test_recordTrade_error
The test output shows all trades made from 16:59 yesterday – i.e. the last five trades made yesterday and no others as error handling tested successfully.


Unit StockTest_Service

The following functions have attempted to create some acceptance 'expected values'  in order to pass or fail the given test case. These expected have – because of their nature of  calculation – limited value in the testing process, but afford some illustration of how acceptance testing may be borne out:
function test_calculate_DividendYield
function test_calculate_TimeLimitedStockPrice
function test_calculate_PERatio
function test_calculate_Stock_PERatio 
function test_calculate_GeometricMean
function test_calculate_GBCEAllShareIndex

function test_calculate_GBCEAllShareIndex
- This function first builds on the 'Trades' table base test data by adding trades with price values that are on average, either 'up' or 'down' on base values
- Although not a clear indicator that the calculated index is correct, this gives and at-a-glance verification that the index is moving in the correct direction from the 1000 base

