<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSaleRequest;
use App\Sale;
use App\SalesDeposit;
use App\SalesExpense;
use App\SalesFoodDelivery;
use App\Unit;
use Carbon\Carbon;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\DateController;
use App\Http\Controllers\PayrollController;

class SalesController extends Controller
{

    public function __construct(DateController $date_controller, PayrollController $payroll_controller){

        $this->date_controller = $date_controller;

        $this->payroll_controller = $payroll_controller;

    }

    /**
     * Returns sales sheets of a specific unit.
     *
     * @param  $unit_id
     * @return \Illuminate\Http\Response
     */
    public function index($unit_id)
    {

        return Unit::findOrFail($unit_id)->sales;

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  $request
     * @param  $unit_id
     * @return \Illuminate\Http\Response
     */
    public function store(CreateSaleRequest $request, $unit_id)
    {
        $date = Carbon::parse($request->input('date'));

        $check = Sale::whereRaw('extract(day from created_at) =' . $date->day .
            ' AND extract(month from created_at) =' . $date->month .
            ' AND extract(year from created_at) =' . $date->year .
            ' AND unit_id =' . $unit_id )->first();

        if (!empty($check))

            return response()->json(['error' => 'Sale already exists.'], 409);

        $sale = Unit::findOrFail($unit_id)->sales()->save(new Sale($request->except('date')));

        if ($sale->id){

            $sale->update(['created_at' => $date->format('Y-m-d H:i:s')]);

            $data = $this->show($unit_id, $sale->id);

            return response()->json(['success' => 'Sales created successfully.', 'data' => $data], 201);

        }

        return 'failed';

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $unit_id
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($unit_id, $id)
    {

        $sale = Unit::findOrFail($unit_id)->sales()->whereId($id)->first();

        $sale = $this->get_sale_detail($sale);

        $sale['prev_cash_over'] = $this->calculate_previous_cashover($sale);

        $sale['total_cash_in_hand'] = $sale['cash_in_hand'] + $sale['prev_cash_over'];

        return $sale;

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $unit_id
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $unit_id, $id)
    {

        Unit::findOrFail($unit_id)->sales()->whereId($id)->update($request->except('token'));

        return 'Updated';
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $unit_id
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($unit_id, $id)
    {

        Unit::findOrFail($unit_id)->sales()->whereId($id)->delete();

        return 'Deleted';

    }

    /**
     * Adds expense to a sale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
*/
    public function add_sales_expense(Request $request, $id){

        $sale = Sale::findOrFail($id);

        $sale->expenses()->save(new SalesExpense($request->all()));

        return 'Expense added';

    }

    /**
     * Updated expense of a sale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  int  $expense_id
     * @return \Illuminate\Http\Response
     */
    public function update_sales_expense(Request $request, $id, $expense_id){

        Sale::findOrFail($id)->expenses()->whereId($expense_id)->update($request->except('token'));

        return 'Expense Updated';

    }

    /**
     * Updated expense of a sale.
     *
     * @param  int  $id
     * @param  int  $expense_id
     * @return \Illuminate\Http\Response
     */
    public function destroy_sales_expense($id, $expense_id){

        $sale = Sale::findOrFail($id)->expenses()->whereId($expense_id)->delete();

        return 'Expense deleted';

    }

    /**
     * Adds deposit to a sale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function add_sales_deposit(Request $request, $id){

        $sale = Sale::findOrFail($id);

        $sale->deposits()->save(new SalesDeposit($request->all()));

        return 'Deposit added';

    }

    /**
     * Updates deposit to a sale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  int  $deposit_id
     * @return \Illuminate\Http\Response
     */
    public function update_sales_deposit(Request $request, $id, $deposit_id){

        $sale = Sale::findOrFail($id)->deposits()->whereId($deposit_id)->update($request->except('token'));

        return 'Deposit updated';

    }

    /**
     * Deletes deposit to a sale.
     *
     * @param  int  $id
     * @param  int  $deposit_id
     * @return \Illuminate\Http\Response
     */
    public function destroy_sales_deposit($id, $deposit_id){

        $sale = Sale::findOrFail($id)->deposits()->whereId($deposit_id)->delete();

        return 'Deposit deleted';

    }

    /**
     * Adds food delivery to a sale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function add_sales_food_delivery(Request $request, $id){

        $sale = Sale::findOrFail($id);

        $sale->sales_food_delivery()->save(new SalesFoodDelivery($request->all()));

        return 'Food delivery added';

    }

    /**
     * Adds food delivery to a sale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  int  $fooddelivery_id
     * @return \Illuminate\Http\Response
     */
    public function update_sales_food_delivery(Request $request, $id, $fooddelivery_id){

        Sale::findOrFail($id)->sales_food_delivery()->whereId($fooddelivery_id)->update($request->except('token'));

        return 'Food delivery updated';

    }

    /**
     * Adds food delivery to a sale.
     *
     * @param  int  $id
     * @param  int  $fooddelivery_id
     * @return \Illuminate\Http\Response
     */
    public function destroy_sales_food_delivery($id, $fooddelivery_id){

        Sale::findOrFail($id)->sales_food_delivery()->whereId($fooddelivery_id)->delete();

        return 'Food delivery deteted';

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  int  $unit_id
     * @param  int  $month
     * @param  int  $year
     * @return array $sales
     */
    public function get_unit_sales($unit_id, $month, $year){

        $unit_sales = array();

        $date = Carbon::createFromDate($year, $month, 1);

        $prev_date = Carbon::createFromDate($year-1, $month, 1);

        $q_month = $date->format('F');

        $daily_sales = $this->get_daily_sales($unit_id, $month, $year);

        $daily_prev_sales = $this->get_daily_sales($unit_id, $month, $year-1);

        $unit_sales['date']['month'] = $q_month;

        $unit_sales['date']['year'] = $year;

        $unit_sales['daily_sales'] = $this->generate_daily_sales_comparison($daily_sales, $daily_prev_sales, $month, $year);

        $weekly_sales = $this->get_weekly_sales($unit_id, $year);

        $weekly_prev_sales = $this->get_weekly_sales($unit_id, $year-1);

        $unit_sales['weekly_sales'] =  $this->generate_weekly_sales_comparison($weekly_sales, $weekly_prev_sales, $month, $year);

        $monthly_sales = $this->get_monthly_sales($unit_id, $year);

        $monthly_prev_sales = $this->get_monthly_sales($unit_id, $year-1);

        $unit_sales['monthly_sales'] = $this->generate_monthly_sales_comparison($monthly_sales, $monthly_prev_sales, $month, $year);

        $unit_sales['totals'] = $this->calculate_sale_totals($unit_sales['daily_sales'], $unit_sales['weekly_sales'], $unit_sales['monthly_sales']);

        return $unit_sales;

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  array  $sales
     * @param  array  $prev_sales
     * @param  int  $month
     * @param  int  $year
     * @return array $sales_comparison
     */
    public function generate_daily_sales_comparison($sales, $prev_sales, $month, $year){

        $re_sales = $re_prev_sales = $sales_comparison = array();

        $now = Carbon::now();

        $curr_year = $now->year;

        $curr_month = $now->month;

        if ($month == $curr_month && $year == $curr_year)

            $prev_sales = array_slice($prev_sales, 0, count($sales));

        foreach($sales as $date => $sale){

            $generated = array();

            $day = Carbon::parse($date)->format('l');

            $generated['date'] = $date;

            $generated['sale_data'] = $sale;

            $generated['day'] = $day;

            $re_sales[] = $generated;

        }

        foreach($prev_sales as $date => $sale){

            $generated = array();

            $day = Carbon::parse($date)->format('l');

            $generated['prev_date'] = $date;

            $generated['prev_sale_data'] = $sale;

            $generated['prev_day'] = $day;

            $re_prev_sales[] = $generated;

        }

        $sales_comparison = array_replace_recursive($re_sales, $re_prev_sales);

        return $this->calculate_daily_difference($sales_comparison);

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  array  $sales
     * @param  array  $prev_sales
     * @param  int  $month
     * @param  int  $year
     * @return array $sales_comparison
     */
    public function generate_weekly_sales_comparison($sales, $prev_sales, $month, $year){

        $re_sales = $re_prev_sales = $sales_comparison = array();

        $now = Carbon::now();

        $curr_month = $now->month;

        $curr_year = $now->year;

        if ($month <= $curr_month && $year == $curr_year)

            $prev_sales = array_slice($prev_sales, 0, count($sales));

        foreach($sales as $week => $sale){

            $generated = array();

            $date = Carbon::parse(explode('-', $week)[1]);

            $generated['week'] = $week;

            $generated['month_num'] = $date->month;

            $generated['year'] = $date->year;

            $generated['net_sales'] = $sale['net_sales'];

            $generated['missing'] = $sale['missing'];

            $re_sales[] = $generated;

        }

        foreach($prev_sales as $week => $sale){

            $generated = array();

            $generated['prev_week'] = $week;

            $generated['prev_net_sales'] = $sale['net_sales'];

            $generated['prev_missing'] = $sale['missing'];

            $re_prev_sales[] = $generated;

        }

        $sales_comparison = array_replace_recursive($re_sales, $re_prev_sales);

        return $this->calculate_weekly_difference($sales_comparison);

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  array  $sales
     * @param  array  $prev_sales
     * @param  int  $month
     * @param  int  $year
     * @return array $sales_comparison
     */
    public function generate_monthly_sales_comparison($sales, $prev_sales, $month, $year){

        $re_sales = $re_prev_sales = $sales_comparison = array();

        $now = Carbon::now();

        $curr_month = $now->month;

        $curr_year = $now->year;

        if ($month <= $curr_month && $year == $curr_year)

            $prev_sales = array_slice($prev_sales, 0, count($sales));

        foreach($sales as $key_month => $sale){

            $generated = array();

            $date = Carbon::parse($key_month);

            $generated['month'] = $key_month;

            $generated['month_num'] = $date->month;

            $generated['year'] = $date->year;

            $generated['net_sales'] = $sale['net_sales'];

            $generated['missing'] = $sale['missing'];

            $re_sales[] = $generated;

        }

        foreach($prev_sales as $key_month => $sale){

            $generated = array();

            $generated['prev_month'] = $key_month;

            $generated['prev_net_sales'] = $sale['net_sales'];

            $generated['prev_missing'] = $sale['missing'];

            $re_prev_sales[] = $generated;

        }

        $sales_comparison = array_replace_recursive($re_sales, $re_prev_sales);

        return $this->calculate_weekly_difference($sales_comparison);

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  array  $sales_data
     * @return array $sales_comparison
     */
    public function calculate_daily_difference($sales_data){

        $sales_comparison = array();

        foreach($sales_data as $data){

            if (!array_key_exists('sale_data', $data) || !array_key_exists('prev_sale_data', $data)
                || ($data['sale_data'] == 'missing' && $data['prev_sale_data'] == 'missing')){

                $data['difference'] = 'null';

                $sales_comparison[] = $data;

                continue;

            }

            $net_sales = ($data['sale_data'] == 'missing') ? 0 : $data['sale_data']['net_sales'];

            $prev_net_sales = ($data['prev_sale_data'] == 'missing') ? 0 : $data['prev_sale_data']['net_sales'];

            $diff_amount = $net_sales - $prev_net_sales;

            $data['difference'] = abs($diff_amount);

            if ($data['sale_data'] === 'missing'){

                $data['diff_status'] = 'down';

                $data['diff_percent'] = 100;

            }

            elseif ($data['prev_sale_data'] === 'missing'){

                $data['diff_status'] = 'up';

                $data['diff_percent'] = 100;

            }

            else{

                $data['diff_status'] = ($diff_amount < 0) ? 'down' : 'up';

                $data['diff_percent'] = round( (abs($diff_amount) / $net_sales) * 100, 4, PHP_ROUND_HALF_DOWN);

            }

            $sales_comparison[] = $data;

        }

        return $sales_comparison;

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  array  $daily_sales
     * @param array $weekly_sales
     * @param array $monthly_sales
     * @return array $sales_comparison
     */
    public function calculate_sale_totals($daily_sales, $weekly_sales, $monthly_sales){

        $totals = array();

        $daily_totals = $weekly_totals = $monthly_totals = 0;

        $gross_sales = $tax = $net_sales = $credit_sales = $cash = $expenses = $deposit_cash = $deposit_bank = $cash_in_hand = $total_cash_in_hand = 0;

        $daily_prev_totals = $weekly_prev_totals = $monthly_prev_totals = 0;

        $daily_difference = $weekly_difference = $montly_difference = 0;

        $daily_difference_percent = $weekly_difference_percent = $montly_difference_percent = 0;

        foreach($daily_sales as $sale){

            $check = (array_key_exists('sale_data', $sale) && $sale['sale_data'] !== 'missing') ? true : false;

            $daily_totals += ($check) ? $sale['sale_data']['net_sales'] : 0;

            $daily_prev_totals += (array_key_exists('prev_sale_data', $sale) && $sale['prev_sale_data'] !== 'missing') ? $sale['prev_sale_data']['net_sales'] : 0;

            $gross_sales += ($check) ? $sale['sale_data']['gross_sales'] : 0;

            $tax += ($check) ? $sale['sale_data']['tax'] : 0;

            $net_sales += ($check) ? $sale['sale_data']['net_sales'] : 0;

            $credit_sales += ($check) ? $sale['sale_data']['credit_sales'] : 0;

            $cash += ($check) ? $sale['sale_data']['cash'] : 0;

            $expenses += ($check) ? $sale['sale_data']['sale_expenses'] : 0;

            $deposit_cash += ($check) ? $sale['sale_data']['deposit_cash'] : 0;

            $deposit_bank += ($check) ? $sale['sale_data']['sale_deposits'] : 0;

            $cash_in_hand += ($check) ? $sale['sale_data']['cash_in_hand'] : 0;

            $total_cash_in_hand += ($check) ? $sale['sale_data']['cash_in_hand'] : 0;

        }

        $daily_difference = $daily_totals - $daily_prev_totals;

        if ($daily_difference < 0)

            $daily_difference_status = 'down';

        else

            $daily_difference_status = 'up';

        $daily_difference_percent = ($daily_totals !== 0 && $daily_prev_totals !== 0) ? (abs($daily_difference) / $daily_prev_totals) * 100 : 100;

        $totals['daily']['totals'] = abs($daily_totals);

        $totals['daily']['prev_totals'] = abs($daily_prev_totals);

        $totals['daily']['difference'] = abs($daily_difference);

        $totals['daily']['percent'] = $daily_difference_percent;

        $totals['daily']['status'] = $daily_difference_status;

        $totals['daily']['details']['gross_sales'] = $gross_sales;

        $totals['daily']['details']['tax'] = $tax;

        $totals['daily']['details']['net_sales'] = $net_sales;

        $totals['daily']['details']['credit_sales'] = $credit_sales;

        $totals['daily']['details']['cash'] = $cash;

        $totals['daily']['details']['expenses'] = $expenses;

        $totals['daily']['details']['deposit_cash'] = $deposit_cash;

        $totals['daily']['details']['deposit_bank'] = $deposit_bank;

        $totals['daily']['details']['cash_in_hand'] = $cash_in_hand;

        $totals['daily']['details']['total_cash_in_hand'] = $total_cash_in_hand;

        foreach($weekly_sales as $sale){

            $weekly_totals += (array_key_exists('net_sales', $sale)) ? $sale['net_sales'] : 0;

            $weekly_prev_totals += (array_key_exists('prev_net_sales', $sale)) ? $sale['prev_net_sales'] : 0;

        }

        $weekly_difference = $weekly_totals - $weekly_prev_totals;

        if ($weekly_difference < 0)

            $weekly_difference_status = 'down';

        else

            $weekly_difference_status = 'up';

        $weekly_difference_percent = ($weekly_totals !== 0 && $weekly_prev_totals !== 0) ? (abs($weekly_difference) / $weekly_prev_totals) * 100 : 100;

        $totals['weekly']['totals'] = abs($weekly_totals);

        $totals['weekly']['prev_totals'] = abs($weekly_prev_totals);

        $totals['weekly']['difference'] = abs($weekly_difference);

        $totals['weekly']['percent'] = $weekly_difference_percent;

        $totals['weekly']['status'] = $weekly_difference_status;

        foreach($monthly_sales as $sale){

            $monthly_totals += $sale['net_sales'];

            $monthly_prev_totals += $sale['prev_net_sales'];

        }

        $monthly_difference = $monthly_totals - $monthly_prev_totals;

        if ($monthly_difference < 0)

            $monthly_difference_status = 'down';

        else

            $monthly_difference_status = 'up';

        $monthly_difference_percent = ($monthly_totals !== 0 && $monthly_prev_totals !== 0) ? (abs($monthly_difference) / $monthly_prev_totals) * 100 : 100;

        $totals['monthly']['totals'] = abs($monthly_totals);

        $totals['monthly']['prev_totals'] = abs($monthly_prev_totals);

        $totals['monthly']['difference'] = abs($monthly_difference);

        $totals['monthly']['percent'] = $monthly_difference_percent;

        $totals['monthly']['status'] = $monthly_difference_status;


        return $totals;

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  array  $sales_data
     * @return array $sales_comparison
     */
    public function calculate_weekly_difference($sales_data){

        $sales_comparison = array();

        foreach($sales_data as $data){

            if (!array_key_exists('net_sales', $data) || !array_key_exists('prev_net_sales', $data)
                || ($data['net_sales'] == 0 && $data['prev_net_sales'] == 0)){

                $data['difference'] = 'null';

                $sales_comparison[] = $data;

                continue;

            }

            $net_sales = ($data['net_sales'] == 0) ? 0 : $data['net_sales'];

            $prev_net_sales = ($data['prev_net_sales'] == 0) ? 0 : $data['prev_net_sales'];

            $diff_amount = $net_sales - $prev_net_sales;

            $data['difference'] = abs($diff_amount);

            if ($data['net_sales'] === 0){

                $data['diff_status'] = 'down';

                $data['diff_percent'] = 100;

            }

            elseif ($data['prev_net_sales'] === 0){

                $data['diff_status'] = 'up';

                $data['diff_percent'] = 100;

            }

            else{

                $data['diff_status'] = ($diff_amount < 0) ? 'down' : 'up';

                $data['diff_percent'] = round( (abs($diff_amount) / $net_sales) * 100, 4, PHP_ROUND_HALF_DOWN);

            }

            $sales_comparison[] = $data;

        }

        return $sales_comparison;

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  int  $unit_id
     * @param  int  $month
     * @param  int  $year
     * @return array $sales
     */
    public function get_daily_sales($unit_id, $month, $year){

        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $now = Carbon::now();

        $curr_year = $now->year;

        $curr_month = $now->month;

        $today = $now->day;

        if ($year > $curr_year || ($year == $curr_year && $month > $curr_month))

            return 'Invalid entry';

        $flag = ($year == $curr_year && $month == $curr_month) ? $today : $days;

        $sales = array();

        for($i = 1; $i <= $flag; $i++){

            $date = Carbon::createFromDate($year, $month, $i)->toFormattedDateString();

            $sale = Sale::whereRaw('extract(day from created_at) =' . $i .
                ' AND extract(month from created_at) =' . $month .
                ' AND extract(year from created_at) =' . $year .
                ' AND unit_id =' . $unit_id )->first();

            $sale = $this->get_sale_detail($sale);

            $sales[$date] = $sale;

        }

        return $sales;

    }

    /**
     * Get sales against all the weeks in an year.
     *
     * @param  int  $unit_id
     * @param  int  $year
     * @return array $sales
     */
    public function get_weekly_sales($unit_id, $year){

        $sales = array();

        $now = Carbon::now();

        $curr_year = $now->year;

        if ($year > $curr_year)

            return 'Invalid entry';

        $weeks = $this->date_controller->get_weeks_in_year($year);

        foreach($weeks as $week){

            $exp_week = explode('-', $week);

            $start = Carbon::parse($exp_week[0]);

            $rev_start = clone $start;

            $missing = 0;

            $net_sales = 0;

            for ($k=1; $k <= 7; $k++){

                if ($k !== 1)

                    $rev_start->addDay();

                $sale = Sale::whereRaw('extract(day from created_at) = ' . $rev_start->day .
                    ' AND extract(month from created_at) = ' . $rev_start->month .
                    ' AND extract(year from created_at) = ' . $rev_start->year .
                    ' AND unit_id = ' . $unit_id )->first();

                $sale = $this->get_sale_detail($sale);

                if ($sale === 'missing')

                    $missing++;

                else{

                    $net_sales += (int)$sale['net_sales'];

                }
            }

            $sales[$week]['net_sales'] = $net_sales;

            $sales[$week]['missing'] = $missing;

        }

        return $sales;

    }

    /**
     * Get sales against all the months in an year.
     *
     * @param  int  $unit_id
     * @param  int  $year
     * @return array $sales
     */
    public function get_monthly_sales($unit_id, $year){

        $sales = array();

        $now = Carbon::now();

        $curr_month = $now->month;

        $curr_year = $now->year;

        if ($year > $curr_year)

            return 'Invalid entry';

        $flag = ($year == $curr_year) ? $curr_month : 12;

        for($i = 1; $i <= $flag; $i++ ){

            $daily_sales = $this->get_daily_sales($unit_id, $i, $year);

            $missing = 0;

            $net_sales = 0;

            foreach($daily_sales as $sale){

                if($sale !== 'missing'){

                    $net_sales += (int)$sale['net_sales'];

                }

                else{

                    $missing++;

                }

            }

            $month = date_format(date_create($year. "-" . $i . "-01"),"F, Y");

            $sales[$month]['net_sales'] = $net_sales;

            $sales[$month]['missing'] = $missing;

        }

        return $sales;

    }

    /**
     * Get sales against all the days in a month.
     *
     * @param  int  $unit_id
     * @param  int  $month
     * @param  int  $year
     * @return array $sales
     */
    public function get_unit_sales_reports($unit_id, $month, $year){

        $unit_sales = array();

        $date = Carbon::createFromDate($year, $month, 1);

        $q_month = $date->format('F');

        $daily_sales = $this->get_daily_sales($unit_id, $month, $year);

        $daily_prev_sales = $this->get_daily_sales($unit_id, $month, $year-1);

        $unit_sales['date']['month'] = $q_month;

        $unit_sales['date']['year'] = $year;

        $unit_sales['daily_sales'] = $this->generate_daily_sales_comparison($daily_sales, $daily_prev_sales, $month, $year);

        $weekly_sales = $this->get_weekly_sales($unit_id, $year);

        $weekly_prev_sales = $this->get_weekly_sales($unit_id, $year-1);

        $unit_sales['weekly_sales'] =  $this->generate_weekly_sales_comparison($weekly_sales, $weekly_prev_sales, $month, $year);

        $monthly_sales = $this->get_monthly_sales($unit_id, $year);

        $monthly_prev_sales = $this->get_monthly_sales($unit_id, $year-1);

        $unit_sales['monthly_sales'] = $this->generate_monthly_sales_comparison($monthly_sales, $monthly_prev_sales, $month, $year);

        $unit_sales['totals'] = $this->calculate_sale_totals($unit_sales['daily_sales'], $unit_sales['weekly_sales'], $unit_sales['monthly_sales']);

        return $unit_sales;

    }

    /**
     * Get sales against all the weeks in an year.
     *
     * @param  int  $unit_id
     * @param  int  $month
     * @param  int  $year
     * @return array $sales_report
     */
    public function get_monthly_sales_report($unit_id, $month, $year){

        $date = Carbon::createFromDate($year, $month, 1);

        $from = $date->firstOfMonth()->toFormattedDateString();

        $to = $date->lastOfMonth()->toFormattedDateString();

        $sales = $this->get_daily_sales($unit_id, $month, $year);

        $payrolls = $this->calculate_payroll_expense_monthly($unit_id, $month, $year);

        $sales_report = $this->generate_sales_report($sales);

        $sales_report['payrolls'] = $payrolls;

        $sales_report['food_payroll_expense'] = $sales_report['net_food_expenses'] + $sales_report['payrolls']['payroll_expense'];

        $sales_report['food_payroll_expense_percent'] = ($sales_report['sales']['gross_sales'] !== 0) ?
            round(($sales_report['food_payroll_expense'] / $sales_report['sales']['gross_sales']) * 100, 2, PHP_ROUND_HALF_DOWN) : 0;

        $sales_report['date'] = $from . ' to ' . $to;

        $sales_report['payrolls']['payroll_expense_percent'] = ($sales_report['sales']['gross_sales'] !== 0) ?
            round(($sales_report['payrolls']['payroll_expense'] / $sales_report['sales']['gross_sales'] ) * 100, 2, PHP_ROUND_HALF_DOWN)  : 0;

        return $sales_report;

    }

    /**
     * Get sales against all the weeks in an year.
     *
     * @param  int  $unit_id
     * @param  int  $year
     * @param  int  $range
     * @return array $sales_report
     */
    public function get_weekly_sales_report($unit_id, $year, $range){

        $week = $range;

        $range = explode('>', $range);

        $start = Carbon::parse($range[0]);

        $from = clone $start;

        $to = Carbon::parse($range[1]);

        $sales = array();

        for($i = 1; $i <= 7; $i++){

            if ($i !== 1)

                $start->addDay();

            $sale = Sale::whereRaw('extract(day from created_at) =' . $start->day .
                ' AND extract(month from created_at) =' . $start->month .
                ' AND extract(year from created_at) =' . $start->year .
                ' AND unit_id =' . $unit_id )->first();

            $date = $start->toFormattedDateString();

            $sale = $this->get_sale_detail($sale);

            $sales[$date] = $sale;

        }

        $payrolls = $this->calculate_payroll_expense_weekly($unit_id, $year, $week);

        $sales_report = $this->generate_sales_report($sales);

        $sales_report['payrolls'] = $payrolls;

        $sales_report['food_payroll_expense'] = $sales_report['net_food_expenses'] + $sales_report['payrolls']['payroll_expense'];

        $sales_report['food_payroll_expense_percent'] = ($sales_report['sales']['gross_sales'] !== 0) ?
            round(($sales_report['food_payroll_expense'] / $sales_report['sales']['gross_sales']) * 100, 2, PHP_ROUND_HALF_DOWN) : 0;

        $sales_report['date'] = $from->toFormattedDateString() . ' to ' . $to->toFormattedDateString();

        $sales_report['payrolls']['payroll_expense_percent'] = ($sales_report['sales']['gross_sales'] !== 0) ?
            round(($sales_report['payrolls']['payroll_expense'] / $sales_report['sales']['gross_sales'] ) * 100, 2, PHP_ROUND_HALF_DOWN)  : 0;

        return $sales_report;

    }

    /**
     * Generates sales report against provided sales array.
     *
     * @param  array  $sales
     * @return array $sales_report
     */
    protected function generate_sales_report($sales){

        $missing = array();

        $gross_sales = $tax = $net_sales = $credit = $cash = $transactions = 0;

        $voids = $voids_amount = $voids_percent = 0;

        $refunds = $refunds_amount = $refunds_percent = 0;

        $discounts = $discounts_amount = $discounts_percent = 0;

        $expenses = $deposits = $food_purchase =  array();

        $net_expenses = $net_food_expenses = $net_deposits = 0;

        foreach($sales as $day => $sale){

            if ($sale == 'missing')

                $missing[] = $day;

            else{

                $gross_sales += $sale['gross_sales'];

                $tax += $sale['tax'];

                $credit += $sale['credit_sales'];

                $transactions += $sale['transactions'];

                $voids += $sale['voids'];

                $voids_amount += $sale['voids_amount'];

                $refunds += $sale['refunds'];

                $refunds_amount += $sale['refunds_amount'];

                $discounts += $sale['discounts'];

                $discounts_amount += $sale['discounts_amount'];

                if (sizeof($sale->expenses))

                    $expenses[] = $sale->expenses;


                $net_expenses += $this->calculate_expense($sale);

                if (sizeof($sale->deposits))

                    $deposits[] = $sale->deposits;

                $net_deposits += $this->calculate_deposits($sale);

                if (sizeof($sale->sales_food_delivery))

                    $food_purchase[] = $sale->sales_food_delivery;

                $net_food_expenses += $this->calculate_food_expense($sale);

            }

        }

        $net_sales = $gross_sales - $tax;

        $cash = $gross_sales - $credit;

        $voids_percent = ($gross_sales == 0) ? 0 : round(($voids_amount / $gross_sales) * 100, 4, PHP_ROUND_HALF_DOWN);

        $refunds_percent = ($gross_sales == 0) ? 0 :  round(($refunds_amount / $gross_sales) * 100, 4, PHP_ROUND_HALF_DOWN);

        $discounts_percent = ($gross_sales == 0) ? 0 :  round(($discounts_amount / $gross_sales) * 100, 4, PHP_ROUND_HALF_DOWN);

        $sales_report['sales'] = array(

            'gross_sales' => $gross_sales,

            'tax' => $tax,

            'net_sales' => $net_sales,

            'credit_sales' => $credit,

            'cash' => $cash,

            'transactions' => $transactions,

            'voids' => $voids,

            'voids_amount' => $voids_amount,

            'voids_percent' => $voids_percent,

            'refunds' => $refunds,

            'refunds_amount' => $refunds_amount,

            'refunds_percent' => $refunds_percent,

            'discounts' => $discounts,

            'discounts_amount' => $discounts_amount,

            'discounts_percent' => $discounts_percent

        );

        $sales_report['expenses'] = $sales_report['deposits'] = $sales_report['food_purchase'] = array();

        foreach($expenses as $exp){

            foreach($exp as $e){

                $sale_date = Carbon::parse(Sale::findOrFail($e->sale_id)->created_at);

                $e['date'] = $sale_date->toFormattedDateString();

                $e['day'] = $sale_date->format('l');

                $sales_report['expenses'][] = $e;

            }

        }

        foreach($deposits as $dep){

            foreach($dep as $d){

                $sale_date = Carbon::parse(Sale::findOrFail($d->sale_id)->created_at);

                $d['date'] = $sale_date->toFormattedDateString();

                $d['day'] = $sale_date->format('l');

                $sales_report['deposits'][] = $d;

            }

        }

        foreach($food_purchase as $food){

            foreach($food as $f){

                $sale_date = Carbon::parse(Sale::findOrFail($f->sale_id)->created_at);

                $f['date'] = $sale_date->toFormattedDateString();

                $f['day'] = $sale_date->format('l');

                $sales_report['food_purchase'][] = $f;

            }

        }

        $sales_report['net_expenses'] = $net_expenses;

        $sales_report['net_expenses_percent'] = ($gross_sales !== 0) ? ($net_expenses / $gross_sales) * 100 : 0;

        $sales_report['net_deposits'] = $net_deposits;

        $sales_report['net_food_expenses'] = $net_food_expenses;

        $sales_report['net_food_expenses_percent'] = ($gross_sales !== 0) ? round(($net_food_expenses / $gross_sales), 4, PHP_ROUND_HALF_DOWN) * 100 : 0;

        $sales_report['cash_available'] = $sales_report['sales']['cash'] - $sales_report['net_expenses'];

        $sales_report['cash_available_after_deposit'] = $sales_report['cash_available'] - $sales_report['net_deposits'];

        $sales_report['missing'] = $missing;

        return $sales_report;

    }

    /**
     * Get sale deposits against a sale.
     *
     * @param  int  $month
     * @param  int  $year
     * @return array $expense
     */
    public function get_monthly_cost_analysis_report($month, $year){

        $now = Carbon::now();

        if ($year > $now->year)

            return 'Invalid entry';

        $units = Unit::orderBy('name', 'ASC')->get();

        $cost_reports = array();

        foreach($units as $unit){

            $sales_report = $this->get_monthly_sales_report($unit->id, $month, $year);

            $cost_report['net_sales'] = $sales_report['sales']['net_sales'];

            $cost_report['food_cost'] = $sales_report['net_food_expenses'];

            $cost_report['payroll_cost'] = $sales_report['payrolls']['payroll_expense'];

            $cost_report['food_payroll_cost'] = $sales_report['food_payroll_expense'];

            $cost_report['food_cost_percent'] = ($cost_report['food_cost'] == 0 || $cost_report['net_sales'] == 0) ? 0 :

                round( ($cost_report['food_cost'] / $cost_report['net_sales']) * 100, 4, PHP_ROUND_HALF_DOWN);

            $cost_report['payroll_cost_percent'] = ($cost_report['payroll_cost'] == 0 || $cost_report['net_sales'] == 0) ? 0 :

                round( ($cost_report['payroll_cost'] / $cost_report['net_sales']) * 100, 4, PHP_ROUND_HALF_DOWN);

            $cost_report['foodpayroll_cost_percent'] = ($cost_report['food_payroll_cost'] == 0 || $cost_report['net_sales'] == 0) ? 0 :

                round( ($cost_report['food_payroll_cost'] / $cost_report['net_sales']) * 100, 4, PHP_ROUND_HALF_DOWN);

            $cost_reports[$unit->name] = $cost_report;

            $cost_reports[$unit->name]['missing_sales'] = count($sales_report['missing']);

        }

        $date = Carbon::createFromDate($year, $month, 1);

        $from = $date->startOfMonth()->toFormattedDateString();

        $to = $date->endOfMonth()->toFormattedDateString();

        $cost_analysis_report['date'] = $from . ' to ' . $to;

        $cost_analysis_report['data'] = $cost_reports;

        return $cost_analysis_report;

    }

    /**
     * Get sale deposits against a sale.
     *
     * @param  int  $year
     * @param  int  $week
     * @return array $expense
     */
    public function get_weekly_cost_analysis_report($year, $week){

        $now = Carbon::now();

        if ($year > $now->year)

            return 'Invalid entry';

        $weeks = $this->date_controller->get_weeks_in_year($year);

        if (!array_key_exists($week, $weeks))

            return 'Invalid entry';

        $units = Unit::orderBy('name', 'ASC')->get();

        $cost_reports = array();

        foreach($units as $unit){

            $sales_report = $this->get_weekly_sales_report($unit->id, $year, $week);

            $cost_report['net_sales'] = $sales_report['sales']['net_sales'];

            $cost_report['food_cost'] = $sales_report['net_food_expenses'];

            $cost_report['payroll_cost'] = $sales_report['payrolls']['payroll_expense'];

            $cost_report['food_payroll_cost'] = $sales_report['food_payroll_expense'];

            $cost_report['food_cost_percent'] = ($cost_report['food_cost'] == 0 || $cost_report['net_sales'] == 0) ? 0 :

                round( ($cost_report['food_cost'] / $cost_report['net_sales']) * 100, 4, PHP_ROUND_HALF_DOWN);

            $cost_report['payroll_cost_percent'] = ($cost_report['payroll_cost'] == 0 || $cost_report['net_sales'] == 0) ? 0 :

                round( ($cost_report['payroll_cost'] / $cost_report['net_sales']) * 100, 4, PHP_ROUND_HALF_DOWN);

            $cost_report['foodpayroll_cost_percent'] = ($cost_report['food_payroll_cost'] == 0 || $cost_report['net_sales'] == 0) ? 0 :

                round( ($cost_report['food_payroll_cost'] / $cost_report['net_sales']) * 100, 4, PHP_ROUND_HALF_DOWN);

            $cost_reports[$unit->name] = $cost_report;

            $cost_reports[$unit->name]['missing_sales'] = count($sales_report['missing']);

        }

        $week = explode('>', $week);

        $from = Carbon::parse($week[0])->toFormattedDateString();

        $to = Carbon::parse($week[1])->toFormattedDateString();

        $date = $from . ' to ' . $to;

        $cost_analysis_report['date'] = $date;

        $cost_analysis_report['data'] = $cost_reports;

        return $cost_analysis_report;

    }

    /**
     * Get missing sales sheets in a month,
     *
     * @param  int  $month
     * @param  int  $year
     * @return array $missing_sales
     */
    public function get_missing_salessheets_report($month, $year){

        $units = Unit::orderBy('name', 'ASC')->get();

        $date = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $from = $date->startOfMonth()->toFormattedDateString();

        $to = $date->endOfMonth()->toFormattedDateString();

        $missing_sales = array();

        foreach($units as $unit){

            $unit_missing_sales = array();

            $sales = $this->get_daily_sales($unit->id, $month, $year);

            foreach($sales as $date => $sale){

                if ($sale == 'missing')

                    $unit_missing_sales[] = $date;

            }

            if (!empty($unit_missing_sales))

                $missing_sales[$unit->name] = $unit_missing_sales;

        }

        $missing_sales_report['data'] = $missing_sales;

        $missing_sales_report['date'] = $from . ' to ' . $to;

        return $missing_sales_report;

    }

    /**
     * Get a single sale's detailed info.
     *
     * @param  int  $sale
     * @return array $sale_detail
     */
    public function get_sale_detail($sale){

        if (!empty($sale)){

            $date = Carbon::parse($sale->created_at);

            $pdt = clone $date;

            $ndt = clone $date;

            $next_dt = Carbon::parse($ndt->addDay());

            $next = Sale::whereRaw('extract(day from created_at) =' . $next_dt->day .
                ' AND extract(month from created_at) =' . $next_dt->month .
                ' AND extract(year from created_at) =' . $next_dt->year .
                ' AND unit_id =' . $sale->unit_id )->first();

            $sale['next'] = (!empty($next)) ? $next->id : 'missing';

            $prev_dt = Carbon::parse($pdt->subDay());

            $prev = Sale::whereRaw('extract(day from created_at) =' . $prev_dt->day .
                ' AND extract(month from created_at) =' . $prev_dt->month .
                ' AND extract(year from created_at) =' . $prev_dt->year .
                ' AND unit_id =' . $sale->unit_id )->first();

            $sale['prev'] = (!empty($prev)) ? $prev->id : 'missing';

            $sale['date'] = $date->day;

            $sale['day'] = $date->format('l');

            $sale['month'] = $date->format('F');

            $sale['year'] = $date->year;

            $expenses = $this->calculate_expense($sale);

            $deposits = $this->calculate_deposits($sale);

            $food_expenses = $this->calculate_food_expense($sale);

            $deposit_cash = $sale['cash'] - $expenses;

            $available_cash = round( $deposit_cash - $deposits, 4, PHP_ROUND_HALF_DOWN);

            $sale['sale_expenses'] = (int)$expenses;

            $sale['sale_deposits'] = (int)$deposits;

            $sale['food_delivery'] = (int)$food_expenses;

            $sale['deposit_cash'] = $deposit_cash;

            $sale['cash_in_hand'] = $available_cash;

            $sale['total_cash_in_hand'] = (isset($this->get_sale_detail($prev)['total_cash_in_hand'])) ? $available_cash + $this->get_sale_detail($prev)['total_cash_in_hand'] : $available_cash;

            return $sale;

        }

        return 'missing';

    }

    protected function calculate_previous_cashover($sale){

        $unit_id = $sale->unit_id;

        $date = Carbon::parse($sale->created_at)->format('Y-m-d H:i:s');

        $prev_sales = Sale::where('created_at' , '<' ,$date)
            ->where('unit_id' , $unit_id )->get();

        if(empty($prev_sales))

            return 0;

        $prev_cash_over = 0;

        foreach($prev_sales as $sale){

            $sale = $this->get_sale_detail($sale);

            $prev_cash_over  += $sale['cash_in_hand'];

        }

        return $prev_cash_over;

    }

    /**
     * Get commulative sale expense against a sale.
     *
     * @param  int  $sale
     * @return array $expense
     */
    public function calculate_sales_expenses($sale){

        $sales_expenses = $this->calculate_expense($sale);

        $food_expenses = $this->calculate_food_expense($sale);

        $expenses = $sales_expenses + $food_expenses;

        return (int)$expenses;

    }

    /**
     * Get sale expenses against a sale.
     *
     * @param  int  $sale
     * @return array $expense
     */
    public function calculate_expense($sale){

        $expenses = $sale->expenses;

        if (empty($expenses))
            return 0;

        $amount = 0;

        foreach($expenses as $expense){

            $amount += $expense['amount'];

        }

        return (int)$amount;

    }

    /**
     * Get sale food expenses against a sale.
     *
     * @param  int  $sale
     * @return array $expense
     */
    public function calculate_food_expense($sale){

        $food_expense = $sale->sales_food_delivery;

        if (empty($food_expense))

            return 0;

        $amount = 0;

        foreach($food_expense as $expense){

            $amount += $expense['amount'];

        }

        return (int)$amount;

    }

    /**
     * Get sale deposits against a sale.
     *
     * @param  int  $sale
     * @return array $expense
     */
    public function calculate_deposits($sale){

        $deposits = $sale->deposits;

        if (empty($deposits))

            return 'empty';

        $amount = 0;

        foreach($deposits as $deposit){

            $amount += $deposit['amount'];

        }

        return (int)$amount;

    }

    /**
     * Get sale deposits against a sale.
     *
     * @param  int  $unit_id
     * @param  int  $month
     * @param  int  $year
     * @return array $expense
     */

    public function calculate_payroll_expense_monthly($unit_id, $month, $year){

        $weeks = $this->date_controller->get_weeks_in_month($month, $year);

        $expenses = array();

        foreach($weeks as $week => $alias){

            $expense = $this->payroll_controller->process_payroll($unit_id, $year, $week);

            if (empty($expense))

                return 'No payroll run yet.';

            if($expense !== 'Payroll not run yet.'){

                $generated = array();

                $generated['week'] = $week;

                $generated['pay_period'] = $alias;

                $generated['total_expense'] = $generated['total_expense_cash'] = 0;

                if (sizeof($expense['employee_payrolls']) !== 0){

                    $generated['total_expense'] = $expense['total_expense'];

                }

                if (sizeof($expense['cash_employee_payrolls']) !== 0){

                    $generated['total_expense_cash'] = $expense['total_expense_cash'];

                }

                $generated['cumulative_expense'] = $generated['total_expense'] + $generated['total_expense_cash'];

                $expenses['payrolls_data'][] = $generated;

            }

        }

        $total_expense = $total_expense_cash = 0;

        if (!empty($expenses)){

            foreach($expenses['payrolls_data'] as $expense){

                $total_expense += $expense['total_expense'];

                $total_expense_cash += $expense['total_expense_cash'];

            }

        }

        $expenses['total_expense'] = $total_expense;

        $expenses['total_expense_cash'] = $total_expense_cash;

        $expenses['payroll_expense'] = $total_expense + $total_expense_cash;

        return $expenses;

    }

    /**
     * Get sale deposits against a sale.
     *
     * @param  int  $unit_id
     * @param  int  $year
     * @param  int  $week
     * @return array $expense
     */

    public function calculate_payroll_expense_weekly($unit_id, $year, $week){

        $expenses = array();

        $expense = $this->payroll_controller->process_payroll($unit_id, $year, $week);

        if (empty($expense))

            return 'No payroll run yet.';

        if($expense !== 'Payroll not run yet.'){

            $generated = array();

            $generated['week'] = $week;

            $generated['pay_period'] = $week;

            $generated['total_expense'] = $generated['total_expense_cash'] = 0;

            if (sizeof($expense['employee_payrolls']) !== 0){

                $generated['total_expense'] = $expense['total_expense'];

            }

            if (sizeof($expense['cash_employee_payrolls']) !== 0){

                $generated['total_expense_cash'] = $expense['total_expense_cash'];

            }

            $generated['cumulative_expense'] = $generated['total_expense'] + $generated['total_expense_cash'];

            $expenses['payrolls_data'][] = $generated;

        }

        $total_expense = $total_expense_cash = 0;

        if (!empty($expenses)){

            foreach($expenses['payrolls_data'] as $expense){

                $total_expense += $expense['total_expense'];

                $total_expense_cash += $expense['total_expense_cash'];

            }

        }

        $expenses['total_expense'] = $total_expense;

        $expenses['total_expense_cash'] = $total_expense_cash;

        $expenses['payroll_expense'] = $total_expense + $total_expense_cash;

        return $expenses;

    }

}
