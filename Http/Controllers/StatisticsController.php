<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClickStatistics;
use App\Models\BasicStatistics;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

//traits
use App\Traits\ApiResponserTrait;

class StatisticsController extends Controller
{
    use ApiResponserTrait;

    public function click_v1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'model_id' => 'required',
            'model_type' => 'required',
            'user_id' => 'required',
            'usedFor' => 'required',
        ]);
     
        if ($validator->fails()) {
            return $this->errorResponse('click', $validator->errors()->all(), 409);
        }
        if($request->model_type == 'car') {

            ClickStatistics::create([
                'user_id' => $request->user_id,
                'model_id' => $request->model_id,
                'usedFor' => $request->usedFor,
                'model_type' => 'App\Models\Car'
            ]);

        } else if($request->model_type == 'property') {

            ClickStatistics::create([
                'user_id' => $request->user_id,
                'model_id' => $request->model_id,
                'usedFor' => $request->usedFor,
                'model_type' => 'App\Models\Properties'
            ]);

        } else {

            return $this->errorResponse('click', 'Wrong model_type.', 409);
        }
        
        return $this->successResponse(null, 'click');
    }

    public function click_v2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'increment' => 'required',
            'user_id' => 'required',
        ]);
     
        if ($validator->fails()) {
            return $this->errorResponse('click', $validator->errors()->all(), 409);
        }

        $user = User::find($request->user_id);

        if($user->hasBasicStatistics()) {
            BasicStatistics::where('user_id', $request->user_id)
            ->increment($request->increment, 1);

        } else {
            BasicStatistics::create([
                'user_id' => $request->user_id,
            ])->increment($request->increment, 1);
        }
        
        return $this->successResponse(null, 'click');
    }
}
