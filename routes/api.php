<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\DoctorRegisterController;
use App\Http\Controllers\Api\Auth\PatientRegisterController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PatientController as ControllersPatientController;
use App\Http\Controllers\PostFolder\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\TreatmentPlanController;
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



    Route::put('profile/edit', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);




    // Doctor Routes
    Route::group(['middleware' => 'role_verifier:doctor'], function () {
        // Home Page 
        Route::get('doctor/home', [DoctorController::class, 'index']);


        // Specializations
        Route::get('specializations', [SpecializationController::class, 'getAllSpecialization']);
        Route::get('get_doctors_by_specialization_name', [SpecializationController::class, 'getDoctorBySpecializationName']);

        // Schedules 
        Route::post('schedules/create', [ScheduleController::class, 'store']);
        Route::get('schedules', [ScheduleController::class, 'show']);

        // Appointments
        Route::get('doctor/appointments', [AppointmentController::class, "getDoctorAppointments"]);
        Route::get('doctor/appointments/{id}/detail', [AppointmentController::class, "getDoctorAppointmentDetails"]);


        // Profile
        Route::get('doctor/patient_list', [DoctorController::class, "getDoctorPatients"]);
        Route::get('/doctor/patient_list/{id}', [DoctorController::class, 'getPatientById']);

        // Treatment Plan
        Route::get('/doctor/patient/{id}/treatment-plans', [TreatmentPlanController::class, 'getPatientTreatmentPlans']);
        Route::post('/doctor/patient/{id}/treatment-plans/create', [TreatmentPlanController::class, 'createTreatmentPlan']);
        Route::delete('doctor/treatment-plans/{id}', [TreatmentPlanController::class, 'deleteTreatmentPlan']);
        Route::put('/doctor/treatment-plans/{id}', [TreatmentPlanController::class, 'updateTreatmentPlan']);
    });










    // Patient
    Route::group(['middleware' => 'role_verifier:patient'], function () {

        // Home page
        Route::get('patient/home', [ControllersPatientController::class, 'index']);


        // Patient Reviews
        Route::get('doctors/{id}/reviews', [ReviewController::class, 'getDoctorReviews']);
        Route::get('doctors/{id}/reviews/create', [ReviewController::class, 'store']);
        Route::post('reviews/{id}/update', [ReviewController::class, 'update']);
        Route::post('reviews/{id}/delete', [ReviewController::class, 'destroy']);


        // Patient Appointments
        Route::get('doctors/{id}/details', [DoctorController::class, 'doctorDetailsForBooking']);
        Route::get('doctors/{id}/appointments/available_slots', [AppointmentController::class, 'getAvailableTimeSlots']);
        Route::post('doctors/{id}/appointments/book', [AppointmentController::class, 'bookAppointment']);
        Route::get('/patient/{status?}/appointments', [AppointmentController::class, 'getAppointmentsForPatient']);
        Route::patch('patient/{appointmentId}/cancel', [AppointmentController::class, 'cancelAppointment']);

        // Patient Detail
        Route::get('/patient/details', [ControllersPatientController::class, 'show']);
        Route::post('/patient/details', [ControllersPatientController::class, 'store']);
        Route::post('/patient/details/update', [ControllersPatientController::class, 'update']);

        // Patient Treatmentplan
        Route::get('/patient/treatment-plans', [TreatmentPlanController::class, 'getAuthPatientTreatmentPlans']);
    });






    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
