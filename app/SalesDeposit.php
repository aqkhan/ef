<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesDeposit extends Model
{
    protected $fillable = [
        'sale_id', 'bank_title', 'bank_id', 'description', 'amount'
    ];

    public function sale(){

        return $this->belongsTo('App\Sale');

    }

}
