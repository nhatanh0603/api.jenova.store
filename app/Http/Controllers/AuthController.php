<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
     * Sign up a user with user_name, email and password.
     *
     * @param  \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function signup(AuthRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'user_name' => $validated['user_name'],
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
     * Sign out a user from a current device.
     *
     * @param  int  $id
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
