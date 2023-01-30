<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Exception;
use \App\Models\Country as CountryModel;
use \App\Models\City;
use \App\Models\Disctrict;

class Country extends Controller
{
    public function index(Request $request)
    {
        try
        {
            $data = CountryModel::orderby('title', 'ASC')->select('id', 'title', 'iso3', 'rewrite as iso2');
            if(isset($request->q) and $request->q != "")
            {
                $data = $data->where('title', 'like', '%'.$request->q.'%')
                ->orWhere('iso3', '%', '%'.$request->q.'%');
            }
            $data = $data->get();    
            $data = $data->filter(function($item)
            {
                $item->flag = url('/flags').'/'.strtolower($item->iso2).'.svg';
                return $item;
            });
            return $this->success([
                'country' => $data
            ]);
            
        }
        catch(Exception $e)
        {
            return $this->failed($e);
        }
    }
    public function countryList(Request $request)
    {
        try
        {
            $data = CountryModel::orderby('title', 'ASC')->select('id', 'title', 'iso3');
            if(isset($request->q) and $request->q != "")
            {
                $data = $data->where('title', 'like', '%'.$request->q.'%')
                ->orWhere('iso3', '%', '%'.$request->q.'%');
            }
            $data = $data->get();    
            return $this->success([
                'country' => $data
            ]);
            
        }
        catch(Exception $e)
        {
            return $this->failed($e);
        }
    }
    public function cityList(Request $request)
    {
        try
        {
            $data = City::orderby('title', 'ASC')->select('id', 'title','country_id');
            if(isset($request->country_id) and $request->country_id != "")
            {
                $data = $data->where('country_id',$request->country_id);
            }
            if(isset($request->q) and $request->q != "")
            {
                $data = $data->where('title', 'like', '%'.$request->q.'%');
            }
            $data = $data->get();    
            return $this->success([
                'cities' => $data
            ]);
            
        }
        catch(Exception $e)
        {
            return $this->failed($e);
        }
    }
    public function distirctList(Request $request)
    {
        try
        {
            $data = Disctrict::orderby('title', 'ASC')->select('id', 'title', 'city_id');
            if(isset($request->city_id) and $request->city_id != "")
            {
                $data = $data->where('city_id',$request->city_id);
            }
            if(isset($request->q) and $request->q != "")
            {
                $data = $data->where('title', 'like', '%'.$request->q.'%');
            }
            $data = $data->get();    
            return $this->success([
                'disctrict' => $data
            ]);
            
        }
        catch(Exception $e)
        {
            return $this->failed($e);
        }
    }

}
