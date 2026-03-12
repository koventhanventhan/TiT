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
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\TimetableController;

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
        Route::post('/students/{id}/activate', [StudentController::class, 'activate'])->name('admin.students.activate');
        Route::post('/students/{id}/deactivate', [StudentController::class, 'deactivate'])->name('admin.students.deactivate');
        Route::delete('/students/{id}', [StudentController::class, 'destroy'])->name('admin.students.destroy');
        Route::post('/students/{id}/mark-paid', [StudentController::class, 'markPaid'])->name('admin.students.mark-paid');
        // Teachers
        Route::get('/teachers', [TeacherController::class, 'index'])->name('admin.teachers.index');
        Route::get('/teachers/create', [TeacherController::class, 'create'])->name('admin.teachers.create');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('admin.teachers.store');
        Route::get('/teachers/{id}/edit', [TeacherController::class, 'edit'])->name('admin.teachers.edit');
        Route::put('/teachers/{id}', [TeacherController::class, 'update'])->name('admin.teachers.update');
        Route::post('/teachers/{id}/deactivate', [TeacherController::class, 'deactivate'])->name('admin.teachers.deactivate');
        Route::post('/teachers/{id}/activate', [TeacherController::class, 'activate'])->name('admin.teachers.activate');
        Route::delete('/teachers/{id}', [TeacherController::class, 'destroy'])->name('admin.teachers.destroy');

        // Subjects
        Route::get('/subjects', [SubjectController::class, 'index'])->name('admin.subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('admin.subjects.store');
        Route::put('/subjects/{id}', [SubjectController::class, 'update'])->name('admin.subjects.update');
        Route::delete('/subjects/{id}', [SubjectController::class, 'destroy'])->name('admin.subjects.destroy');

        // Site settings routes
        Route::get('/settings', [SiteSettingController::class, 'index'])->name('admin.settings.index');
        Route::get('/settings/about', [SiteSettingController::class, 'about'])->name('admin.settings.about');
        Route::post('/settings/branding', [SiteSettingController::class, 'updateBranding'])->name('admin.settings.updateBranding');
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
        Route::post('/zoom/{id}/notify', [ZoomScheduleController::class, 'notify'])->name('admin.zoom.notify');
        Route::delete('/zoom/{id}', [ZoomScheduleController::class, 'destroy'])->name('admin.zoom.destroy');

        // Timetable management
        Route::get('/timetables', [TimetableController::class, 'index'])->name('admin.timetables.index');
        Route::get('/timetables/create', [TimetableController::class, 'create'])->name('admin.timetables.create');
        Route::post('/timetables', [TimetableController::class, 'store'])->name('admin.timetables.store');
        Route::get('/timetables/{timetable}/edit', [TimetableController::class, 'edit'])->name('admin.timetables.edit');
        Route::put('/timetables/{timetable}', [TimetableController::class, 'update'])->name('admin.timetables.update');
        Route::delete('/timetables/{timetable}', [TimetableController::class, 'destroy'])->name('admin.timetables.destroy');
        Route::post('/timetables/{timetable}/toggle', [TimetableController::class, 'toggle'])->name('admin.timetables.toggle');
        Route::get('/timetables/sync', [TimetableController::class, 'sync'])->name('admin.timetables.sync');

        // Attendance
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::post('/attendance/update', [AttendanceController::class, 'update'])->name('admin.attendance.update');

        // Messages (embedded in admin dashboard)
        Route::get('/messages', [AdminMessageController::class, 'index'])->name('admin.messages.index');
        Route::get('/messages/unread-count', [AdminMessageController::class, 'unreadCount'])->name('admin.messages.unread-count');

        // Profile & Settings
        Route::get('/profile', [ProfileController::class, 'index'])->name('admin.profile.index');
        Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('admin.profile.update');
        Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('admin.profile.settings');
        Route::post('/profile/settings/update', [ProfileController::class, 'updateSettings'])->name('admin.profile.settings.update');
        Route::post('/profile/security/update', [ProfileController::class, 'updateSecurity'])->name('admin.profile.security.update');
        Route::post('/profile/password/update', [ProfileController::class, 'updatePassword'])->name('admin.profile.password.update');
        Route::post('/profile/admins', [ProfileController::class, 'storeAdmin'])->name('admin.profile.admins.store');
        Route::put('/profile/admins/{id}', [ProfileController::class, 'updateAdmin'])->name('admin.profile.admins.update');
        Route::delete('/profile/admins/{id}', [ProfileController::class, 'deleteAdmin'])->name('admin.profile.admins.delete');

        // Calendar Events
        Route::get('/calendar/events', [CalendarController::class, 'index'])->name('admin.calendar.events.index');
        Route::post('/calendar/events', [CalendarController::class, 'store'])->name('admin.calendar.events.store');
        Route::put('/calendar/events/{id}', [CalendarController::class, 'update'])->name('admin.calendar.events.update');
        Route::get('/calendar/events/counts', [CalendarController::class, 'getCategoryCounts'])->name('admin.calendar.events.counts');
        Route::delete('/calendar/events/{id}', [CalendarController::class, 'destroy'])->name('admin.calendar.events.destroy');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
        Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('admin.notifications.mark-as-read');
        Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('admin.notifications.mark-all-as-read');
        

        // Global Search API
        Route::get('/search', [AdminController::class, 'search'])->name('admin.search');
    });
});

