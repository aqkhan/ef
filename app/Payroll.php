<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{

    protected $fillable = [
        'unit_id', 'period', 'status'
    ];

    public function employee_payrolls(){

        return $this->hasMany('App\EmployeePayroll');

    }

    public function cash_employee_payrolls(){

        return $this->hasMany('App\EmployeePayroll')->where('type','=' ,'Cash');

    }

    public function reg_employee_payrolls(){

        return $this->hasMany('App\EmployeePayroll')->where('type','!=' ,'Cash');

    }

    public function unit(){

        return $this->belongsTo('App\Unit');

    }

}
