<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\Review;
use \Exception;
use Illuminate\Support\Facades\Auth;

class ReviewsController extends Controller
{
    public function getList(Request $request)
    {
        try
        {
            $validated = $request->validate([
                'id' => 'required',
                'model' => 'required',
            ]);
            $reviews = Review::where('model_type',$request->model)->where('model_id', $request->id)->with('replies','replies.user:first_name,last_name')->join('users', 'users.id', '=', 'reviews.user_id')->select('users.first_name as name', 'reviews.id', 'reviews.text', 'reviews.stars_value', 'reviews.created_at')->get();
            return $reviews;
        } 
        catch (Exception $e) {
            return $this->failed($e);
        }
        
    }
    public function create(Request $request)
    {
        try
        {
          
        $validated = $request->validate([
            'text' => 'required',
            'stars_value' => 'required',
            'model'=>'required',
            'id'=>'required'
        ]);
        $review = new Review();
        $review->user_id = Auth::user()->id;
        $review->status = 0;
        $review->text = $request->text;
        $review->stars_value = $request->stars_value;
        $review->model_id = $request->id;
        $review->model_type = $request->model;
        if($review->save())
        {
            return $this->success([
                'review' => $review
                 ]);
        }
    } 
    catch (Exception $e) {
        return $this->failed($e);
    }
    }
}
