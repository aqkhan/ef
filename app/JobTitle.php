<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
{
    protected $fillable = [
        'title'
    ];

    public function employees(){

        return $this->hasMany('App\Employee');

    }

}
