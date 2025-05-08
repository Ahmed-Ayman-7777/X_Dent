<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MyTestMail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\ApiResponseTrait; // Import the trait
use App\Http\Resources\DoctorResource;
use App\Http\Resources\PatientResource;
use App\Notifications\ForgetPasswordNotificatoin;
use PatientController;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = Auth::attempt($credentials)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            // Get the authenticated user.
            $user = Auth::guard('api')->user();

            // Event
            // Mail::to($user->email)->send(new MyTestMail(
            //     $user->first_name,
            //     $user->email,
            //     $user->specialization,
            //     'Welcome Doctor'
            // ));

            if ($user->role === 'patient') {
                //$user = User::with('patient')->find($user->id);
                return $this->respondWithToken($token, new PatientResource($user));
            } else {
                //$user = User::with('doctor')->find($user->id);
                //$user->notify(new ForgetPasswordNotificatoin());
                return $this->respondWithToken($token, new DoctorResource($user));
            }

            return $this->errorResponse();
        } catch (JWTException $e) {
            return $this->errorResponse('Could not create token', 500);
        }
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return $this->successResponse(['doctor' => $user], 'User retrieved successfully');
    }

    public function logout()
    {
        Auth::invalidate(Auth::getToken());
        return $this->successResponse([], 'Successfully logged out');
    }

    // Custom method to respond with the token
    protected function respondWithToken($token, $user)
    {
        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'data' => $user,
            //'expires_in' => auth()->factory()->getTTL() * 60 // Token expiration time in seconds
        ], 'Login successful');
    }
}
