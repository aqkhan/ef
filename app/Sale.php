<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{

    protected $fillable = [
        'unit_id', 'date', 'gross_sales', 'tax', 'net_sales', 'credit_sales', 'cash', 'transactions', 'voids',
        'voids_amount', 'voids_percent', 'refunds', 'refunds_amount', 'refunds_percent', 'discounts',
        'discounts_amount', 'discounts_percent', 'status', 'created_at'
    ];

    public function expenses(){

        return $this->hasMany('App\SalesExpense');

    }

    public function deposits(){

        return $this->hasMany('App\SalesDeposit');

    }

    public function sales_food_delivery(){

        return $this->hasMany('App\SalesFoodDelivery');

    }

}
