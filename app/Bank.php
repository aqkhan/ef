<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = [
        'name'
    ];

    public function sales_deposits(){

        return $this->hasMany('App\SalesDeposit');

    }
}
