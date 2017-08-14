<?php

/*
|--------------------------------------------------------------------------
| E-FRANCHISE PRO API ROUTES
|--------------------------------------------------------------------------
|
| This file contains API Routes for eFranchisePRO's server API.
|
*/

use Illuminate\Support\Facades\Redirect;

Route::get('/', function (){

    return Redirect::to('http://arreporting.net/');

});

Route::post('/user/signin', 'UserController@sign_in');

Route::get('/employees/import', 'EmployeeController@import');

Route::get('/sitestatus', function (){

   return response(['message' => 'Down'], 200);

});

//Route::get('/employees/delete', 'EmployeeController@delete_all');

//Route::get('/create', function (){
//
//    \App\UserGroup::Create(['name' => 'SysAdmin']);
//    \App\UserGroup::Create(['name' => 'OfficeAdmin']);
//    \App\UserGroup::Create(['name' => 'OfficeManager']);
//    \App\UserGroup::Create(['name' => 'UnitManager']);
//
//});
//
//Route::get('/create/admin', function (){
//    \App\User::Create(['name' => 'Admin', 'nick' => 'System Admin', 'email' => 'admin@test.com', 'password' => bcrypt('test'),
//        'user_pass' => 'test', 'user_group_id' => 1]);
//});

Route::get('test', function(){

    return cal_days_in_month(CAL_GREGORIAN, 8, 2017);

});

Route::group(['middleware' => ['CheckUnitExistance', 'jwt.auth']], function () {

/*
|--------------------------------------------------------------------------
| CRUD ROUTES
|--------------------------------------------------------------------------
*/



    Route::get('/token/validate', function (){

        return response(['success' => 'token_valid'], 200);

    });

    Route::get('/user/signout', 'UserController@sign_out');

    Route::group(['middleware' => ['AdminPermissions']], function () {

        Route::post('/user/signup', 'UserController@sign_up');

        Route::resource('usergroups', 'UserGroupController');

        Route::resource('users', 'UserController');

        Route::resource('units', 'UnitController');

    });

    Route::get('/user/units/{user_id}', 'UserController@get_units');

    Route::post('/user/units/add/{user_id}', 'UserController@add_units');

    Route::get('states', 'UnitController@get_states');

    Route::resource('dmas', 'DmaController');

    Route::resource('banks', 'BankController');

    Route::resource('expenses', 'ExpenseController');

    Route::resource('jobtitles', 'JobTitleController');

    Route::resource('suppliers', 'SupplierController');

/*
|--------------------------------------------------------------------------
| SALES MODULE ROUTES
|--------------------------------------------------------------------------
*/

    Route::post('/sales/{unit_id}', 'SalesController@store');  //Stores a sales sheet to unit

    Route::get('/sales/{unit_id}/{id}', 'SalesController@show'); //Returns a sales sheet for a unit

    Route::put('/sales/{unit_id}/{id}', 'SalesController@update'); //Updates sales sheets for a unit

    Route::delete('/sales/{unit_id}/{id}', 'SalesController@destroy'); //Deletes a sales sheet for a unit

//    Route::get('/sales/showall/{unit_id}', 'SalesController@index'); //Returns all sales sheets for a unit

    Route::get('/sales/unitsales/{unit_id}/{month}/{year}', 'SalesController@get_unit_sales'); //Returns all sales sheets for a unit

    Route::get('/sales/daily/{unit_id}/{month}/{year}', 'SalesController@get_daily_sales'); //Returns daily sales sheets for a unit

    Route::get('/sales/weekly/{unit_id}/{year}', 'SalesController@get_weekly_sales'); //Returns weekly sales sheets for a unit

    Route::get('/sales/monthly/{unit_id}/{year}', 'SalesController@get_monthly_sales'); //Returns monthly sales sheets for a unit

    Route::get('/sales/reports/monthly/{unit_id}/{month}/{year}', 'SalesController@get_monthly_sales_report'); //Returns monthly sales report for a unit

    Route::get('/sales/reports/weekly/{unit_id}/{year}/{range}', 'SalesController@get_weekly_sales_report'); //Returns weekly sales sheets for a unit

    Route::get('/sales/reports/costanalysis/monthly/{month}/{year}', 'SalesController@get_monthly_cost_analysis_report'); //Returns weekly sales sheets for a unit

    Route::get('/sales/reports/costanalysis/weekly/{year}/{week}', 'SalesController@get_weekly_cost_analysis_report'); //Returns weekly sales sheets for a unit

    Route::get('/sales/reports/missingsales/{month}/{year}', 'SalesController@get_missing_salessheets_report'); //Returns monthly missing sales sheets report for all unit

    Route::post('/sales/expense/{id}', 'SalesController@add_sales_expense'); //Stores a sales expense for a sales sheet

    Route::put('/sales/expense/{id}/{expense_id}', 'SalesController@update_sales_expense'); //Updates a sales expense for a sales sheet

    Route::delete('/sales/expense/{id}/{expense_id}', 'SalesController@destroy_sales_expense'); //Deletes a sales expense for a sales sheet

    Route::post('/sales/deposit/{sale_id}', 'SalesController@add_sales_deposit'); //Stores a sales deposit for a sales sheet

    Route::put('/sales/deposit/{id}/{deposit_id}', 'SalesController@update_sales_deposit'); //Updates a sales deposit for a sales sheet

    Route::delete('/sales/deposit/{id}/{deposit_id}', 'SalesController@destroy_sales_deposit'); //Deletes a sales deposit for a sales sheet

    Route::post('/sales/fooddelivery/{sale_id}', 'SalesController@add_sales_food_delivery'); //Stores a sales food delivery expense for a sales sheet

    Route::put('/sales/fooddelivery/{id}/{fooddelivery_id}', 'SalesController@update_sales_food_delivery'); //Updates a sales food delivery expense for a sales sheet

    Route::delete('/sales/fooddelivery/{id}/{fooddelivery_id}', 'SalesController@destroy_sales_food_delivery'); //Deletes a sales food delivery expense for a sales sheet

/*
|--------------------------------------------------------------------------
| EMPLOYEES MODULE ROUTES
|--------------------------------------------------------------------------
*/

    Route::post('/employees/{unit_id}', 'EmployeeController@store'); //Stores an employee to a unit

    Route::get('/employees/{unit_id}', 'EmployeeController@index'); //Returns all employees of a unit

    Route::get('/employees/{unit_id}/{id}', 'EmployeeController@show'); // Returns queried employee of a unit

    Route::put('/employees/{unit_id}/{id}', 'EmployeeController@update'); // Updates queried employee of a unit

/*
|--------------------------------------------------------------------------
| PAYROLL MODULE ROUTES
|--------------------------------------------------------------------------
*/

    Route::get('/payroll/run/{unit_id}/{year}/{period}', 'PayrollController@run_payroll');

    Route::post('/payroll/run/add/{unit_id}/{year}/{period}', 'PayrollController@enter_payroll');

    Route::put('/payroll/run/update/{unit_id}/{payroll_id}', 'PayrollController@update_payroll');

    Route::get('/payroll/stats/{unit_id}/{year}', 'PayrollController@payroll_stats');

    Route::get('/payroll/process/{unit_id}/{year}/{period}', 'PayrollController@run_process_payroll');

    Route::get('/payroll/stats/process/{unit_id}/{year}', 'PayrollController@process_payroll_stats');

    Route::put('/payroll/process/update', 'PayrollController@update_payroll_status');

    Route::delete('/payroll/run/delete/{payroll_id}', 'PayrollController@delete_payroll');

    Route::put('/payroll/update/{id}', 'PayrollController@update');

    Route::get('/payroll/generatefile/{unit_id}/{year}/{period}', 'PayrollController@generate_payroll_file');

    Route::get('/payroll/downloadfile/{unit_id}', 'PayrollController@download_payroll_file');

/*
|--------------------------------------------------------------------------
| MISC ROUTES
|--------------------------------------------------------------------------
*/

    Route::get('/weeks/list/{year}', 'DateController@get_weeks_in_year'); //Returns list of weeks in an year

    Route::get('/weeks/payroll/{year}', 'DateController@get_weeks_for_payroll'); //Returns list of weeks in an year

//    Route::get('/weeks/payroll/{year}', 'DateController@get_payroll_weeks'); //Returns list of weeks in an year

    Route::get('/weeks/list/{month}/{year}', 'DateController@get_weeks_in_month'); //Returns list of weeks in a month

});

