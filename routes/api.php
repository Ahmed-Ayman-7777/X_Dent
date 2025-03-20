<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\DoctorRegisterController;
use App\Http\Controllers\Api\Auth\PatientRegisterController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PostFolder\PostController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SpecializationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('/hi', function () {
    return 'Hello';
});

// Route::group(['prefix' => 'auth/{role?}'], function () {
//     Route::post('register', [AuthController::class, 'register']);
// });

Route::group(['prefix' => 'auth'], function () {
    Route::post('patient/register', [PatientRegisterController::class, 'register']);
    Route::post('doctor/register', [DoctorRegisterController::class, 'register']);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
});


















Route::get('patients', [DoctorController::class, 'getAllPatients']);
Route::get('doctors', [DoctorController::class, 'getAllDoctors']);
Route::get('doctors/filter', [DoctorController::class, 'filter']);









Route::group(['middleware' => ['jwt_verifier:api']], function () {


    Route::get('users/me', [AuthController::class, 'me']);
    Route::get('specialization', [DoctorController::class, 'userSpecialization']);








    // Doctor Routes
    Route::group(['middleware' => 'role_verifier:doctor'], function () {
        // Specializations
        Route::get('specializations', [SpecializationController::class, 'getAllSpecialization']);
        Route::get('get_doctors_by_specialization_name', [SpecializationController::class, 'getDoctorBySpecializationName']);
        Route::post('schedules/create', [ScheduleController::class, 'store']);
    });










    // Patient
    Route::group(['middleware' => 'role_verifier:patient'], function () {
        Route::get('doctors/{id}/reviews', [ReviewController::class, 'getDoctorReviews']);
        Route::get('doctors/{id}/reviews/create', [ReviewController::class, 'store']);
        Route::post('reviews/{id}/update', [ReviewController::class, 'update']);
        Route::post('reviews/{id}/delete', [ReviewController::class, 'destroy']);


        // Patient Appointments
        Route::get('doctors/{id}/details', [DoctorController::class, 'doctorDetailsForBooking']);
        Route::get('doctors/{id}/appointments/available_slots', [AppointmentController::class, 'getAvailableTimeSlots']);
        Route::post('doctors/{id}/appointments/book', [AppointmentController::class, 'bookAppointment']);
        Route::get('/patient/{status?}/appointments', [AppointmentController::class, 'getAppointmentsForPatient']);
    });



    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
