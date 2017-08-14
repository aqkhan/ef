<?php

namespace App\Http\Controllers;

use App\Sale;
use App\SalesFoodDelivery;
use Illuminate\Http\Request;

class SalesFoodDeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return SalesFoodDelivery::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $sale_id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $sale_id)
    {
        Sale::findOrFail($sale_id)->sales_food_delivery()->save(new SalesFoodDelivery($request->all()));

        return 'Created';
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        SalesFoodDelivery::findOrFail($id)->update($request->except('token'));
        return 'Updated';
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        SalesFoodDelivery::findOrFail($id)->delete();
        return 'Deleted';
    }
}
