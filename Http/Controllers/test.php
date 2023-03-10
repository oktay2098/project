<?php

namespace App\Http\Controllers;

use App\Jobs\CheckUsersSubscriptions;
use App\Models\Currency;
use App\Services\ExchangeRateService;
use App\Services\PricesService;
use Illuminate\Http\Request;
use GoogleTranslate;

class test extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        CheckUsersSubscriptions::dispatch();
        // $currency = Currency::find(1);
        // dd(PricesService::price(100, $currency));
        // dd((new ExchangeRateService())->rates('TRY'));
        // dd((new ExchangeRateService())->convert(100, 'USD', 'TYR'));
        // dd($this->user()?->isAdmin());
        // dd(GoogleTranslate::translate(['Hello world', 'Laravel is the best'], 'tr'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
