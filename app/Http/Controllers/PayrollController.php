<?php

namespace App\Http\Controllers;
use App\Employee;
use App\EmployeePayroll;
use App\Http\Controllers\DateController;

use App\Payroll;
use App\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class PayrollController extends Controller
{

    public function __construct(DateController $date_controller){

        $this->date_controller = $date_controller;

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return Payroll::all();

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        Payroll::create($request->all());

        return 'Created';

    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        return Payroll::findOrFail($id);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        Payroll::findOrFail($id)->update($request->all());

        return 'Updated';

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        Payroll::findOrFail($id)->delete();

        return 'Deleted';

    }

    /**
     * Lists payrolls for the queried year.
     *
     * @param  $unit_id
     * @param  $year
     * @return \Illuminate\Http\Response
     */
    public function payroll_stats($unit_id, $year){

        $weeks = $this->date_controller->get_weeks_in_year($year);

        $stats = array();

        foreach($weeks as $week => $alias){

            $payroll = $this->run_payroll($unit_id, $year, $week);

            $stats[$week]['alias'] = $alias;

            $stats[$week]['status'] = $payroll['status'];

        }

        return $stats;

    }

    /**
     * Lists payrolls for the queried year.
     *
     * @param  $unit_id
     * @param  $year
     * @return \Illuminate\Http\Response
     */
    public function process_payroll_stats($unit_id, $year){

        $all_weeks = $this->date_controller->get_weeks_for_payroll($year);

        $stats = array();

        foreach($all_weeks as $week => $alias){

            $weeks = $this->date_controller->split_payroll_weeks($week);

            $week_1 = $this->process_payroll($unit_id, $year, $weeks[0]);

            $week_2 = $this->process_payroll($unit_id, $year, $weeks[1]);

            $status = '';

            if ($week_1 === 'Payroll not run yet.' || $week_2 == 'Payroll not run yet.'){

                $status = 'One or more payrolls not run yet.';

            }

            elseif ($week_1['status'] === 'Closed' && $week_2['status'] === 'Closed'){

                $status = 'Closed';

            }

            elseif ($week_1['status'] !== 'Finalized' || $week_2['status'] !== 'Finalized'){

                $status = 'One or more payrolls not finalized';

            }

            elseif($week_1['status'] === 'Finalized' && $week_2['status'] === 'Finalized'){

                $status = 'Finalized';

            }

            $stats[$week]['alias'] = $alias;

            $stats[$week]['status'] = $status;

        }

        return $stats;

    }

    /**
     * Returns payrolls for the queried period.
     *
     * @param  $unit_id
     * @param  $year
     * @param  $period
     * @return \Illuminate\Http\Response
     */
    public function run_payroll($unit_id, $year, $period)
    {

        $weeks = $this->date_controller->get_weeks_in_year($year);

        if (!array_key_exists($period, $weeks))

            return 'Invalid entry.';

        $unit = Unit::findOrFail($unit_id);

        $payroll = $unit->payrolls()->whereRaw('period = ?' , $period )->first();

        if (empty($payroll)){

            $date = explode('>', $period)[0];

            $date = Carbon::parse($date);

            $payroll['status'] = 'Payroll not run yet.';

            $employees =  $unit->employees()->where('status', '=', 'Active')->get();

            $reg_employees = $cash_employees = array();

            foreach ($employees as $key => $employee){

                $generated = array();

                $generated['hours'] = $generated['gross_wages'] = $generated['employer_liability'] = $generated['total_expense'] = 0;

                $generated['employer_contribution'] = $unit->employer_contribution;

                $generated['wages'] = $employee->wage;

                $generated['employee_data'] = $employee;

                if ($employee['type']== 'Cash'){

                    $generated['employer_contribution'] = 0;

                    $cash_employees[] = $generated;

                }

                else{

                    $reg_employees[] = $generated;

                }

            }

            $payroll['employee_payrolls'] = $reg_employees;

            $payroll['cash_employee_payrolls'] = $cash_employees;

            return $payroll;

        }

        $raw_payrolls = $payroll->employee_payrolls;

        unset($payroll['employee_payrolls']);

        $emp_payrolls = $cash_payrolls = array();

        foreach($raw_payrolls as $raw_payroll){

            $generated = $raw_payroll;

            $emp_data = Employee::findOrFail($raw_payroll->employee_id);

            $generated['employee_data'] = $emp_data;

            $emp_data['type'] = $raw_payroll['employee_type'];

            if ($raw_payroll['employee_type'] == 'Cash'){

                $cash_payrolls[] = $generated;

            }

            else{

                $emp_payrolls[] = $generated;

            }

        }

        $payroll['employee_payrolls'] = $emp_payrolls;

        $payroll['cash_employee_payrolls'] = $cash_payrolls;

        return $this->generate_payroll_summary($payroll);

    }

    /**
     * Enters payrolls for the queried period.
     *
     * @param  $unit_id
     * @param  $year
     * @param  $period
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function enter_payroll(Request $request, $unit_id, $year, $period)
    {

        $weeks = $this->date_controller->get_weeks_in_year($year);

        if (!array_key_exists($period, $weeks))

            return 'Invalid entry.';

        $unit = Unit::findOrFail($unit_id);

        $check = $unit->payrolls()->whereRaw('period = ?' , $period )->first();

        if (!empty($check))

            return 'Payroll already exists';

        $employees = $query_employees = array();

        if(!empty($unit->employees()->where('status', '=', 'Active')->get())){

            foreach($unit->employees()->where('status', '=', 'Active')->get() as $employee){

                $employees[] = $employee->id;

            }

        }

        if(!empty($request->employee_payrolls)){

            foreach($request->employee_payrolls as $employee){

                $query_employees[] = $employee['employee_id'];

            }

        }

        sort($employees);

        sort($query_employees);

        if ($employees !== $query_employees){

            return response('Incomplete data.', 422);

        }

        $payroll = $unit->payrolls()->save(new Payroll(['unit_id' => $unit_id, 'period' => $period, 'status' => 'Run']));

        $response = array();

        if (!empty($payroll)){

            $emp_payrolls = $request->employee_payrolls;

            if (!empty($emp_payrolls) && is_array($emp_payrolls)){

                foreach($emp_payrolls as $key => $emp_payroll){

                    $emp_id = Employee::find($emp_payroll['employee_id']);

                    $emp_payroll['employee_type'] = $emp_id['type'];

                    if ($emp_id){

                        $payroll->employee_payrolls()->save(new EmployeePayroll($emp_payroll));

                    }

                    else{

                        $response['errors'][$emp_payroll['employee_id']] = 'Employee not found.';

                    }

                }

            }

        }

        if (!empty($response)){

            $response['status'] = 'Incomplete';

            $payroll->update(['status' => 'Incomplete']);

        }

        else

            $response['status'] = 'Run';

        return $response;

    }

    /**
     * Updated payrolls for the queried period.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $unit_id
     * @param  $payroll_id
     * @return \Illuminate\Http\Response
     */
    public function update_payroll(Request $request, $unit_id, $payroll_id)
    {

        $unit = Unit::findOrFail($unit_id);

        $payroll = $unit->payrolls()->whereId($payroll_id)->first();

        if (empty($payroll))

            return 'Payroll does not exists';

        if (!empty($payroll)){

            $emp_payrolls = $request->employee_payrolls;

            if (!empty($emp_payrolls) && is_array($emp_payrolls)){

                foreach($emp_payrolls as $emp_payroll){

                    $payroll->employee_payrolls()->where('id', $emp_payroll['id'])->update($emp_payroll);

                }

            }

        }

        return 'Updated';

    }

    /**
     * Processes payroll for the queried period.
     *
     * @param  $unit_id
     * @param  $year
     * @param  $period
     * @return \Illuminate\Http\Response
     */
    public function process_payroll($unit_id, $year, $period)

    {

        $payrolls = $this->run_payroll($unit_id, $year, $period);

        if ($payrolls['status'] == 'Payroll not run yet.'){

            return $payrolls['status'];

        }

        $payrolls['pay_period'] = $this->date_controller->get_week_alias($year, $payrolls['period']);

        if (empty($payrolls['employee_payrolls']) && empty($payrolls['cash_employee_payrolls']))

            return $payrolls;

        foreach($payrolls['employee_payrolls'] as $emp_payroll){

            if ($emp_payroll['hours'] > 40){

                $wage = $emp_payroll['employee_data']['wage'];

                $overtime_wage = $wage * 1.5;

                $emp_payroll['over_time'] = $emp_payroll['hours'] - 40;

                $emp_payroll['hours'] = 40;

                $emp_payroll['basic_wages'] = $wage * 40;

                $emp_payroll['overtime_wages'] = $overtime_wage * $emp_payroll['over_time'];

                $emp_payroll['gross_wages'] = $emp_payroll['basic_wages'] + $emp_payroll['overtime_wages'];

                $emp_payroll['basic_employer_liability'] =
                    $this->calculate_employer_liability($emp_payroll['employer_contribution'], $emp_payroll['basic_wages']);

                $emp_payroll['overtime_employer_liability'] =
                    $this->calculate_employer_liability($emp_payroll['employer_contribution'], $emp_payroll['overtime_wages']);

                $emp_payroll['employer_liability'] =
                    $emp_payroll['basic_employer_liability'] + $emp_payroll['overtime_employer_liability'];

                $emp_payroll['basic_total_expense'] = $emp_payroll['basic_wages'] + $emp_payroll['basic_employer_liability'];

                $emp_payroll['overtime_total_expense'] = $emp_payroll['overtime_wages'] + $emp_payroll['overtime_employer_liability'];

                $emp_payroll['total_expense'] = $emp_payroll['basic_total_expense'] + $emp_payroll['overtime_total_expense'];

            }

        }

        foreach($payrolls['cash_employee_payrolls'] as $emp_payroll){

            if ($emp_payroll['hours'] > 40){

                $wage = $emp_payroll['employee_data']['wage'];

                $overtime_wage = $wage * 1.5;

                $emp_payroll['over_time'] = $emp_payroll['hours'] - 40;

                $emp_payroll['hours'] = 40;

                $emp_payroll['basic_wages'] = $wage * 40;

                $emp_payroll['overtime_wages'] = $overtime_wage * $emp_payroll['over_time'];

                $emp_payroll['gross_wages'] = $emp_payroll['basic_wages'] + $emp_payroll['overtime_wages'];

                $emp_payroll['basic_employer_liability'] =
                    $this->calculate_employer_liability($emp_payroll['employer_contribution'], $emp_payroll['basic_wages']);

                $emp_payroll['overtime_employer_liability'] =
                    $this->calculate_employer_liability($emp_payroll['employer_contribution'], $emp_payroll['overtime_wages']);

                $emp_payroll['employer_liability'] =
                    $emp_payroll['basic_employer_liability'] + $emp_payroll['overtime_employer_liability'];

                $emp_payroll['basic_total_expense'] = $emp_payroll['basic_wages'] + $emp_payroll['basic_employer_liability'];

                $emp_payroll['overtime_total_expense'] = $emp_payroll['overtime_wages'] + $emp_payroll['overtime_employer_liability'];

                $emp_payroll['total_expense'] = $emp_payroll['basic_total_expense'] + $emp_payroll['overtime_total_expense'];

            }

        }

        return $this->generate_payroll_summary($payrolls);

    }

    /**
     * Processes payroll for the queried period.
     *
     * @param  $unit_id
     * @param  $year
     * @param  $period
     * @return \Illuminate\Http\Response
     */
    public function run_process_payroll($unit_id, $year, $period)

    {

        $weeks = $this->date_controller->get_weeks_for_payroll($year);

        if (!array_key_exists($period, $weeks))

            return 'Invalid entry.';

        $payroll_data = array();

        $weeks = $this->date_controller->split_payroll_weeks($period);

        $week_1 = $this->process_payroll($unit_id, $year, $weeks[0]);

        $week_2 = $this->process_payroll($unit_id, $year, $weeks[1]);

        $payroll_data['status'] = 'Finalized';

        if ($week_1 === 'Payroll not run yet.' || $week_2 == 'Payroll not run yet.'){

            $payroll_data['status'] = 'One or more payrolls not run yet.';

            return $payroll_data;

        }

        elseif ($week_1['status'] === 'Closed' && $week_2['status'] === 'Closed'){

            $payroll_data['status'] = 'Closed';

        }

        elseif ($week_1['status'] !== 'Finalized' || $week_2['status'] !== 'Finalized'){

            $payroll_data['status'] = 'One or more payrolls not finalized';

            return $payroll_data;

        }

        $payroll_data['total_hours'] = $week_1['total_hours'] + $week_2['total_hours'];

        $payroll_data['total_gross_wages'] = $week_1['total_gross_wages'] + $week_2['total_gross_wages'];

        $payroll_data['employer_contribution'] = Unit::findOrFail($unit_id)->get(['employer_contribution'])[0]['employer_contribution'];

        $payroll_data['total_employer_liability'] = $week_1['total_employer_liability'] + $week_2['total_employer_liability'];

        $payroll_data['total_expense'] = $week_1['total_expense'] + $week_2['total_expense'];

        $payroll_data['total_hours_cash'] = $week_1['total_hours_cash'] + $week_2['total_hours_cash'];

        $payroll_data['total_gross_wages_cash'] = $week_1['total_gross_wages_cash'] + $week_2['total_gross_wages_cash'];

        $payroll_data['total_employer_liability_cash'] = $week_1['total_employer_liability_cash'] + $week_2['total_employer_liability_cash'];

        $payroll_data['total_expense_cash'] = $week_1['total_expense_cash'] + $week_2['total_expense_cash'];

        $payroll_data['week'] = $period;

        $payroll_data['week_alias'] = $this->date_controller->payroll_week_alias($year, $period);

        $payroll_data['payroll_1_id'] = $week_1['id'];

        $payroll_data['payroll_2_id'] = $week_2['id'];

        $employees_payrolls = Payroll::findOrFail($week_1['id'])->employee_payrolls;

        $employees = $cash_employees = array();

        if (!empty($employees_payrolls)){

            foreach ($employees_payrolls as $employee){

                $payroll_1 = EmployeePayroll::whereRaw('employee_id = ' . $employee['employee_id'] . ' AND payroll_id = ' . $week_1['id'])->first();

                $employee_data = Employee::findOrFail($employee['employee_id']);

                if ($payroll_1['gross_wages'] != 0){

                    if ($payroll_1['employee_type'] !== 'Cash'){

                        $employees[$employee['employee_id']]['week_1'] = $payroll_1;

                        $employees[$employee['employee_id']]['week_1']['week'] = $weeks[0];

                        $employees[$employee['employee_id']]['week_1']['week_alias'] = $this->date_controller->get_week_alias($year, $weeks[0]);

                    }

                    else{

                        $cash_employees[$employee['employee_id']]['week_1'] = $payroll_1;

                        $cash_employees[$employee['employee_id']]['week_1']['week'] = $weeks[0];

                        $cash_employees[$employee['employee_id']]['week_1']['week_alias'] = $this->date_controller->get_week_alias($year, $weeks[0]);

                    }

                }

                $payroll_2 = EmployeePayroll::whereRaw('employee_id = ' . $employee['employee_id'] . ' AND payroll_id = ' . $week_2['id'])->first();

                if ($payroll_2['gross_wages'] != 0){

                    if ($payroll_2['employee_type'] !== 'Cash'){

                        $employees[$employee['employee_id']]['week_2'] = $payroll_2;

                        $employees[$employee['employee_id']]['week_2']['week'] = $weeks[1];

                        $employees[$employee['employee_id']]['week_2']['week_alias'] = $this->date_controller->get_week_alias($year, $weeks[1]);

                    }

                    else{

                        $cash_employees[$employee['employee_id']]['week_2'] = $payroll_2;

                        $cash_employees[$employee['employee_id']]['week_2']['week'] = $weeks[1];

                        $cash_employees[$employee['employee_id']]['week_2']['week_alias'] = $this->date_controller->get_week_alias($year, $weeks[1]);

                    }

                }

                if (array_key_exists($employee['employee_id'], $employees))

                    $employees[$employee['employee_id']]['employee_data'] = $employee_data;

                if (array_key_exists($employee['employee_id'], $cash_employees))

                    $cash_employees[$employee['employee_id']]['employee_data'] = $employee_data;

            }

        }

        $payroll_data['employee_payrolls'] = $employees;

        $payroll_data['cash_employee_payrolls'] = $cash_employees;

        if (!empty($payroll_data['employee_payrolls'])){

            foreach ($payroll_data['employee_payrolls'] as $key => $payroll){

                $cumulative = array();

                if (!isset($payroll['week_1']['id'])){

                    $payroll['week_1']['hours'] = 0;

                    $payroll['week_1']['gross_wages'] = 0;

                    $payroll['week_1']['employer_liability'] = 0;

                    $payroll['week_1']['total_expense'] = 0;

                }

                if (!isset($payroll['week_2']['id'])){

                    $payroll['week_2']['hours'] = 0;

                    $payroll['week_2']['gross_wages'] = 0;

                    $payroll['week_2']['employer_liability'] = 0;

                    $payroll['week_2']['total_expense'] = 0;

                }

                $payroll['week_1']['over_time'] = $payroll['week_2']['over_time'] = 0;

                $payroll['week_1']['overtime_wages'] = $payroll['week_2']['overtime_wages'] = 0;

                $payroll['week_1']['total_gross_wages'] = $payroll['week_1']['gross_wages'];

                $payroll['week_2']['total_gross_wages'] = $payroll['week_2']['gross_wages'];

                $payroll['week_1']['overtime_employer_liability'] = $payroll['week_2']['overtime_employer_liability'] = 0;

                $payroll['week_1']['total_employer_liability'] = $payroll['week_1']['employer_liability'];

                $payroll['week_2']['total_employer_liability'] = $payroll['week_2']['employer_liability'];

                $payroll['week_1']['total_hours'] = $payroll['week_1']['hours'];

                $payroll['week_2']['total_hours'] = $payroll['week_2']['hours'];

                if ($payroll['week_1']['hours'] > 40){

                    $payroll['week_1']['over_time'] = $payroll['week_1']['hours'] - 40;

                    $payroll['week_1']['hours'] = $payroll['week_1']['hours'] - $payroll['week_1']['over_time'];

                    $payroll['week_1']['gross_wages'] = $payroll['week_1']['hours'] * $payroll['week_1']['wages'];

                    $payroll['week_1']['overtime_wages'] = ($payroll['week_1']['over_time'] * $payroll['week_1']['wages']) * 1.5;

                    $payroll['week_1']['total_gross_wages'] = $payroll['week_1']['gross_wages'] + $payroll['week_1']['overtime_wages'];

                    $payroll['week_1']['total_hours'] = $payroll['week_1']['over_time'] + $payroll['week_1']['hours'];

                    $payroll['week_1']['employer_liability'] =
                        $this->calculate_employer_liability($payroll['week_1']['employer_contribution'], $payroll['week_1']['gross_wages']);

                    $payroll['week_1']['overtime_employer_liability'] =
                        $this->calculate_employer_liability($payroll['week_1']['employer_contribution'], $payroll['week_1']['overtime_wages']);

                    $payroll['week_1']['total_employer_liability']
                        = $payroll['week_1']['employer_liability'] + $payroll['week_1']['overtime_employer_liability'];

                    $payroll['week_1']['total_expense'] = $payroll['week_1']['total_gross_wages'] + $payroll['week_1']['total_employer_liability'];

                }

                if ($payroll['week_2']['hours'] > 40){

                    $payroll['week_2']['over_time'] = $payroll['week_2']['hours'] - 40;

                    $payroll['week_2']['hours'] = $payroll['week_2']['hours'] - $payroll['week_2']['over_time'];

                    $payroll['week_2']['gross_wages'] = $payroll['week_2']['hours'] * $payroll['week_2']['wages'];

                    $payroll['week_2']['overtime_wages'] = ($payroll['week_2']['over_time'] * $payroll['week_2']['wages']) * 1.5;

                    $payroll['week_2']['total_gross_wages'] = $payroll['week_2']['gross_wages'] + $payroll['week_2']['overtime_wages'];

                    $payroll['total_hours'] = $payroll['week_2']['over_time'] + $payroll['week_2']['hours'];

                    $payroll['week_2']['employer_liability'] =
                        $this->calculate_employer_liability($payroll['week_2']['employer_contribution'], $payroll['week_2']['gross_wages']);

                    $payroll['week_2']['overtime_employer_liability'] =
                        $this->calculate_employer_liability($payroll['week_2']['employer_contribution'], $payroll['week_2']['overtime_wages']);

                    $payroll['week_2']['total_employer_liability']
                        = $payroll['week_2']['employer_liability'] + $payroll['week_2']['overtime_employer_liability'];

                    $payroll['week_2']['total_expense'] = $payroll['week_2']['total_gross_wages'] + $payroll['week_2']['total_employer_liability'];

                }

                $cumulative['hours'] = $payroll['week_1']['total_hours'] + $payroll['week_2']['total_hours'];

                $cumulative['base_hours'] = $payroll['week_1']['hours'] + $payroll['week_2']['hours'];

                $cumulative['over_time'] = $payroll['week_1']['over_time'] + $payroll['week_2']['over_time'];

                $cumulative['gross_wages'] = $payroll['week_1']['total_gross_wages'] + $payroll['week_2']['total_gross_wages'];

                $cumulative['total_employer_liability'] =
                    $payroll['week_1']['total_employer_liability'] + $payroll['week_2']['total_employer_liability'];

                $cumulative['total_expense'] =
                    $payroll['week_1']['total_expense'] + $payroll['week_2']['total_expense'];

                $payroll['cumulative'] = $cumulative;

                $payroll_data['employee_payrolls'][$key] = $payroll;

            }

        }

        if (!empty($payroll_data['cash_employee_payrolls'])){

            foreach ($payroll_data['cash_employee_payrolls'] as $key => $payroll){

                $cumulative = array();

                if (!isset($payroll['week_1']['id'])){

                    $payroll['week_1']['hours'] = 0;

                    $payroll['week_1']['gross_wages'] = 0;

                    $payroll['week_1']['employer_liability'] = 0;

                    $payroll['week_1']['total_expense'] = 0;

                }

                if (!isset($payroll['week_2']['id'])){

                    $payroll['week_2']['hours'] = 0;

                    $payroll['week_2']['gross_wages'] = 0;

                    $payroll['week_2']['employer_liability'] = 0;

                    $payroll['week_2']['total_expense'] = 0;

                }

                $payroll['week_1']['over_time'] = $payroll['week_2']['over_time'] = 0;

                $payroll['week_1']['overtime_wages'] = $payroll['week_2']['overtime_wages'] = 0;

                $payroll['week_1']['total_gross_wages'] = $payroll['week_1']['gross_wages'];

                $payroll['week_2']['total_gross_wages'] = $payroll['week_2']['gross_wages'];

                $payroll['week_1']['total_hours'] = $payroll['week_1']['hours'];

                $payroll['week_2']['total_hours'] = $payroll['week_2']['hours'];

                if ($payroll['week_1']['hours'] > 40){

                    $payroll['week_1']['over_time'] = $payroll['week_1']['hours'] - 40;

                    $payroll['week_1']['hours'] = $payroll['week_1']['hours'] - $payroll['week_1']['over_time'];

                    $payroll['week_1']['gross_wages'] = $payroll['week_1']['hours'] * $payroll['week_1']['wages'];

                    $payroll['week_1']['overtime_wages'] = ($payroll['week_1']['over_time'] * $payroll['week_1']['wages']) * 1.5;

                    $payroll['week_1']['total_gross_wages'] = $payroll['week_1']['gross_wages'] + $payroll['week_1']['overtime_wages'];

                    $payroll['total_hours'] = $payroll['week_1']['over_time'] + $payroll['week_1']['hours'];

                    $payroll['week_1']['total_expense'] = $payroll['week_1']['total_gross_wages'] + $payroll['week_1']['total_employer_liability'];

                }

                if ($payroll['week_2']['hours'] > 40){

                    $payroll['week_2']['over_time'] = $payroll['week_2']['hours'] - 40;

                    $payroll['week_2']['hours'] = $payroll['week_2']['hours'] - $payroll['week_2']['over_time'];

                    $payroll['week_2']['gross_wages'] = $payroll['week_2']['hours'] * $payroll['week_2']['wages'];

                    $payroll['week_2']['overtime_wages'] = ($payroll['week_2']['over_time'] * $payroll['week_2']['wages']) * 1.5;

                    $payroll['week_2']['total_gross_wages'] = $payroll['week_2']['gross_wages'] + $payroll['week_2']['overtime_wages'];

                    $payroll['total_hours'] = $payroll['week_2']['over_time'] + $payroll['week_2']['hours'];

                    $payroll['week_2']['total_expense'] = $payroll['week_2']['total_gross_wages'] + $payroll['week_2']['total_employer_liability'];

                }

                $cumulative['hours'] = $payroll['week_1']['hours'] + $payroll['week_2']['total_hours'];

                $cumulative['base_hours'] = $payroll['week_1']['hours'] + $payroll['week_2']['hours'];

                $cumulative['over_time'] = $payroll['week_1']['over_time'] + $payroll['week_2']['over_time'];

                $cumulative['gross_wages'] = $payroll['week_1']['total_gross_wages'] + $payroll['week_2']['total_gross_wages'];

                $cumulative['total_expense'] =
                    $payroll['week_1']['total_expense'] + $payroll['week_2']['total_expense'];

                $payroll['cumulative'] = $cumulative;

                $payroll_data['cash_employee_payrolls'][$key] = $payroll;

            }

        }

        return $payroll_data;

    }

    /**
     * Processes payroll for the queried period.
     *
     * @param  $employer_contribution
     * @param  $wages
     * @return \Illuminate\Http\Response
     */
    public function calculate_employer_liability($employer_contribution, $wages){

        return ($wages * $employer_contribution) / 100;

    }

    /**
     * Processes payroll for the queried period.
     *
     * @param  $payrolls
     * @return \Illuminate\Http\Response
     */
    public function generate_payroll_summary($payrolls){

        if (!empty($payrolls['employee_payrolls']) && sizeof($payrolls['employee_payrolls']) !== 0){

            $total_hours = $total_gross_wages = $total_employer_liability = $total_expense = 0;

            foreach($payrolls['employee_payrolls'] as $payroll){

                $payroll['hours'] += $payroll['over_time'];

                $total_hours += $payroll['hours'];

                $total_gross_wages += $payroll['gross_wages'];

                $total_employer_liability += $payroll['employer_liability'];

                $total_expense += $payroll['total_expense'];

            }

            $payrolls['total_hours'] = $total_hours;

            $payrolls['total_gross_wages'] = $total_gross_wages;

            $payrolls['total_employer_liability'] = $total_employer_liability;

            $payrolls['total_expense'] = $total_expense;

        }

        if (!empty($payrolls['cash_employee_payrolls']) && sizeof($payrolls['cash_employee_payrolls']) !== 0){

            $total_hours_cash = $total_gross_wages_cash = $total_employer_liability_cash = $total_expense_cash = 0;

            foreach($payrolls['cash_employee_payrolls'] as $payroll){

                $payroll['hours'] += $payroll['over_time'];

                $total_hours_cash += $payroll['hours'];

                $total_gross_wages_cash += $payroll['gross_wages'];

                $total_employer_liability_cash += $payroll['employer_liability'];

                $total_expense_cash += $payroll['total_expense'];

            }

            $payrolls['total_hours_cash'] = $total_hours_cash;

            $payrolls['total_gross_wages_cash'] = $total_gross_wages_cash;

            $payrolls['total_employer_liability_cash'] = $total_employer_liability_cash;

            $payrolls['total_expense_cash'] = $total_expense_cash;

        }

        return $payrolls;

    }

    /**
     * Processes payroll for the queried period.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function update_payroll_status(Request $request){

        if(!empty($request->payroll_ids) && !empty($request->status)){

            $status = $request->status;

            $payroll_ids = explode(',', $request->payroll_ids);

            foreach ($payroll_ids as $payroll_id){

                Payroll::findOrFail($payroll_id)->update(array('status' => $status));

            }

        }

        else{

            return response('Invalid data.', 400);

        }

    }

    /**
     * Processes payroll for the queried period.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function delete_payroll($id){

        $payroll = Payroll::findOrFail($id);

        $employee_payrolls = $payroll->employee_payrolls;

        if (!empty($employee_payrolls)){

            foreach ($employee_payrolls as $employee_payroll){

                $employee_payroll->delete();

            }

        }

        $payroll->delete();

        return 'Deleted';

    }

    /**
     * Processes payroll for the queried period.
     *
     * @param  int $unit_id
     * @param  int $year
     * @param  string $period
     * @return \Illuminate\Http\Response
     */
    public function generate_payroll_file($unit_id, $year, $period){

        $payrolls = $this->run_process_payroll($unit_id, $year, $period);

        if ($payrolls['status'] !== 'Closed')

            return response(['message' => 'Payroll not closed!'], 400);

        $unit = Unit::findOrFail($unit_id);

        $file_name = ($unit->payroll_client_id !== '' && $unit->payroll_client_id !== null) ? $unit->payroll_client_id : $unit->number;

        $path = public_path() . '/gen/' . $file_name . '.csv';

        return $this->generate_csv($payrolls, $path, $unit->payroll_file_format, $unit->payroll_client_id);

    }

    public function generate_csv($payrolls, $path, $type, $payroll_client_id){

        if ($type == 'ADP'){

            $data = array();

            $data[] = array('Co Code', 'Batch ID', 'File #', 'Reg Hours', 'O/T Hours', 'Reg Earnings');

            if (!empty($payrolls['employee_payrolls'])){

                foreach ($payrolls['employee_payrolls'] as $employee_payroll) {

                    $type = ($employee_payroll['employee_data']['type'] == 'Check') ? 'Hours' : 'Salary';

                    $raw = array($payroll_client_id, $type, $employee_payroll['employee_data']['payroll_id'],
                        $employee_payroll['cumulative']['base_hours'], $employee_payroll['cumulative']['over_time'],
                        $employee_payroll['cumulative']['gross_wages']);

                    $data[] = $raw;

                }

            }

            $fp = fopen($path, 'w');

            foreach ($data as $d)
                fputcsv($fp, $d);

        }

        elseif ($type == 'Hartford'){

            $data = array();

            $data[] = array('Legal', 'PayGroup', 'Division', 'Department', 'Key', 'Name', 'E_Regular_Hours',
                'E_Overtime_Hours', 'E_Salary_Dollars', 'E_Vacation_Hours', 'E_GAS REIMB_Dollars', 'E_Bonus_Dollars',
                'E_Other_Dollars');

            if (!empty($payrolls['employee_payrolls'])){

                foreach ($payrolls['employee_payrolls'] as $employee_payroll) {

                    $raw = array($payroll_client_id, 'Bi-Weekly', '', '', $employee_payroll['employee_data']['payroll_id'],
                        $employee_payroll['employee_data']['lastname'] . ', ' . $employee_payroll['employee_data']['firstname'],
                        $employee_payroll['cumulative']['base_hours'], $employee_payroll['cumulative']['over_time'],
                        $employee_payroll['cumulative']['gross_wages']);

                    $data[] = $raw;

                }

            }

            $fp = fopen($path, 'w');

            foreach ($data as $d)
                fputcsv($fp, $d);

        }

        else{

            $data = array();

            $data[] = array('Client ID', 'Worker ID', 'Org', 'Job Number', 'Pay Component', 'Rate', 'Rate Number',
                'Hours', 'Units', 'Line Date', 'Amount', 'Check Seq Number', 'Override State', 'Override Local',
                'Override Local Jurisdiction', 'Labor Assignment');

            if (!empty($payrolls['employee_payrolls'])){

                foreach ($payrolls['employee_payrolls'] as $employee_payroll) {

                    $type = ($employee_payroll['employee_data']['type'] == 'Check') ? 'Hourly' : 'Salary';

                    $raw = array($payroll_client_id, $employee_payroll['employee_data']['payroll_id'], '', '', $type,
                        '', '', $employee_payroll['cumulative']['hours'], '', '', '', 1, '', '', '', '');

                    $data[] = $raw;

                }

            }

            $fp = fopen($path, 'w');

            foreach ($data as $d)
                fputcsv($fp, $d);

        }

        if (file_exists($path))

            return response(['message' => 'File created!'], 200);

        return response(['error' => 'Cannot create file.'], 500);

    }

    public function download_payroll_file($unit_id){

        $unit = Unit::findOrFail($unit_id);

        $file_name = $unit->payroll_client_id . '.csv';

        $file = public_path() . '/gen/' . $file_name;

        if (file_exists($file)) {

            return response(['path' => URL::to('/') . '/gen/' . $file_name], 200);

        }

        return response(['error' => 'File not found.'], 404);

    }

}
