<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|min:6|email|exists:users,email',
                'password' => 'required|min:6'
            ]);

            if (Auth::validate($credentials)) {
                $user = User::firstWhere('email', $credentials['email']);

                return response()->json([
                    'name' => $user->name,
                    'email' => $user->email,
                    'access_token' => $user->createToken('')->plainTextToken
                ]);
            }
    
            return response()->json(['status' => 'unauthorized'], 401);
        } catch(ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch(Throwable $th) {
            report($th);

            return response()->json(['status' => 'error'], 500);
        }
    }

    public function logout(): Response
    {
        try {
            Auth::user()->currentAccessToken()->delete();

            return response()->noContent();
        } catch (Throwable $th) {
            report($th);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
