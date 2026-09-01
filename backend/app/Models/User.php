<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\BelongsToInstitute;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, BelongsToInstitute, Impersonate;
    
    protected static function booted()
    {
        static::updating(function ($user) {
            if ($user->isDirty('password')) {
                \Log::info('User model UPDATING password', [
                    'id' => $user->id,
                    'email' => $user->email,
                    'new_hash' => substr($user->password, 0, 10) . '...'
                ]);
            }
        });
    }

    public function canImpersonate(): bool
    {
        return $this->role === 'super_admin';
    }

    public function canBeImpersonated(): bool
    {
        return $this->role !== 'super_admin';
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

        'teacher_unique_id',
        'teacher_class',
        'deactivated_at',
        'institute_id',
        'username',
        'avatar',
        'google_id',
        'bio',
        'website',
        'location',
        'profile_settings',
        'custom_fields',
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
            'profile_settings' => 'array',
            'selected_subjects' => 'array',
            'custom_fields' => 'array',
        ];
    }

    public function students()
    {
        return $this->hasMany(\App\Models\Student::class);
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

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'super-admin') {
            return $this->role === 'super_admin';
        }

        // Add rules for other panels if needed, e.g. 'admin'
        if ($panel->getId() === 'admin') {
            return in_array($this->role, ['admin', 'teacher', 'user']);
        }

        return false;
    }

    public function getSubjectCategory()
    {
        // Legacy fallback (in case students table is empty)
        $grade = $this->current_grade;
        $stream = strtolower($this->stream ?? '');
        $gradeNum = 0;

        if (preg_match('/Grade\s*(\d+)/i', $grade, $m)) {
            $gradeNum = (int)$m[1];
        } elseif (preg_match('/(\d+)/', $grade, $m)) {
            $gradeNum = (int)$m[1];
        }

        if ($gradeNum >= 1 && $gradeNum <= 2) return 'grade_1_to_2';
        if ($gradeNum == 3) return 'grade_3';
        if ($gradeNum == 4) return 'grade_4';
        if ($gradeNum == 5) return 'grade_5';
        if ($gradeNum >= 6 && $gradeNum <= 9) return 'grade_6_to_9';
        if ($gradeNum >= 10 && $gradeNum <= 11) return 'grade_10_to_11';

        if ($gradeNum >= 12 && $gradeNum <= 13) {
            if (str_contains($stream, 'art')) return 'arts_stream';
            if (str_contains($stream, 'bio') || str_contains($stream, 'math')) return 'bio_maths_stream';
            return 'grade_12_to_13'; // Fallback
        }

        return null;
    }

    public function calculateMonthlyFee(?float $fallbackAmount = null): float
    {
        // If the user has students linked, sum their fees
        if ($this->students()->exists()) {
            $total = 0;
            foreach ($this->students as $student) {
                $total += $student->calculateMonthlyFee($fallbackAmount);
            }
            return $total;
        }

        // --- LEGACY LOGIC BELOW (fallback during migration) ---
        $amount = 0;

        if ($this->selected_subjects) {
            $category = $this->getSubjectCategory();

            // Parse selected subjects (could be JSON array or comma-separated)
            $subjectsRaw = $this->selected_subjects;
            if (is_string($subjectsRaw) && str_starts_with(trim($subjectsRaw), '[')) {
                $selectedSubjects = json_decode($subjectsRaw, true) ?? [];
            } else {
                $selectedSubjects = array_filter(array_map('trim', explode(',', (string) $subjectsRaw)));
            }

            if (!empty($selectedSubjects)) {
                $medium = strtolower($this->medium ?? '');
                $subjectData = \App\Models\Subject::when($category, function ($query) use ($category) {
                    return $query->where('category', $category);
                })
                ->whereIn('name', $selectedSubjects)
                ->when($medium, function ($query) use ($medium) {
                    return $query->where(function ($q) use ($medium) {
                        $q->where('medium', $medium)->orWhere('medium', 'both');
                    });
                })
                ->get(['name', 'price']);

                // Deduplicate by name (in case both medium-specific and 'both' entries exist)
                $uniqueSubjects = $subjectData->unique('name');
                $sumAmount = (float) $uniqueSubjects->sum('price');
                
                // Check for a package bundle
                $package = \App\Models\Package::where('category', $category)
                    ->where(function ($q) use ($medium) {
                        $q->where('medium', $medium)->orWhere('medium', 'both');
                    })
                    ->first();
                
                if ($package) {
                    // Count total available subjects for this category and medium
                    $totalSubjectsForCategory = \App\Models\Subject::where('category', $category)
                        ->where(function ($q) use ($medium) {
                            $q->where('medium', $medium)->orWhere('medium', 'both');
                        })
                        ->get()
                        ->unique('name')
                        ->count();
                    
                    if ($totalSubjectsForCategory > 0 && count($uniqueSubjects) >= $totalSubjectsForCategory) {
                        $sumAmount = (float) $package->package_price;
                    }
                }

                $amount = $sumAmount;
            }
        }

        if ($amount <= 0) {
            $amount = $fallbackAmount ?? config('payment.monthly_amount', 500.0);
        }

        $isFirstPayment = !$this->payments()->where('status', 'paid')->exists();
        if ($isFirstPayment && $this->current_grade) {
            $gradeNum = 0;
            if (preg_match('/(\d+)/', $this->current_grade, $m)) {
                $gradeNum = (int)$m[1];
            }
            if ($gradeNum > 0) {
                $configStr = \App\Models\SiteSetting::get('admission_fees_config', '{}');
                $config = json_decode($configStr, true) ?? [];
                
                if (isset($config[$gradeNum]) && $config[$gradeNum]['enabled']) {
                    $admissionFee = (float)$config[$gradeNum]['amount'];
                    $amount += $admissionFee;
                }
            }
        }

        return $amount;
    }

    public function getMonthlyFeeBreakdown(?float $fallbackAmount = null): array
    {
        $breakdown = [];
        $total = 0;

        if ($this->students()->exists()) {
            foreach ($this->students as $student) {
                $fee = $student->calculateMonthlyFee($fallbackAmount);
                $name = $student->first_name ?? $student->full_name ?? 'Student';
                $breakdown[] = [
                    'name' => $name,
                    'amount' => $fee,
                    'grade' => $student->current_grade
                ];
                $total += $fee;
            }
        } else {
            // Legacy
            $fee = $this->calculateMonthlyFee($fallbackAmount);
            $breakdown[] = [
                'name' => $this->first_name ?? $this->full_name ?? 'Student',
                'amount' => $fee,
                'grade' => $this->current_grade
            ];
            $total = $fee;
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown
        ];
    }
}
