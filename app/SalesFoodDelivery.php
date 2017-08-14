<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesFoodDelivery extends Model
{
    protected $fillable = [
        'sales_id','supplier_title' , 'supplier_id', 'description', 'amount'
    ];

    public function sale(){

        return $this->belongsTo('App\Sale');

    }

}
