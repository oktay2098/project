<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
//models
use App\Models\Car;
use App\Models\Favorite;
use App\Models\User;
use App\Models\Properties;

//traits
use App\Traits\ApiResponserTrait;

class FavoriteCarsController extends Controller
{
    use ApiResponserTrait;

    public function getUserFavorites(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'favorite_type' => 'required',
        ]);
     
        if ($validator->fails()) {
            return $this->errorResponse('get_user_favorites', $validator->errors()->all(), 409);
        }

        if($request->favorite_type == 'car') {

            $favorites = $this->user()->favorite_cars;

        } else if($request->favorite_type == 'property') {

            $favorites = $this->user()->favorite_properties;

        } else {

            return $this->errorResponse('get_user_favorites', 'Wrong favorite_type.', 409);
        }

        return $this->successResponse($favorites, 'get_user_favorites');
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'favorite_type' => 'required',
        ]);
     
        if ($validator->fails()) {
            return $this->errorResponse('get_favorites', $validator->errors()->all(), 409);
        }
        
        if($request->favorite_type == 'car') {

            $favorites = Car::all()->sortByDesc('favorite_count')->get(5);

        } else if($request->favorite_type == 'property') {

            $favorites = Properties::all()->sortByDesc('favorite_count')->get(5);

        } else {

            return $this->errorResponse('get_favorites', 'Wrong favorite_type.', 409);
        }

        return $this->successResponse($favorites, 'get_favorites');
    }

   
    
    public function addFavoriteCar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'favorite_id' => 'required',
            'favorite_type' => 'required',
        ]);
     
        if ($validator->fails()) {
            return $this->errorResponse('add_favorite', $validator->errors()->all(), 409);
        }
        if($request->favorite_type == 'car') {

            $favorite = Favorite::create([
                'user_id' => $this->user()->id,
                'favorite_id' => $request->favorite_id,
                'favorite_type' => 'App\Models\Car'
            ]);

        } else if($request->favorite_type == 'property') {

            $favorite = Favorite::create([
                'user_id' => $this->user()->id,
                'favorite_id' => $request->favorite_id,
                'favorite_type' => 'App\Models\Properties'
            ]);

        } else {

            return $this->errorResponse('add_favorite', 'Wrong favorite_type.', 409);
        }
        
        return $this->successResponse($favorite, 'add_favorite');
    }
    
    public function destroy($id)
    {
        $favorite = Favorite::find($id);
        if(!$favorite) {
            return $this->errorResponse('destroy_favorite', 'Favorite not found', 409);
        }
        $favorite->deleteOrFail();
        return $this->successResponse(null, 'destroy_favorite', '');
    }
}
