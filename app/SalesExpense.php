<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesExpense extends Model
{
    protected $fillable  = [
        'sale_id', 'expense_id', 'description', 'amount', 'expense_title'
    ];

    public function sale(){

        return $this->belongsTo('App\Sale');

    }
}
