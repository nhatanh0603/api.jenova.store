<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Enums\Gender;
use App\Http\Resources\HeroCardResource;
use App\Jobs\SendEmail;
use App\Mail\ResetPassword;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password as PasswordValidator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Password;
use App\Mail\Test;

class AuthController extends Controller
{
    /**
     * Return authenticated user information.
     *
     * @return \Illuminate\Http\Response
     */
    public function show() {
        return response()->json([
            'user' => new UserResource(auth()->user())
        ]);
    }

    /**
     * Get user's owned cards.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCards() {
        $id_array = DB::table('order_details')->join('orders', 'orders.id', '=', 'order_details.order_id')
                        ->where('orders.user_id', auth()->user()->id)
                        ->select('product_id', DB::raw('SUM(quantity) AS quantity, MAX(name) AS name'))
                        ->groupBy('product_id')->get();

        if(count($id_array) > 0) {
            foreach ($id_array as $key => $value) {
                $products[$key] = Product::find($value->product_id);
                $products[$key]->unit = $value->quantity;
            }

            return HeroCardResource::collection(collect($products)->sortBy('id')->values());
        } else {
            return response()->json([
                'data' => []
            ]);
        }
    }

    /**
     * Sign up a user with username, email and password.
     *
     * @param  \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function signup(AuthRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        $user->cart()->create(['user_id' => $user->id]);

        return response()->json([
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Sign in a user with an email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function signin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:6|max:100'
        ]);

        if(!Auth::attempt($validated))
            return response()->json([
                'errors' => ['unauthorized' => 'Invalid login credentials.']
            ], 401);

        return response()->json([
            'access_token' => auth()->user()->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource(auth()->user())
        ]);
    }

    /**
     * Update a user information.
     *
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $validated = request()->validate([
            'username' => 'required|alpha_dash|max:40',
            'fullname' =>  'nullable|string|max:100',
            'address' =>  'nullable|string|max:255',
            'phone' =>  'nullable|digits:10',
            'birthday' =>  'nullable|date|date_format:Y-m-d',
            'gender' =>  ['nullable', new Enum(Gender::class)]
        ]);

        try {
            auth()->user()->update($validated);

            return response()->json([
                'message' => 'Your profile has been updated successfully.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ops, something went wrong. Please try again later.'
            ], 409);
        }
    }

    /**
     * Change User's Password.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword()
    {
        $validated = request()->validate([
            'old_password' => ['bail', 'current_password:sanctum', 'required', 'string', PasswordValidator::min(6)->letters()->numbers(), 'max:100'],
            'new_password' => ['bail', 'confirmed', 'different:old_password', 'required', 'string', PasswordValidator::min(6)->letters()->numbers(), 'max:100'],
            'new_password_confirmation' => ['bail', 'required', 'string', PasswordValidator::min(6)->letters()->numbers(), 'max:100']
        ]);

        try {
            auth()->user()->fill([
                'password' => bcrypt($validated['new_password'])
            ])->save();

            return response()->json([
                'message' => 'Your password was successfully changed.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ops, something went wrong. Please try again later.'
            ], 409);
        }
    }

    /**
     * Handle User's Request Forgot Password.
     *
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword()
    {
        request()->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            request()->only('email'),
            function($user, $token) {
                SendEmail::dispatch(new ResetPassword($user, $token), $user);
            }
        );

        $statusCode = $status === Password::INVALID_USER ? 404 :
                     ($status === Password::RESET_THROTTLED ? 409 : 200);

        return response()->json([
            'message' => __($status)
        ], $statusCode);
    }

    /**
     * Handle User's Request To Reset Password.
     * vendor\laravel\framework\src\Illuminate\Auth\Passwords\PasswordBroker.php
     *
     * @return \Illuminate\Http\Response
     */
    public function resetPassword()
    {
        request()->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password'=> ['required', 'string', PasswordValidator::min(6)->letters()->numbers(), 'max:100', 'confirmed'],
        ]);

        $status = Password::reset(
            request()->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();
            }
        );

        $statusCode = $status === Password::PASSWORD_RESET ? 200 : 404;

        return response()->json([
            'message' => __($status)
        ], $statusCode);
    }

    /**
     * Sign out a user from a current device.
     *
     * @return \Illuminate\Http\Response
     */
    public function signout()
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token Revoked'
        ]);
    }
}
