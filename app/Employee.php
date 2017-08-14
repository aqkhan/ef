<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{

    protected $fillable = [
        'unit_id', 'firstname', 'middlename', 'lastname', 'payroll_id', 'address', 'city', 'state', 'zip', 'country',
        'phone', 'gender', 'hire_date', 'job_title_id', 'job_title', 'type', 'wage', 'birth_date',
        'social_security_num', 'marital_status', 'exemptions', 'status', 'termination_date', 'termination_reason', 'rehire_date'
    ];

    public function unit(){

        return $this->belongsTo('App\Unit');

    }

    public function job_title(){

        return $this->belongsTo('App\JobTitle');

    }

    public function employee_payrolls(){

        return $this->hasMany('App\EmployeePayroll');

    }

}
