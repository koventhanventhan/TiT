<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Api\StudentZoomController;
use App\Http\Controllers\Api\TeacherZoomController;
use App\Http\Controllers\Api\StudentMessageController;
use App\Http\Controllers\Api\TranslateController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/settings', [SiteSettingController::class, 'getSettings']);
Route::get('/learning-materials', [SiteSettingController::class, 'getMaterials']);

// Translate text using Gemini (public for language switch)
Route::post('/translate', [TranslateController::class, 'translate']);

// Student registration step 1 (public - creates user with pending_payment)
Route::post('/register/step1', [RegistrationController::class, 'step1']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Route to create web session from API token (for admin dashboard access)
    Route::post('/auth/create-session', [AuthController::class, 'createWebSession']);

    // Registration step 2 (payment choice) and payment success
    Route::post('/register/step2', [RegistrationController::class, 'step2']);
    Route::post('/register/payment-success', [RegistrationController::class, 'paymentSuccess']);

    // Student zoom classes and attendance
    Route::get('/student/zoom-classes', [StudentZoomController::class, 'index']);
    Route::post('/student/attend', [StudentZoomController::class, 'attend']);
    // Student messages
    Route::get('/student/messages', [StudentMessageController::class, 'index']);
    Route::post('/student/messages/{id}/read', [StudentMessageController::class, 'markRead']);

    // New Student Dashboard Routes
    Route::prefix('student')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\StudentDashboardController::class, 'stats']);
        Route::get('/assignments', [\App\Http\Controllers\Api\StudentAssignmentController::class, 'index']);
        Route::post('/assignments/{assignment}/submit', [\App\Http\Controllers\Api\StudentAssignmentController::class, 'submit']);
        Route::get('/materials', [\App\Http\Controllers\Api\StudentMaterialController::class, 'index']);
    });

    // Teacher zoom classes and attendance
    Route::get('/teacher/zoom-classes', [TeacherZoomController::class, 'index']);
    Route::post('/teacher/attend', [TeacherZoomController::class, 'attend']);

    // New Teacher Dashboard Routes
    Route::prefix('teacher')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\TeacherDashboardController::class, 'stats']);
        Route::get('/upcoming-schedules', [\App\Http\Controllers\Api\TeacherDashboardController::class, 'upcomingSchedules']);
        
        Route::get('/students', [\App\Http\Controllers\Api\TeacherStudentController::class, 'index']);
        Route::get('/students/{student}', [\App\Http\Controllers\Api\TeacherStudentController::class, 'show']);
        
        Route::get('/assignments', [\App\Http\Controllers\Api\TeacherAssignmentController::class, 'index']);
        Route::post('/assignments', [\App\Http\Controllers\Api\TeacherAssignmentController::class, 'store']);
        Route::get('/assignments/{assignment}/submissions', [\App\Http\Controllers\Api\TeacherAssignmentController::class, 'submissions']);
        Route::post('/submissions/{submission}/grade', [\App\Http\Controllers\Api\TeacherAssignmentController::class, 'gradeSubmission']);
        
        Route::get('/materials', [\App\Http\Controllers\Api\TeacherMaterialController::class, 'index']);
        Route::post('/materials', [\App\Http\Controllers\Api\TeacherMaterialController::class, 'store']);
    });

    // Admin Master Control Routes
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\MasterAdminController::class, 'stats']);
        Route::get('/students', [\App\Http\Controllers\Api\MasterAdminController::class, 'students']);
        Route::get('/teachers', [\App\Http\Controllers\Api\MasterAdminController::class, 'teachers']);
        Route::get('/finance', [\App\Http\Controllers\Api\MasterAdminController::class, 'finance']);
        Route::get('/zoom', [\App\Http\Controllers\Api\MasterAdminController::class, 'zoomClasses']);
        Route::get('/materials', [\App\Http\Controllers\Api\MasterAdminController::class, 'materials']);
        Route::get('/settings', [\App\Http\Controllers\Api\MasterAdminController::class, 'settings']);
        Route::get('/assignments', [\App\Http\Controllers\Api\MasterAdminController::class, 'assignments']);
        Route::get('/attendance', [\App\Http\Controllers\Api\MasterAdminController::class, 'attendanceStats']);
    });

    // Super Admin Routes
    Route::prefix('super-admin')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\SuperAdminController::class, 'stats']);
        Route::get('/institutes', [\App\Http\Controllers\Api\SuperAdminController::class, 'institutes']);
        Route::post('/institutes', [\App\Http\Controllers\Api\SuperAdminController::class, 'storeInstitute']);
        Route::get('/plans', [\App\Http\Controllers\Api\SuperAdminController::class, 'plans']);
        Route::post('/plans', [\App\Http\Controllers\Api\SuperAdminController::class, 'storePlan']);
    });
});
