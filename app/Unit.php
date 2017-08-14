<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{

    protected $fillable = [

        'number', 'name', 'user_id', 'company_name', 'address', 'city', 'state', 'zip', 'country', 'phone', 'fax',
        'contact_name', 'contact_phone', 'currency', 'dma', 'employer_contribution', 'payroll_client_id', 'payroll_file_format',
        'payroll_file_name', 'status'

    ];

    public function users(){

        return $this->belongsToMany('App\User');

    }

    public function employees(){

        return $this->hasMany('App\Employee');

    }

    public function sales(){

        return $this->hasMany('App\Sale');

    }

    public function payrolls(){

        return $this->hasMany('App\Payroll');

    }

}
