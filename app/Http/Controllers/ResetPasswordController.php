<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash as FacadesHash;

class ResetPasswordController extends Controller
{
    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        $user->update(['password' => bcrypt($request->password)]);
        return response()->json([
            'success' => true,
            'message' => 'password updated successfully',
            'status_code' => 200,
        ], 200);
    }
}
