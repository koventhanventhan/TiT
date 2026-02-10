<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\ZoomScheduleController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AdminMessageController;
use App\Http\Controllers\Admin\TeacherController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Admin panel routes using the admin theme
|
*/

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    // Login routes
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    
    // Protected admin routes - only for admin role
    Route::middleware(['auth:web'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/', function () {
            // Check if user is admin
            if (auth()->check() && auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        });
        
        // Student management routes
        Route::get('/students', [StudentController::class, 'index'])->name('admin.students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('admin.students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('admin.students.store');
        Route::get('/students/{id}', [StudentController::class, 'show'])->name('admin.students.show');
        Route::get('/students/{id}/edit', [StudentController::class, 'edit'])->name('admin.students.edit');
        Route::put('/students/{id}', [StudentController::class, 'update'])->name('admin.students.update');
        Route::patch('/students/{id}', [StudentController::class, 'update']);
        Route::post('/students/{id}/confirm', [StudentController::class, 'confirm'])->name('admin.students.confirm');
        Route::post('/students/{id}/deactivate', [StudentController::class, 'deactivate'])->name('admin.students.deactivate');
        Route::post('/students/{id}/mark-paid', [StudentController::class, 'markPaid'])->name('admin.students.mark-paid');
        // Teachers
        Route::get('/teachers', [TeacherController::class, 'index'])->name('admin.teachers.index');
        Route::get('/teachers/create', [TeacherController::class, 'create'])->name('admin.teachers.create');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('admin.teachers.store');
        Route::get('/teachers/{id}/edit', [TeacherController::class, 'edit'])->name('admin.teachers.edit');
        Route::put('/teachers/{id}', [TeacherController::class, 'update'])->name('admin.teachers.update');
        Route::post('/teachers/{id}/deactivate', [TeacherController::class, 'deactivate'])->name('admin.teachers.deactivate');
        Route::delete('/teachers/{id}', [TeacherController::class, 'destroy'])->name('admin.teachers.destroy');

        // Site settings routes
        Route::get('/settings', [SiteSettingController::class, 'index'])->name('admin.settings.index');
        Route::get('/settings/about', [SiteSettingController::class, 'about'])->name('admin.settings.about');
        Route::get('/settings/contact', [SiteSettingController::class, 'contact'])->name('admin.settings.contact');
        Route::get('/settings/learning', [SiteSettingController::class, 'learning'])->name('admin.settings.learning');
        Route::get('/settings/classes', [SiteSettingController::class, 'classes'])->name('admin.settings.classes');
        Route::post('/settings', [SiteSettingController::class, 'store'])->name('admin.settings.store');
        Route::post('/settings/learning/store', [SiteSettingController::class, 'storeMaterial'])->name('admin.settings.learning.material.store');
        Route::delete('/settings/learning/{id}', [SiteSettingController::class, 'deleteMaterial'])->name('admin.settings.learning.material.delete');
        Route::post('/settings/upload', [SiteSettingController::class, 'uploadImage'])->name('admin.settings.upload');

        // Zoom classes
        Route::get('/zoom', [ZoomScheduleController::class, 'index'])->name('admin.zoom.index');
        Route::get('/zoom/create', [ZoomScheduleController::class, 'create'])->name('admin.zoom.create');
        Route::post('/zoom', [ZoomScheduleController::class, 'store'])->name('admin.zoom.store');
        Route::get('/zoom/{id}/edit', [ZoomScheduleController::class, 'edit'])->name('admin.zoom.edit');
        Route::put('/zoom/{id}', [ZoomScheduleController::class, 'update'])->name('admin.zoom.update');
        Route::delete('/zoom/{id}', [ZoomScheduleController::class, 'destroy'])->name('admin.zoom.destroy');

        // Attendance
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::post('/attendance/update', [AttendanceController::class, 'update'])->name('admin.attendance.update');

        // Messages
        Route::get('/messages', [AdminMessageController::class, 'index'])->name('admin.messages.index');
        Route::get('/messages/create', [AdminMessageController::class, 'create'])->name('admin.messages.create');
        Route::post('/messages', [AdminMessageController::class, 'store'])->name('admin.messages.store');
    });
});

