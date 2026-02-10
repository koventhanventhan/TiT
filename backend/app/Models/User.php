<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Scopes\InstituteScope;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected static function booted()
    {
        static::addGlobalScope(new InstituteScope);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'phone_number',
        'full_name',
        'date_of_birth',
        'gender',
        'school_name',
        'medium',
        'online_experience',
        'device_used',
        'current_grade',
        'stream',
        'selected_subjects',
        'admin_confirmed_at',
        'registration_status',
        'deactivated_at',
        'institute_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'admin_confirmed_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\Attendance::class);
    }

    public function zoomSchedulesAsTeacher()
    {
        return $this->belongsToMany(\App\Models\ZoomSchedule::class, 'zoom_schedule_teacher')
            ->withTimestamps();
    }

    public function assignments()
    {
        return $this->hasMany(\App\Models\Assignment::class, 'teacher_id');
    }

    public function submissions()
    {
        return $this->hasMany(\App\Models\AssignmentSubmission::class, 'student_id');
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function hasPaidForMonth(string $yearMonth): bool
    {
        return $this->payments()
            ->where('year_month', $yearMonth)
            ->where('status', 'paid')
            ->exists();
    }
}
