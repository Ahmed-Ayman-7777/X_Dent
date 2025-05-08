<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\OtpCodeRequest;
use App\Models\User;
use App\Notifications\ForgetPasswordNotificatoin;
use Ichtrojan\Otp\Otp;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    private $otp;

    public function __construct()
    {
        $this->otp = new Otp();
    }
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $input = $request->only('email');
        $user = User::where('email', $input)->first();
        $user->notify(new ForgetPasswordNotificatoin());
        return response()->json([
            'success' => true,
            'message' => 'code sent successfully',
            'status_code' => 200,
            'data' => $request->email,
        ], 200);
    }

    public function otpChecking(OtpCodeRequest $request)
    {
        $input = $this->otp->validate($request->email, $request->code);
        if (!$input->status) {
            return response()->json([
                'success' => 'error',
                'message' => $input,
                'status_code' => 401,
            ], 401);
        }
        $user = User::where('email', $request->email)->first();
        return response()->json([
            'success' => true,
            'message' => 'Valid Code',
            'status_code' => 200,
            'data' => $user->email,
        ], 200);
    }
}
