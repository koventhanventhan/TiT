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
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);

// Public routes protected by reCAPTCHA v3 (Ticket #370951 — prevent bot email spam)
Route::middleware(['recaptcha'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/send-verification-otp', [AuthController::class, 'sendVerificationOtp']);

    // Student registration step 1 (creates user with pending_payment + sends welcome emails)
    Route::post('/register/step1', [RegistrationController::class, 'step1']);
});

// Google Auth Routes
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::get('/settings', [SiteSettingController::class, 'getSettings']);
Route::get('/learning-materials', [SiteSettingController::class, 'getMaterials']);

// Translate text using Gemini (public for language switch)
Route::post('/translate', [TranslateController::class, 'translate']);

// Public download route for materials (circumvents symlink issues)
Route::get('/materials/download', [\App\Http\Controllers\Api\TeacherMaterialController::class, 'download']);

// Public download route for avatars (circumvents symlink issues)
Route::get('/avatars/{filename}', function ($filename) {
    $path = storage_path('app/public/avatars/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
});

// Sibling merge OTP endpoints (public but rate limited)
Route::post('/register/send-merge-otp', [RegistrationController::class, 'sendMergeOtp']);
Route::post('/register/verify-merge-otp', [RegistrationController::class, 'verifyMergeOtp']);

// PayHere notification (public - called by PayHere servers)
Route::post('/payhere/notify', [RegistrationController::class, 'payhereNotify'])->name('payhere.notify');

Route::get('/test-admin-whatsapp', [RegistrationController::class, 'testAdminWhatsApp']);

// Protected routes
Route::middleware(['auth:sanctum', 'profile.context'])->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/update-avatar', [AuthController::class, 'updateAvatar']);
    Route::post('/auth/add-sibling', [AuthController::class, 'addSibling']);
    
    // Route to create web session from API token (for admin dashboard access)
    Route::post('/auth/create-session', [AuthController::class, 'createWebSession']);

    // Registration step 2 (payment choice) and payment success
    Route::post('/register/step2', [RegistrationController::class, 'step2']);
    Route::post('/register/payment-success', [RegistrationController::class, 'paymentSuccess']);

    // Student Dashboard Routes (Tenant Aware + Role: student)
    Route::middleware(['role:user', 'tenant'])->prefix('student')->group(function () {
        // Monthly Payment Routes (Must be accessible even if reg_status check fails)
        Route::get('/payment-status', [RegistrationController::class, 'checkMonthlyPaymentStatus']);
        Route::post('/pay-monthly', [RegistrationController::class, 'initializeMonthlyPayment']);
        Route::get('/payment-details', [RegistrationController::class, 'getPaymentDetails']);

        // Restricted Dashboard Routes
        Route::middleware(['reg_status'])->group(function () {
            Route::get('/zoom-classes', [StudentZoomController::class, 'index']);
            Route::get('/upcoming-schedules', [StudentZoomController::class, 'upcomingSchedules']);
            Route::post('/attend', [StudentZoomController::class, 'attend']);
            Route::get('/messages', [StudentMessageController::class, 'index']);
            Route::post('/messages/{id}/read', [StudentMessageController::class, 'markRead']);
            
            Route::get('/stats', [\App\Http\Controllers\Api\StudentDashboardController::class, 'stats']);
            Route::get('/assignments', [\App\Http\Controllers\Api\StudentAssignmentController::class, 'index']);
            Route::post('/assignments/{assignment}/submit', [\App\Http\Controllers\Api\StudentAssignmentController::class, 'submit']);
            Route::get('/materials', [\App\Http\Controllers\Api\StudentMaterialController::class, 'index']);
            Route::get('/zoom-schedules', [\App\Http\Controllers\Api\StudentZoomController::class, 'schedules']);
            Route::post('/update-subjects', [\App\Http\Controllers\StudentSubjectController::class, 'updateSubjects']);
        });
    });

    // Teacher Dashboard Routes (Tenant Aware + Role: teacher)
    Route::middleware(['role:teacher', 'tenant'])->prefix('teacher')->group(function () {
        Route::get('/zoom-classes', [TeacherZoomController::class, 'index']);
        Route::post('/attend', [TeacherZoomController::class, 'attend']);
        
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
        Route::delete('/materials/{id}', [\App\Http\Controllers\Api\TeacherMaterialController::class, 'destroy']);
    });

    // Admin Master Control Routes (Tenant Aware + Role: admin)
    Route::middleware(['role:admin', 'tenant'])->prefix('admin')->group(function () {
        Route::post('/students/promote', [\App\Http\Controllers\AdminPromotionController::class, 'promoteStudents']);
        Route::get('/stats', [\App\Http\Controllers\Api\MasterAdminController::class, 'stats']);
        Route::get('/students', [\App\Http\Controllers\Api\MasterAdminController::class, 'students']);
        Route::post('/students/bulk-delete', [\App\Http\Controllers\Api\MasterAdminController::class, 'bulkDeleteStudents']);
        Route::get('/teachers', [\App\Http\Controllers\Api\MasterAdminController::class, 'teachers']);
        Route::patch('/students/{id}/subject-review', [\App\Http\Controllers\Api\MasterAdminController::class, 'updateSubjectReview']);
        Route::post('/teachers/bulk-delete', [\App\Http\Controllers\Api\MasterAdminController::class, 'bulkDeleteTeachers']);
        Route::get('/finance', [\App\Http\Controllers\Api\MasterAdminController::class, 'finance']);
        Route::get('/zoom', [\App\Http\Controllers\Api\MasterAdminController::class, 'zoomClasses']);
        Route::post('/zoom/bulk-delete', [\App\Http\Controllers\Api\MasterAdminController::class, 'bulkDeleteZoomClasses']);
        Route::get('/materials', [\App\Http\Controllers\Api\MasterAdminController::class, 'materials']);
        Route::post('/materials', [\App\Http\Controllers\Api\MasterAdminController::class, 'storeMaterial']);
        Route::delete('/materials/{id}', [\App\Http\Controllers\Api\MasterAdminController::class, 'deleteMaterial']);
        Route::get('/settings', [\App\Http\Controllers\Api\MasterAdminController::class, 'settings']);
        Route::post('/settings', [\App\Http\Controllers\Api\MasterAdminController::class, 'updateSettings']);
        Route::get('/assignments', [\App\Http\Controllers\Api\MasterAdminController::class, 'assignments']);
        Route::get('/attendance', [\App\Http\Controllers\Api\MasterAdminController::class, 'attendanceStats']);
        Route::post('/branding', [\App\Http\Controllers\Api\MasterAdminController::class, 'updateBranding']);
        Route::get('/calendar', [\App\Http\Controllers\Api\MasterAdminController::class, 'calendarEvents']);

        // ── Exam Results Management ──
        Route::prefix('exam-results')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ExamResultController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\ExamResultController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\ExamResultController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\ExamResultController::class, 'destroy']);
            Route::post('/bulk-delete', [\App\Http\Controllers\Api\ExamResultController::class, 'bulkDelete']);
            Route::post('/import', [\App\Http\Controllers\Api\ExamResultController::class, 'import']);
            Route::get('/export', [\App\Http\Controllers\Api\ExamResultController::class, 'export']);
            Route::get('/terms', [\App\Http\Controllers\Api\ExamResultController::class, 'listTerms']);
            Route::post('/terms', [\App\Http\Controllers\Api\ExamResultController::class, 'storeTerm']);
            Route::delete('/terms/{id}', [\App\Http\Controllers\Api\ExamResultController::class, 'destroyTerm']);
        });
    });

    // Super Admin Routes (Role: super_admin) - Note: Global context, no tenant middleware usually
    Route::middleware(['role:super_admin'])->prefix('super-admin')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\SuperAdminController::class, 'stats']);
        Route::get('/institutes', [\App\Http\Controllers\Api\SuperAdminController::class, 'institutes']);
        Route::post('/institutes', [\App\Http\Controllers\Api\SuperAdminController::class, 'storeInstitute']);
        Route::patch('/institutes/{institute}', [\App\Http\Controllers\Api\SuperAdminController::class, 'updateInstitute']);
        Route::get('/plans', [\App\Http\Controllers\Api\SuperAdminController::class, 'plans']);
        Route::post('/plans', [\App\Http\Controllers\Api\SuperAdminController::class, 'storePlan']);
        Route::patch('/plans/{plan}', [\App\Http\Controllers\Api\SuperAdminController::class, 'updatePlan']);
        Route::get('/activity-logs', [\App\Http\Controllers\Api\SuperAdminController::class, 'activityLogs']);
    });

    // ── Messaging System (all authenticated roles) ──
    Route::prefix('messages')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\MessageController::class, 'index']);
        Route::get('/sent', [\App\Http\Controllers\Api\MessageController::class, 'sent']);
        Route::get('/recipients', [\App\Http\Controllers\Api\MessageController::class, 'recipients']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\MessageController::class, 'unreadCount']);
        Route::post('/', [\App\Http\Controllers\Api\MessageController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\MessageController::class, 'show']);
        Route::post('/{id}/read', [\App\Http\Controllers\Api\MessageController::class, 'markRead']);
    });
});

Route::get('/subjects/prices', [\App\Http\Controllers\Admin\SubjectController::class, 'getPrices']);
Route::get('/packages', [\App\Http\Controllers\Admin\PackageController::class, 'getActivePackages']);

// ── Exam Results (Public) ──
Route::prefix('exam-results')->group(function () {
    Route::get('/search', [\App\Http\Controllers\Api\ExamResultController::class, 'search']);
    Route::get('/terms', [\App\Http\Controllers\Api\ExamResultController::class, 'getTerms']);
    Route::get('/grades', [\App\Http\Controllers\Api\ExamResultController::class, 'getGrades']);
    Route::get('/years', [\App\Http\Controllers\Api\ExamResultController::class, 'getYears']);
});

// Contact form (public)
Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'send']);

// Tutor Application (public)
Route::post('/tutor/apply', [\App\Http\Controllers\Api\TutorApplicationController::class, 'apply']);

