<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeePayroll extends Model
{

    protected $fillable = [
        'employee_id', 'payroll_id', 'wages', 'gross_wages', 'hours', 'employer_contribution',
        'employer_liability', 'total_expense', 'employee_type'
    ];

    public function payroll(){

        return $this->belongsTo('App\Payroll');

    }

    public function employee(){

        return $this->belongsTo('App\Employee');

    }

}
