<?php

namespace App\Http\Controllers;

use App\Events\NewSubscription;
use App\Events\SubscriptionUpdated;
use App\Events\SubscriptionCanceled;
use App\Events\SubscriptionFailure;
use App\Events\SubscriptionSuccess;
use App\Http\Resources\SubscriptionsResource;
use App\Models\PackageItem;
use App\Models\UserPackage;
use App\Services\IyzicoPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    protected function validationRules()
    {
        return [
            'card' => ['array', 'required'],
            'card.holder_name' => ['string', 'required', 'max:60'],
            'card.number' => ['required', 'max:30'],
            'card.expire_month' => ['required', 'max:2'],
            'card.expire_year' => ['required', 'max:4'],
            'card.cvc' => ['required', 'max:12'],

            'user.identity_number' => ['required'],
            'user.phone' => ['required'],
            'billing_address' => ['required', 'array'],
            'billing_address.contactName' => ['required', 'string'],
            'billing_address.country' => ['required', 'string'],
            'billing_address.city' => ['required', 'string'],
            'billing_address.address' => ['required', 'string'],
            'billing_address.zipCode' => ['required'],

            'plan_id' => [
                'required', 'integer', Rule::exists('package_items', 'id')->where(function ($query) {
                    return $query->whereNull('deleted_at')->where('status', 1);
                }),
            ],
        ];
    }


    public function subscribe(Request $req)
    {
        $paymentService = new IyzicoPaymentService();

        $validation = Validator::make($req->all(), $this->validationRules())->validate();

        $user = $this->user();
        $plan = PackageItem::find($req->plan_id);


        if ($user == null) {
            abort(403);
        }

        if ($this->checkIfUserHasActivePlan($plan->package?->type, $plan->package?->ads_type)) {
            return response([
                'message' => __('You already have an active plan.'),
            ], 403);
        }
        
        if (!$this->checkUserTypeCompatibilityWithPackageUserType($plan->package?->user_type)) {
            return response([
                'message' => __('You are not allowed to subscribe to this package. expected user type: :type', [ 'type' => $plan->package?->user_type]),
            ], 403);
        }

        DB::beginTransaction();
        $userPackage = $this->createUserPackage($plan, $user);
        $paymentResponse = $paymentService->createSubscription(
            planCode: $plan->subscription_plan_code,
            card: [
                "cardHolderName" => $req->card['holder_name'],
                "cardNumber" => $req->card['number'],
                "expireMonth" => $req->card['expire_month'],
                "expireYear" => $req->card['expire_year'],
                "cvc" => $req->card['cvc'],
            ],
            user: [
                "name" => $user->first_name,
                "surname" => $user->last_name,
                "gsmNumber" => $req->user['phone'],
                "email" => $user->email,
                "identityNumber" => $req->user['identity_number'],
                "shippingContactName" => $req->billing_address['contactName'],
                "shippingCity" => $req->billing_address['city'],
                "shippingCountry" => $req->billing_address['country'],
                "shippingAddress" => $req->billing_address['address'],
                "shippingZipCode" => $req->billing_address['zipCode'],
                "billingContactName" => $req->billing_address['contactName'],
                "billingCity" => $req->billing_address['city'],
                "billingCountry" => $req->billing_address['country'],
                "billingAddress" => $req->billing_address['address'],
                "billingZipCode" => $req->billing_address['zipCode'],
            ]
        );

        if ($paymentResponse->getStatus() == "success") {
            $userPackage->subscription_reference_code = $paymentResponse->getReferenceCode();
            $userPackage->save();

            DB::commit();

            event(new NewSubscription($user, $plan));

            return response()->json([
                'status' => 'success',
            ]);
        } else {
            DB::rollBack();
            return response()->json([
                'status' =>  $paymentResponse->getStatus(),
                'message' => $paymentResponse->getErrorMessage(),
            ], 400);
        }
    }


    public function updateSubscription(Request $request)
    {
        $paymentService = new IyzicoPaymentService();

        $validation = Validator::make($request->all(), $this->validationRules())->validate();

        $user = $this->user();
        $plan = PackageItem::find($request->plan_id);


        if ($user == null) {
            abort(403);
        }

        if (!$userActivePackages = $this->getUserActivePackages($plan->package?->type)) {
            return response([
                'message' => __('You don\'t have any active plans.'),
            ], 403);
        }

        

        foreach ($userActivePackages as $package) {
            if ($package->package_item_id == $request->plan_id) {
                return response([
                    'message' => __('You already subscribed to this plan.'),
                ], 403);
            }
        }


        DB::beginTransaction();

        $userPackage = $this->createUserPackage($plan, $user);

        $paymentResponse = $paymentService->createSubscription(
            planCode: $plan->subscription_plan_code,
            card: [
                "cardHolderName" => $request->card['holder_name'],
                "cardNumber" => $request->card['number'],
                "expireMonth" => $request->card['expire_month'],
                "expireYear" => $request->card['expire_year'],
                "cvc" => $request->card['cvc'],
            ],
            user: [
                "name" => $user->first_name,
                "surname" => $user->last_name,
                "gsmNumber" => $request->user['phone'],
                "email" => $user->email,
                "identityNumber" => $request->user['identity_number'],
                "shippingContactName" => $request->billing_address['contactName'],
                "shippingCity" => $request->billing_address['city'],
                "shippingCountry" => $request->billing_address['country'],
                "shippingAddress" => $request->billing_address['address'],
                "shippingZipCode" => $request->billing_address['zipCode'],
                "billingContactName" => $request->billing_address['contactName'],
                "billingCity" => $request->billing_address['city'],
                "billingCountry" => $request->billing_address['country'],
                "billingAddress" => $request->billing_address['address'],
                "billingZipCode" => $request->billing_address['zipCode'],
            ]
        );

        if ($paymentResponse->getStatus() == "success") {
            $userPackage->subscription_reference_code = $paymentResponse->getReferenceCode();
            $userPackage->save();

            $this->deactivateUserPackages($userActivePackages);

            DB::commit();

            event(new SubscriptionUpdated($user, $plan));

            return response()->json([
                'status' => 'success',
            ]);
        } else {
            DB::rollBack();
            return response()->json([
                'status' =>  $paymentResponse->getStatus(),
                'message' => $paymentResponse->getErrorMessage(),
            ], 400);
        }
    }


    public function createUserPackage(PackageItem $package_item, $user, $subscriptionReferenceCode = null)
    {
        $userPackage = UserPackage::create([
            'user_id' => $user->id,
            'subscription_reference_code' => $subscriptionReferenceCode,
            'package_item_id' => $package_item->id,
            'status' => 1,
            'expire_date' => Carbon::now()->addMonths($package_item->package?->period),
            'price' => $package_item->price,
            'currency_id' => $package_item->currency_id,
            'balance' => $package_item->ads_number,
        ]);

        return $userPackage;
    }


    // public function subscriptionCallBack(Request $req)
    // {
    //     $paymentService = new IyzicoPaymentService();

    //     $payment = $paymentService->getPayment($req->get('paymentId'));

    //     $userPackage = UserPackage::where('subscription_id', $payment->getPaymentId())->first();

    //     if ($userPackage == null) {
    //         abort(404);
    //     }

    //     if ($payment->getStatus() == 'success') {
    //         $userPackage->update([
    //             'status' => 1,
    //             'subscription_id' => $payment->getPaymentId(),
    //         ]);
    //     }

    //     return response([
    //         'status' => $payment->getStatus(),
    //         'paid_price' => $payment->getPaidPrice(),
    //     ]);
    // }

    public function failedPaymentCallback(Request $request)
    {
        $userPackage = UserPackage::where('subscription_reference_code', $request->subscriptionReferenceCode)->first();
        if ($userPackage) {
            $userPackage->status = 0;
            $userPackage->save();

            event(new SubscriptionFailure($userPackage->user, $userPackage->plan));
        }


        return;
    }

    public function successPaymentCallback(Request $request)
    {
        $userPackage = UserPackage::where('subscription_reference_code', $request->subscriptionReferenceCode)->first();
        if ($userPackage) {
            $userPackage->status = 1;
            $userPackage->save();

            event(new SubscriptionSuccess($userPackage->user, $userPackage->plan));
        }


        return;
    }


    public function checkIfUserHasActivePlan($package_type, $ads_type)
    {
        $userPackages = UserPackage::where('user_id', $this->user()?->id)->where('status', 1)->get();
        foreach ($userPackages as $userPackage) {

            if (
                $userPackage->plan?->package?->type == $package_type
                && $userPackage->plan?->package?->ads_type == $ads_type
                && $userPackage->status == 1
                && $userPackage->expire_date > Carbon::now()
                && (new IyzicoPaymentService)->checkIsSubscriptionActive($userPackage->subscription_reference_code)
            ) {
                return true;
            }
        }

        return false;
    }

    public function checkUserTypeCompatibilityWithPackageUserType($package_user_type)
    {
        if ($this->user()->userType() == 'admin' || $this->user()->userType() == $package_user_type) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserActivePackages($package_type)
    {
        $userPackages = UserPackage::where('user_id', $this->user()?->id)->where('status', 1)->get();
        $activePackages = [];
        foreach ($userPackages as $userPackage) {

            if (
                $userPackage->plan?->package?->type == $package_type
                && $userPackage->status == 1
                && $userPackage->expire_date > Carbon::now()
                && (new IyzicoPaymentService)->checkIsSubscriptionActive($userPackage->subscription_reference_code)
            ) {
                $activePackages[] = $userPackage;
            }
        }
        
        return $activePackages;
    }

    public function deactivateUserPackages($userPackages)
    {
        $paymentService = new IyzicoPaymentService();
        foreach ($userPackages as $userPackage) {
            $paymentService->cancelSubscription($userPackage->subscription_reference_code);
            $userPackage->status = 0;
            $userPackage->save();
        }
    }

    public function cancelSubscription(Request $request)
    {
        $paymentService = new IyzicoPaymentService();

        $validation = Validator::make($request->all(), [
            'user_package_id' => [
                'required', 'integer', Rule::exists('users_packages', 'id')->where(function ($query) {
                    return $query->whereNull('deleted_at')->where('status', 1)->where('user_id', $this->user()?->id);
                }),
            ],
        ])->validate();

        $userPackage = UserPackage::find($request->user_package_id);

        if ($userPackage) {

            $paymentService->cancelSubscription($userPackage->subscription_reference_code);
            $userPackage->status = 0;
            $userPackage->save();

            event(new SubscriptionCanceled($this->user(), $userPackage->plan));
        }

        return response()->json([
            'status' => 'success',
        ]);
    }

    public function userSubscriptions()
    {
        $userPackages = UserPackage::where('user_id', $this->user()?->id)->where('status', 1)->get();
        
        return SubscriptionsResource::collection($userPackages);
    }
}
