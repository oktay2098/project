<?php

namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;
//traits
use App\Traits\ApiResponserTrait;

class CarRequest extends FormRequest
{
    use ApiResponserTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'translations' => 'required',
            'price' => 'required',
            'status' => 'required',
            'year' => 'required',
            'is_used' => 'required',
            'model' => 'required',
            'kilometre' => 'required',
            'doors_number' => 'required',
            'horsepower' => 'required',
            'engine_displacement' => 'required',
            'seat_number' => 'required',
            'cylinders' => 'required',
            'currency_id' => 'required',
            'class_id' => 'required',
            'brand_id' => 'required',
            'body_style_id' => 'required',
            'drivetrain_id' => 'required',
            'transmission_id' => 'required',
            'exterior_color_id' => 'required',
            'interior_color_id' => 'required',
            'fuel_type_id' => 'required',
            'address' => ['nullable', 'array'],
            'address.inserted' => ['required', 'string'],
            'address.geodata' => ['required', 'array'],
        ];
    }
    protected function failedValidation(Validator $validator) { 
    
        $response = [
            'success' => false,
			'message' => $validator->errors(),
			'action' => 'car',
			'status' => 'Error'
		];
        
        throw new HttpResponseException(response()->json($response, 200)); 
    }
}
