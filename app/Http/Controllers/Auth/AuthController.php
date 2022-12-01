<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Wallet;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'signup']]);
    }

    public function signup(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'firstname'  => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors()->first()
            ], 400);
        }

        try {
            DB::beginTransaction();

            $user = new User;
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->password = $request->password;
            $user->save();

            $wallet = new Wallet;
            $wallet->user_id = $user->id;
            $wallet->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'signup successful',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'error' => "Request failed"
            ], 400);
        }
    }

    public function login(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email|string',
            'password' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors()->first()
            ], 422);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);

        // if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
        //     return response()->json([
        //         'status' => 'success',
        //         'message' => 'login successful'
        //     ], 200);   
        // }
    }

    public function userProfile()
    {
        $user = User::with('wallet')->where('id', auth()->user()->id)->first();
        return response()->json($user);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return response()->json([
            'status' => 'success',
            'message' => 'logout successful'
        ], 200);
    }


    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
}
