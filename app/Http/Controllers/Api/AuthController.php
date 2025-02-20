<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'email' =>'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request['email'])->first();
        $samePassword = Hash::check($request['password'], $user?->password);
        
        if (empty($user) || !$samePassword) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'El usuario o la contraseña son incorrectos'
            ], 401);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
           'success' => true,
            'data' => [
                'token' => $token,
                'user' => $user
            ],  
           'message' => 'Usuario logueado correctamente'
        ], 200);
    }
}