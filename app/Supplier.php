<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name'
    ];

    public function sales_food_deliveries(){

        return $this->hasMany('App\SalesFoodDelivery');

    }
}
