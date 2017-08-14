<?php

namespace App\Http\Controllers;

use App\Employee;
use App\Unit;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Returns employees of a unit.
     *
     * @param  $unit_id
     * @return \Illuminate\Http\Response
     */
    public function index($unit_id)
    {

        return Unit::findOrFail($unit_id)->employees()->orderBy('lastname', 'ASC')->get();

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  $unit_id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $unit_id)
    {

        $flag = Unit::findOrFail($unit_id)->employees()->where('social_security_num', $request->input('social_security_num'))->first();

        if (!empty($flag))

            return response(['error' => 'Duplicate Social Security number!'], 500);

        Unit::findOrFail($unit_id)->employees()->save(new Employee($request->all()));

        return 'Created';

    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @param  $unit_id
     * @return \Illuminate\Http\Response
     */
    public function show($unit_id, $id)
    {

        return Unit::findOrFail($unit_id)->employees()->whereId($id)->first();

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @param  $unit_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $unit_id, $id)
    {

        $flag = Unit::findOrFail($unit_id)->employees()->where('social_security_num', $request->input('social_security_num'))->first();

        if (!empty($flag))

            return response(['error' => 'Duplicate Social Security number!'], 500);

        Unit::findOrFail($unit_id)->employees()->whereId($id)->update($request->except('token'));

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

        Employee::findOrFail($id)->delete();

        return 'Deleted';

    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function import()
    {

        $array = $fields = array(); $i = 0;

        $path = public_path() . '/csv/import.csv';

        $handle = @fopen($path, "r");

        if ($handle) {

            while (($row = fgetcsv($handle, 4096)) !== false) {

                if (empty($fields)) {

                    $fields = $row;

                    continue;

                }

                foreach ($row as $k=>$value) {

                    $array[$i][$fields[$k]] = $value;

                }

                $i++;

            }

            if (!feof($handle)) {

                echo "Error: unexpected fgets() fail\n";

            }

            fclose($handle);

        }

        if (!empty($array)){

            foreach ($array as $emp){

                $unit = Unit::where('number', $emp['Payroll Company Code'])->first();

                if (!empty($unit)){

                    $emp_data = array(

                        'firstname' => $emp['First Name'],
                        'middlename' => $emp['Middle Name'],
                        'lastname' => $emp['Last Name'],
                        'payroll_id' => $emp['File Number'],
                        'address' => $emp['Legal / Preferred Address: Address Line 1'] . ', ' . $emp['Legal / Preferred Address: Address Line 2'],
                        'city' => $emp['Legal / Preferred Address: City'],
                        'state' => ($emp['Worked In State Tax Code'] !== '' ) ? $emp['Worked In State Tax Code'] : 'NJ',
                        'zip' => $emp['Legal / Preferred Address: Zip / Postal Code'],
                        'country' => 'USA',
                        'gender' => $emp['Gender'],
                        'hire_date' => $this->date_format($emp['Hire Date']),
                        'job_title_id' => 1,
                        'job_title' => 'Employee',
                        'type' => $this->get_employee_type($emp['Regular Pay Rate Description']),
                        'wage' => $this->get_float($emp['Regular Pay Rate Amount']),
                        'birth_date' => $this->date_format($emp['Birth Date']),
                        'social_security_num' => $emp['Tax ID (SSN)'],
                        'marital_status' => $emp['Federal/W4 Marital Status Description'],
                        'exemptions' => $emp['Federal/W4 Exemptions'],
                        'status' => $emp['Position Status'],
                        'termination_date' => ($emp['Termination Date'] !== '') ? $this->date_format($emp['Termination Date']) : NULL,
                        'termination_reason' => $emp['Termination Reason Description'],
                        'rehire_date' => ($emp['Rehire Date'] !== '') ? $this->date_format($emp['Rehire Date']) : NULL

                    );

                    $flag = Employee::where('social_security_num', $emp['Tax ID (SSN)'])->first();

                    if (empty($flag))

                        $unit->employees()->save(new Employee($emp_data));

                }

            }

        }

        return 'Finished!';

    }

    public function date_format($date){

        $date = explode('/', $date);

        return $date[2] . '/' . $date[0] . '/' . $date[1];

    }

    public function get_employee_type($type){

        if ($type == 'Hourly')

            return 'Check';

        elseif ($type == 'Salary')

            return 'Salary';

        else

            return 'Cash';

    }

    public function get_float($val){

        $val = str_replace('$', '', $val);

        if (strpos($val, ',')){

            $val = str_replace(',', '', $val);

        }

        return floatval($val);

    }

    public function delete_all(){

        $employees = Employee::all();

        if (empty($employees))

            return 'Empty';

        foreach ($employees as $employee){

            $employee->delete();

        }

        return 'Deleted!';

    }
}
