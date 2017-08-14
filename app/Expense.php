<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{

    protected $fillable = [
        'title', 'category', 'type'
    ];

    public function sales_expenses(){

        return $this->hasMany('\App\SalesExpense');

    }

}
