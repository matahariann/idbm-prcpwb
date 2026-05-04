<?php

namespace App\Http\Controllers\Api\HITUAM\HITUAM02;

use App\Helpers\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Models\HITUAM01\HITUAM_MSHUSER as User;

class HITUAMF009 extends Controller
{
    public function login(Request $request)
    {
        $user = User::query()
            ->where('VUSERNAME', $request->username)
            ->first();

        if (!$user) {
            return Response::error('You have entered an invalid username or password', 404);
        }

        if (! Hash::check($request->password, $user->VPASSWORD)) {
            return Response::error('You have entered an invalid username or password', 404);
        }

        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return Response::success(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return Response::success(message: 'Logout successful');
    }
}
