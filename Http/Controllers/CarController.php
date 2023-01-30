<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

use App\Http\Requests\CarRequest;
use App\Http\Resources\CarResource;
//models
use App\Models\Car;
//traits
use App\Traits\ApiResponserTrait;

class CarController extends Controller
{
    use ApiResponserTrait;


    public function index()
    {
        $cars = Car::with([
            'cars_body_style' => function ($query) {
                $query->select('id', 'title');
            },
            'car_model' => function ($query) {
                $query->select('id', 'title');
            }, 'interior_color' => function ($query) {
                $query->select('id', 'title');
            }, 'exterior_color' => function ($query) {
                $query->select('id', 'title');
            }, 'fuel' => function ($query) {
                $query->select('id', 'title');
            }, 'car_class' => function ($query) {
                $query->select('id', 'title');
            }, 'car_brand' => function ($query) {
                $query->select('id', 'title');
            }, 'drivetrain' => function ($query) {
                $query->select('id', 'title');
            }, 'transmission' => function ($query) {
                $query->select('id', 'title');
            },
            'address',
            'features',
        ])
            ->where('status', true)
            ->get();

        // avoid unnecessary relations
        $cars->each(function ($car) {
            $car->author->setAppends([]);
        });

        return $this->successResponse($cars, 'show_cars');
    }


    public function getByUser(Request $request)
    {
        $user = $this->user();
        $query = Car::query();

        if ($request->has('order_by') && $request->has('order_direction')) {
            !in_array($request->order_direction, ['desc', 'asc']) ? $request->order_direction = 'desc' : '';
            $orderBy = null;

            if ($request->order_by == 'price') {
                $orderBy = 'price';
            } elseif ($request->order_by == 'last_updated') {
                $orderBy = 'updated_at';
            } elseif ($request->order_by == 'last_created') {
                $orderBy = 'created_at';
            }

            if ($orderBy) {
                $query->orderBy($orderBy, $request->order_direction);
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->where('author_id', $user->id);

        $cars = $query->paginate($request->limit);

        return CarResource::collection($cars);
    }

    public function store(CarRequest $request)
    {
        if (!$this->hasSubscription()) {
            return response()->json([
                'message' => __('You have no subscription'),
            ], 403);
        }

        if (!$this->canCreateNew()) {
            return response()->json([
                'message' => __('you can\'t create more properties'),
            ], 403);
        }

        $car = new Car();
        $car->price = $request->price;
        $car->status = $request->status;
        $car->year = $request->year;
        $car->is_used = $request->is_used;
        $car->model = $request->model;
        $car->kilometre = $request->kilometre;
        $car->doors_number = $request->doors_number;
        $car->seat_number = $request->seat_number;
        $car->horsepower = $request->horsepower;
        $car->engine_displacement = $request->engine_displacement;
        $car->cylinders = $request->cylinders;
        $car->currency_id = $request->currency_id;
        $car->class_id = $request->class_id;
        $car->brand_id = $request->brand_id;
        $car->body_style_id = $request->body_style_id;
        $car->drivetrain_id = $request->drivetrain_id;
        $car->transmission_id = $request->transmission_id;
        $car->exterior_color_id = $request->exterior_color_id;
        $car->interior_color_id = $request->interior_color_id;
        $car->fuel_type_id = $request->fuel_type_id;
        // $car->address_id = $request->address_id;
        $car->author_id = $this->user()->id;

        foreach ($request->translations as $translations) {
            $car->setTranslation("title", $translations["language"], $translations["title"]);
            $car->setTranslation("description", $translations["language"], $translations["description"]);
            $car->setTranslation("meta_desc", $translations["language"], $translations["meta_desc"]);
        }
        $car->save();

        $this->user()->decreasePackageBalance('regular', 'cars');

        if ($request->has('address')) {
            $addressController = new AddressController();
            $addressController->createOrUpdate($request->address, $car);
        }

        $car->addAllMediaFromTokens();

        $car = Car::with([
            'cars_body_style' => function ($query) {
                $query->select('id', 'title');
            }, 'interior_color' => function ($query) {
                $query->select('id', 'title');
            }, 'exterior_color' => function ($query) {
                $query->select('id', 'title');
            }, 'fuel' => function ($query) {
                $query->select('id', 'title');
            }, 'car_class' => function ($query) {
                $query->select('id', 'title');
            }, 'car_brand' => function ($query) {
                $query->select('id', 'title');
            }, 'drivetrain' => function ($query) {
                $query->select('id', 'title');
            }, 'transmission' => function ($query) {
                $query->select('id', 'title');
            },
            'features',
            'author',
            'address'
        ])
            ->where('status', true)
            ->where('id', $car->id)
            ->first();

        return $this->successResponse($car, 'store_car', 'Car ' . $car->id . ' created');
    }

    public function update(CarRequest $request)
    {
        if (!$this->hasSubscription()) {
            return response()->json([
                'message' => __('You have no subscription'),
            ], 403);
        }

        $id = $request->id;
        try {
            $car = Car::findOrFail($id);

            if ($car->author_id != $this->currentUserId() && !$this->user()?->isAdmin()) {
                return response()->json([
                    'message' => __('You are not authorized to update this car'),
                ], 403);
            }

            $car->price = $request->price;
            $car->status = $request->status;
            $car->year = $request->year;
            $car->is_used = $request->is_used;
            $car->model = $request->model;
            $car->kilometre = $request->kilometre;
            $car->doors_number = $request->doors_number;
            $car->seat_number = $request->seat_number;
            $car->horsepower = $request->horsepower;
            $car->engine_displacement = $request->engine_displacement;
            $car->cylinders = $request->cylinders;
            $car->currency_id = $request->currency_id;
            $car->class_id = $request->class_id;
            $car->brand_id = $request->brand_id;
            $car->body_style_id = $request->body_style_id;
            $car->drivetrain_id = $request->drivetrain_id;
            $car->transmission_id = $request->transmission_id;
            $car->exterior_color_id = $request->exterior_color_id;
            $car->interior_color_id = $request->interior_color_id;
            $car->fuel_type_id = $request->fuel_type_id;
            // $car->address_id = $request->address_id;
            $car->author_id = $this->user()->id;

            foreach ($request->translations as $translations) {
                $car->setTranslation("title", $translations["language"], $translations["title"]);
                $car->setTranslation("description", $translations["language"], $translations["description"]);
                $car->setTranslation("meta_desc", $translations["language"], $translations["meta_desc"]);
            }
            $car->save();

            if ($request->has('address')) {
                $addressController = new AddressController();
                $addressController->createOrUpdate($request->address, $car, $car->address?->id);
            }

            $car->addAllMediaFromTokens();

            $car = Car::with([
                'cars_body_style' => function ($query) {
                    $query->select('id', 'title');
                }, 'interior_color' => function ($query) {
                    $query->select('id', 'title');
                }, 'exterior_color' => function ($query) {
                    $query->select('id', 'title');
                }, 'fuel' => function ($query) {
                    $query->select('id', 'title');
                }, 'car_class' => function ($query) {
                    $query->select('id', 'title');
                }, 'car_brand' => function ($query) {
                    $query->select('id', 'title');
                }, 'drivetrain' => function ($query) {
                    $query->select('id', 'title');
                }, 'transmission' => function ($query) {
                    $query->select('id', 'title');
                },
                'features',
                'author',
                'address'
            ])
                ->where('status', true)
                ->where('id', $car->id)
                ->first();

            return $this->successResponse($car, 'update_car', 'Car type' . $id . ' updated');
        } catch (\Throwable $th) {

            return $this->errorResponse('update_car', $th, 409);
        }
    }

    public function show($id)
    {
        if (!Car::find($id)) {
            return $this->errorResponse('show_car', 'Car not found', 409);
        }

        $car = Car::with([
            'cars_body_style' => function ($query) {
                $query->select('id', 'title');
            }, 'interior_color' => function ($query) {
                $query->select('id', 'title');
            }, 'exterior_color' => function ($query) {
                $query->select('id', 'title');
            }, 'fuel' => function ($query) {
                $query->select('id', 'title');
            }, 'car_class' => function ($query) {
                $query->select('id', 'title');
            }, 'car_brand' => function ($query) {
                $query->select('id', 'title');
            }, 'drivetrain' => function ($query) {
                $query->select('id', 'title');
            }, 'transmission' => function ($query) {
                $query->select('id', 'title');
            },
            'features',
            'author',
            'address'
        ])
            ->where('status', true)
            ->where('id', $id)
            ->get();

        return $this->successResponse($car, 'show_car');
    }

    public function addFeaturesToCar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feature_id_list' => ['required', 'exists:definitions,id,deleted_at,NULL'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('add_feature_car', $validator->errors()->all(), 409);
        }

        $car = Car::find($request->id);
        $added_ids = [];

        if ($car) {
            foreach ($request->feature_id_list as $feature_id) {
                if (!$car->features->contains($feature_id)) {
                    $car->features()->attach($feature_id);
                    array_push($added_ids, $feature_id);
                }
            }
            $car = Car::with([
                'cars_body_style' => function ($query) {
                    $query->select('id', 'title');
                }, 'interior_color' => function ($query) {
                    $query->select('id', 'title');
                }, 'exterior_color' => function ($query) {
                    $query->select('id', 'title');
                }, 'fuel' => function ($query) {
                    $query->select('id', 'title');
                }, 'car_class' => function ($query) {
                    $query->select('id', 'title');
                }, 'car_brand' => function ($query) {
                    $query->select('id', 'title');
                }, 'drivetrain' => function ($query) {
                    $query->select('id', 'title');
                }, 'transmission' => function ($query) {
                    $query->select('id', 'title');
                },
                'features',
                'author',
                'address'
            ])
                ->where('id', $request->id)
                ->first();

            return $this->successResponse($car, 'add_feature_car');
        } else {
            return $this->errorResponse('add_feature_car', 'Car not found.', 409);
        }
    }

    public function destroy($id)
    {
        $car = Car::find($id);
        if (!$car) {
            return $this->errorResponse('destroy_car', 'Car not found', 409);
        }
        $car->deleteOrFail();
        return $this->successResponse(null, 'destroy_car', 'Car with the id:' . $id . ' deleted.');
    }


    public function hasSubscription()
    {
        if ($this->user()?->subscription_status['cars']['status'] == 1) {
            return true;
        }
        return false;
    }

    public function canCreateNew()
    {
        if ($this->hasSubscription()) {
            if ($this->user()?->subscription_status['cars']['balance'] > 0) {
                return true;
            }
        }
        return false;
    }

    public function markAsBoostUp(Request $request)
    {
        $data = $request->all();
        Validator::make($data, [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'exists:cars,id,deleted_at,NULL'],
        ])->validate();

        $boostedUpCount = Car::where('boosted_up', 1)
            ->where('author_id', $this->currentUserId())->whereNotIn('id', $data['ids'])->count();

        if ($this->user()?->subscription_status['cars']['boost_up'] - $boostedUpCount >= count($data['ids'])) {
            $marked = 0;
            foreach ($data['ids'] as $propertyId) {
                $property = Car::findOrFail($propertyId);
                if ($property->boosted_up == 0) {
                    $property->boosted_up = 1;
                    $property->save();
                    $marked++;
                }
            }

            return response()->json([
                'message' => __(':count ads marked as boost up', ['count' => $marked]),
            ], 200);
        } else {
            return response()->json([
                'message' => __("You don't have enough BoostUp credits"),
            ], 403);
        }
    }

    public function removeBoostUp(Request $request)
    {
        $data = $request->all();
        Validator::make($data, [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'exists:cars,id,deleted_at,NULL'],
        ])->validate();

        foreach ($data['ids'] as $propertyId) {
            $property = Car::findOrFail($propertyId);
            $property->boosted_up = 0;
            $property->save();
        }

        return response([
            'success' => true,
        ]);
    }
}
