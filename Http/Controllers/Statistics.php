<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Analytics;
use Spatie\Analytics\Period;
class Statistics extends Controller
{
    public function pageviews(Request $request)
    {
        try
        {
            $day = 7;
            if(isset($request->day))
            {
                $day = $request->day;
            }
            $data = Analytics::fetchMostVisitedPages(Period::days($day));
            return $data;
        }
        catch(\Exception $e)
        {
            return $e;
        }
      
    }
    public function pageviewsactivity(Request $request)
    {
        try
        {
           
        $traffic['data']  =[];
        $traffic['header']  =[];
        $day = 30;
        if(isset($request->day))
        {
            $day = $request->day;
        }
        $data = Analytics::performQuery(
            Period::days($day),
            'ga:pagePath',
            [
                'metrics' => 'ga:pageviews',
                'dimensions' => 'ga:pagePath,ga:country',
                'filters'=>'ga:pagePath==/property/38'
                
            ]
        );
        $data = json_decode(json_encode($data));
        return json_encode($data);
        if(isset($data->rows))
        {
            foreach($data->rows as $item)
            {
                array_push($traffic['data'], ['name'=>$item[0], 'type'=>$item[1], 'session'=>$item[2], 'pageviews'=>$item[3], 'sessionDuration'=>$item[4], 'exits'=>$item['5']]);
            }
                array_push($traffic['header'], ['status'=>true,'day'=>$day, 'date'=>date('Y-m-d H:i:s')]);
           
        }
        else
        {
            array_push($traffic['header'], ['status'=>false]);
        }
            return $traffic;
         }
        catch(\Exception $e)
        {
            return $e;
        }
       
      
    }
    public function trafficResources()
    {
        try
        {
           
        $traffic['data']  =[];
        $traffic['header']  =[];
        $day = 7;
        if(isset($request->day))
        {
            $day = $request->day;
        }
        $data = Analytics::performQuery(
            Period::days($day),
            'ga:source,ga:medium',
            [
                'metrics' => 'ga:sessions,ga:pageviews,ga:sessionDuration,ga:exits',
                'dimensions' => 'ga:source,ga:medium'
            ]
        );
        $data = json_decode(json_encode($data));
        if(isset($data->rows))
        {
            foreach($data->rows as $item)
            {
                array_push($traffic['data'], ['name'=>$item[0], 'type'=>$item[1], 'session'=>$item[2], 'pageviews'=>$item[3], 'sessionDuration'=>$item[4], 'exits'=>$item['5']]);
            }
                array_push($traffic['header'], ['status'=>true,'day'=>$day, 'date'=>date('Y-m-d H:i:s')]);
           
        }
        else
        {
            array_push($traffic['header'], ['status'=>false]);
        }
            return $traffic;
         }
        catch(\Exception $e)
        {
            return $e;
        }
       
    }
    public function countryResources()
    {
        try
        {
           
        $traffic['data']  =[];
        $traffic['header']  =[];
        $day = 7;
        if(isset($request->day))
        {
            $day = $request->day;
        }
        $data = Analytics::performQuery(
            Period::days($day),
            'ga:source',
            [
                'metrics' => 'ga:sessions, ga:pageviews',
                'dimensions' => 'ga:country'
            ]
        );
        $data = json_decode(json_encode($data));
       
        if(isset($data->rows))
        {
            foreach($data->rows as $item)
            {
                array_push($traffic['data'], ['country'=>$item[0], 'session'=>$item[1], 'pageviews'=>$item[2]]);
            }
                array_push($traffic['header'], ['status'=>true,'day'=>$day, 'date'=>date('Y-m-d H:i:s')]);
           
        }
        else
        {
            array_push($traffic['header'], ['status'=>false]);
        }
            return $traffic;
         }
        catch(\Exception $e)
        {
            return $e;
        }
       
    }
    public function browserResources()
    {
        try
        {
           
        $traffic['data']  =[];
        $traffic['header']  =[];
        $day = 7;
        if(isset($request->day))
        {
            $day = $request->day;
        }
        $data = Analytics::performQuery(
            Period::days($day),
            'ga:source',
            [
                'metrics' => 'ga:sessions, ga:pageviews',
                'dimensions' => 'ga:operatingSystem,ga:browser'
            ]
        );
        $data = json_decode(json_encode($data));
       
        if(isset($data->rows))
        {
            foreach($data->rows as $item)
            {
                array_push($traffic['data'], ['Os'=>$item[0], 'browser'=>$item[1], 'session'=>$item[2],'pageviews'=>$item[3]]);
            }
                array_push($traffic['header'], ['status'=>true,'day'=>$day, 'date'=>date('Y-m-d H:i:s')]);
           
        }
        else
        {
            array_push($traffic['header'], ['status'=>false]);
        }
            return $traffic;
         }
        catch(\Exception $e)
        {
            return $e;
        }
       
    }
    
}
