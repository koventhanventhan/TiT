<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LearningMaterial;
use App\Models\Message;
use App\Models\User;
use App\Models\ZoomSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * EventNotifier
 *
 * Central place for all event-driven EMAIL alerts (attendance, class changes,
 * recordings, materials, account events, registration, admin alerts ...).
 *
 * All mails are sent through NotificationService (email only - no WhatsApp
 * template exists for these), so they inherit DNS/MX validation, parent
 * redirection and bounce tracking.
 *
 * When called from an HTTP request, sending is deferred until after the
 * response has been sent so the user never waits for SMTP.
 */
class EventNotifier
{
    // ------------------------------------------------------------------
    // Infrastructure helpers
    // ------------------------------------------------------------------

    /** Run now in console, otherwise after the HTTP response is sent. */
    public static function defer(callable $fn): void
    {
        $safe = function () use ($fn) {
            try {
                $fn();
            } catch (\Throwable $e) {
                Log::error('EventNotifier: deferred task failed: ' . $e->getMessage());
            }
        };

        if (app()->runningInConsole()) {
            $safe();
        } else {
            app()->terminating($safe);
        }
    }

    protected static function notifier(): NotificationService
    {
        return app(NotificationService::class);
    }

    protected static function name(User $u): string
    {
        return $u->full_name ?? $u->name ?? 'Student';
    }

    protected static function appUrl(string $path = ''): string
    {
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        return $base . $path;
    }

    protected static function fmt($date): string
    {
        return Carbon::parse($date)->timezone(config('app.timezone'))->format('D, M j, Y \a\t h:i A');
    }

    /** Send email-only to a user (parent redirect + validation handled inside). */
    public static function toUser(User $user, string $type, array $data): bool
    {
        try {
            return self::notifier()->notifyUserByEmail($user, $type, $data);
        } catch (\Throwable $e) {
            Log::error("EventNotifier: [{$type}] to user #{$user->id} failed: " . $e->getMessage());
            return false;
        }
    }

    /** Send email-only to the admin. */
    public static function toAdmin(string $type, array $data): bool
    {
        try {
            return self::notifier()->notifyAdminByEmail($type, $data);
        } catch (\Throwable $e) {
            Log::error("EventNotifier: admin [{$type}] failed: " . $e->getMessage());
            return false;
        }
    }

    /** Send to many users with light rate limiting. */
    protected static function toMany(iterable $users, string $type, array $data, ?callable $personalise = null): int
    {
        $sent = 0;
        foreach ($users as $user) {
            $payload = $personalise ? $personalise($user, $data) : $data;
            if (self::toUser($user, $type, $payload)) {
                $sent++;
                if ($sent % 5 === 0) {
                    sleep(1);
                }
            }
        }
        return $sent;
    }

    /** Students matching a class (grade / medium / subject). */
    protected static function matchingStudents(?string $grade, ?string $medium, ?string $subject): Collection
    {
        if (empty($grade)) {
            return collect();
        }
        if (in_array($medium, ['all', 'both', ''], true)) {
            $medium = null;
        }
        return app(ClassNotifier::class)->getMatchingStudents($grade, $medium, $subject);
    }

    // ------------------------------------------------------------------
    // 1. Attendance
    // ------------------------------------------------------------------

    public static function absent(User $student, ZoomSchedule $schedule): bool
    {
        return self::toUser($student, 'attendance_absent', [
            'subject_line' => 'Absence Alert - ' . self::name($student) . ' - TiT Education',
            'heading'      => 'Absent from Class',
            'icon'         => '⚠️',
            'color'        => '#f59e0b',
            'greeting'     => 'Dear Parent / Student,',
            'message'      => self::name($student) . ' did not attend the class below. If this was unavoidable, please ignore this message. Recordings (when available) can be watched from the dashboard.',
            'details'      => [
                'Student' => self::name($student),
                'Class'   => $schedule->title,
                'Subject' => $schedule->subject,
                'Grade'   => $schedule->grade,
                'Time'    => self::fmt($schedule->scheduled_at),
            ],
            'button_url'   => self::appUrl('/'),
            'button_text'  => 'Open Dashboard',
        ]);
    }

    public static function lowAttendance(User $student, int $percent, int $attended, int $total): bool
    {
        return self::toUser($student, 'attendance_low', [
            'subject_line' => 'Low Attendance Warning - ' . self::name($student) . ' - TiT Education',
            'heading'      => 'Low Attendance Warning',
            'icon'         => '📉',
            'color'        => '#ef4444',
            'greeting'     => 'Dear Parent / Student,',
            'message'      => self::name($student) . "'s attendance over the last 30 days is below the recommended level. Regular attendance is very important for good results.",
            'details'      => [
                'Student'            => self::name($student),
                'Attendance'         => $percent . '%',
                'Classes attended'   => $attended . ' of ' . $total,
            ],
            'button_url'   => self::appUrl('/'),
            'button_text'  => 'View Schedule',
        ]);
    }

    // ------------------------------------------------------------------
    // 2. Class changes (cancel / reschedule / teacher change)
    // ------------------------------------------------------------------

    /** Teachers are passed in because the pivot rows may already be gone on delete. */
    protected static function notifyClassPeople(ZoomSchedule $schedule, Collection $teachers, string $type, array $data): void
    {
        $classNotifier = $schedule;
        self::defer(function () use ($classNotifier, $teachers, $type, $data) {
            self::toMany($teachers, $type, $data);
            $students = self::matchingStudents($classNotifier->grade, $classNotifier->medium, $classNotifier->subject);
            self::toMany($students, $type, $data);
        });
    }

    public static function classCancelled(ZoomSchedule $schedule, ?Collection $teachers = null): void
    {
        // Only worth telling people about classes that haven't happened yet
        if ($schedule->scheduled_at && $schedule->scheduled_at->isPast()) {
            return;
        }
        $teachers = $teachers ?? $schedule->teachers;

        self::notifyClassPeople($schedule, $teachers, 'class_cancelled', [
            'subject_line' => 'Class Cancelled - ' . $schedule->title . ' - TiT Education',
            'heading'      => 'Class Cancelled',
            'icon'         => '❌',
            'color'        => '#ef4444',
            'message'      => 'The following class has been cancelled. We apologise for the inconvenience.',
            'details'      => [
                'Class'          => $schedule->title,
                'Subject'        => $schedule->subject,
                'Grade'          => $schedule->grade,
                'Was scheduled'  => self::fmt($schedule->scheduled_at),
            ],
        ]);
    }

    public static function classRescheduled(ZoomSchedule $schedule, $oldTime): void
    {
        self::notifyClassPeople($schedule, $schedule->teachers, 'class_rescheduled', [
            'subject_line' => 'Class Rescheduled - ' . $schedule->title . ' - TiT Education',
            'heading'      => 'Class Rescheduled',
            'icon'         => '🔄',
            'color'        => '#0ea5e9',
            'message'      => 'The time of the following class has changed. Please note the new time.',
            'details'      => [
                'Class'     => $schedule->title,
                'Subject'   => $schedule->subject,
                'Grade'     => $schedule->grade,
                'Old time'  => self::fmt($oldTime),
                'New time'  => self::fmt($schedule->scheduled_at),
            ],
            'button_url'   => self::appUrl('/'),
            'button_text'  => 'View Schedule',
        ]);
    }

    public static function teacherChanged(ZoomSchedule $schedule, array $oldNames, array $newNames): void
    {
        self::notifyClassPeople($schedule, $schedule->teachers, 'class_teacher_changed', [
            'subject_line' => 'Teacher Changed - ' . $schedule->title . ' - TiT Education',
            'heading'      => 'Teacher Changed',
            'icon'         => '👩‍🏫',
            'color'        => '#8b5cf6',
            'message'      => 'The teacher for the following class has been changed.',
            'details'      => [
                'Class'        => $schedule->title,
                'Subject'      => $schedule->subject,
                'Time'         => self::fmt($schedule->scheduled_at),
                'Previous'     => implode(', ', $oldNames) ?: '-',
                'New teacher'  => implode(', ', $newNames) ?: '-',
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // 3. Recording ready / 4. New study material
    // ------------------------------------------------------------------

    public static function materialCreated(LearningMaterial $material): void
    {
        if (empty($material->grade)) {
            return; // no audience to target
        }

        $isRecording = $material->type === 'recording' && $material->zoom_schedule_id;

        self::defer(function () use ($material, $isRecording) {
            $students = self::matchingStudents($material->grade, $material->medium, null);
            if ($students->isEmpty()) {
                return;
            }

            if ($isRecording) {
                $data = [
                    'subject_line' => 'Class Recording Ready - ' . $material->title,
                    'heading'      => 'Recording Ready',
                    'icon'         => '🎥',
                    'color'        => '#6366f1',
                    'message'      => 'The recording of your class is now available. You can watch it anytime from the Materials section of your dashboard.',
                    'details'      => [
                        'Recording' => $material->title,
                        'Grade'     => $material->grade,
                    ],
                    'button_url'   => self::appUrl('/'),
                    'button_text'  => 'Watch Now',
                ];
                $type = 'recording_ready';
            } else {
                $data = [
                    'subject_line' => 'New Study Material - ' . $material->title,
                    'heading'      => 'New Study Material',
                    'icon'         => '📚',
                    'color'        => '#10b981',
                    'message'      => 'A new study material has been uploaded for your grade.',
                    'details'      => [
                        'Title' => $material->title,
                        'Type'  => ucfirst((string) $material->type),
                        'Grade' => $material->grade,
                    ],
                    'button_url'   => self::appUrl('/'),
                    'button_text'  => 'Open Materials',
                ];
                $type = 'material_uploaded';
            }

            self::toMany($students, $type, $data);
        });
    }

    // ------------------------------------------------------------------
    // 5. Account events
    // ------------------------------------------------------------------

    public static function passwordResetLink(User $user, string $url): bool
    {
        return self::toUser($user, 'password_reset_link', [
            'subject_line' => 'Reset Your Password - TiT Education',
            'heading'      => 'Reset Your Password',
            'icon'         => '🔑',
            'color'        => '#4f46e5',
            'greeting'     => 'Hello ' . self::name($user) . ',',
            'message'      => 'We received a request to reset your password. Click the button below to choose a new one. This link is valid for 60 minutes.',
            'button_url'   => $url,
            'button_text'  => 'Reset Password',
            'footer_note'  => "If you didn't request this, you can safely ignore this email - your password will not change.",
        ]);
    }

    public static function passwordChanged(User $user): void
    {
        self::defer(fn() => self::toUser($user, 'password_changed', [
            'subject_line' => 'Your Password Was Changed - TiT Education',
            'heading'      => 'Password Changed',
            'icon'         => '🔒',
            'color'        => '#059669',
            'greeting'     => 'Hello ' . self::name($user) . ',',
            'message'      => 'Your account password was just changed.',
            'details'      => ['Time' => self::fmt(now())],
            'footer_note'  => "If this wasn't you, please contact us immediately.",
        ]));
    }

    public static function newDeviceLogin(User $user, string $ip, string $agent): void
    {
        self::defer(fn() => self::toUser($user, 'new_device_login', [
            'subject_line' => 'New Login to Your Account - TiT Education',
            'heading'      => 'New Device Login',
            'icon'         => '🛡️',
            'color'        => '#0ea5e9',
            'greeting'     => 'Hello ' . self::name($user) . ',',
            'message'      => 'Your account was just accessed from a new device or browser.',
            'details'      => [
                'Time'    => self::fmt(now()),
                'IP'      => $ip,
                'Device'  => mb_substr($agent, 0, 120),
            ],
            'footer_note'  => "If this was you, no action is needed. If not, please change your password and contact us.",
        ]));
    }

    public static function accountReactivated(User $user): void
    {
        $lockKey = 'reactivated_notif_' . $user->id;
        if (!Cache::add($lockKey, true, 60)) {
            return;
        }

        self::defer(fn() => self::toUser($user, 'account_reactivated', [
            'subject_line' => 'Account Reactivated ✅ - TiT Education',
            'heading'      => 'Account Reactivated',
            'icon'         => '✅',
            'color'        => '#059669',
            'greeting'     => 'Hello ' . self::name($user) . ',',
            'student_name' => self::name($user),
            'message'      => 'Good news! Your account has been reactivated. You can log in and continue as usual.',
            'button_url'   => self::appUrl('/'),
            'button_text'  => 'Login',
        ]));
    }

    public static function teacherDeactivated(User $user): void
    {
        self::defer(fn() => self::toUser($user, 'account_deactivated', [
            'student_name' => self::name($user),
        ]));
    }

    // ------------------------------------------------------------------
    // 6. Registration
    // ------------------------------------------------------------------

    public static function registrationRejected(User $user, ?string $reason): void
    {
        self::defer(fn() => self::toUser($user, 'registration_rejected', [
            'subject_line' => 'Registration Update - TiT Education',
            'heading'      => 'Registration Not Approved',
            'icon'         => '📝',
            'color'        => '#ef4444',
            'greeting'     => 'Dear ' . self::name($user) . ',',
            'message'      => "Unfortunately your registration could not be approved at this time.\n\nReason: " . ($reason ?: 'Not specified') . "\n\nPlease contact us if you would like to correct the details and apply again.",
        ]));
    }

    public static function approvalPendingStudent(User $user): bool
    {
        return self::toUser($user, 'approval_pending', [
            'subject_line' => 'Your Registration Is Under Review - TiT Education',
            'heading'      => 'Registration Under Review',
            'icon'         => '⏳',
            'color'        => '#f59e0b',
            'greeting'     => 'Dear ' . self::name($user) . ',',
            'message'      => 'Thank you for registering. Your registration is still waiting for admin approval. We will email you as soon as it is approved. If you have already paid, please contact us so we can speed things up.',
        ]);
    }

    // ------------------------------------------------------------------
    // 7. Messages / complaints
    // ------------------------------------------------------------------

    public static function studentMessage(Message $message, User $sender, User $receiver): void
    {
        $text = mb_strtolower(($message->subject ?? '') . ' ' . ($message->body ?? ''));
        $isComplaint = str_contains($text, 'complaint') || str_contains($text, 'complain');

        self::defer(function () use ($message, $sender, $receiver, $isComplaint) {
            $data = [
                'subject_line' => ($isComplaint ? 'Complaint from ' : 'New Message from ') . self::name($sender),
                'heading'      => $isComplaint ? 'Student Complaint' : 'New Student Message',
                'icon'         => $isComplaint ? '🚨' : '💬',
                'color'        => $isComplaint ? '#ef4444' : '#4f46e5',
                'message'      => mb_substr((string) $message->body, 0, 600),
                'details'      => [
                    'From'    => self::name($sender),
                    'Grade'   => $sender->current_grade,
                    'Subject' => $message->subject,
                ],
                'button_url'   => self::appUrl('/'),
                'button_text'  => 'Open Messages',
            ];

            self::toUser($receiver, $isComplaint ? 'student_complaint' : 'student_message', $data);

            // Complaints always reach the admin as well
            if ($isComplaint && $receiver->role !== 'admin') {
                self::toAdmin('student_complaint', $data);
            }
        });
    }

    // ------------------------------------------------------------------
    // 8. Admin system alerts
    // ------------------------------------------------------------------

    public static function paymentFailed(User $user, $payment, string $statusLabel, ?string $orderId): void
    {
        $amount = number_format((float) ($payment->amount ?? 0), 2);

        self::defer(function () use ($user, $amount, $statusLabel, $orderId, $payment) {
            self::toAdmin('payment_failed', [
                'subject_line' => 'Payment ' . $statusLabel . ' - ' . self::name($user),
                'heading'      => 'Payment ' . $statusLabel,
                'icon'         => '💳',
                'color'        => '#ef4444',
                'message'      => 'An online (PayHere) payment did not complete.',
                'details'      => [
                    'Student' => self::name($user),
                    'Amount'  => 'LKR ' . $amount,
                    'Month'   => $payment->year_month ?? null,
                    'Order'   => $orderId,
                    'Status'  => $statusLabel,
                ],
            ]);

            self::toUser($user, 'payment_failed', [
                'subject_line' => 'Payment ' . $statusLabel . ' - TiT Education',
                'heading'      => 'Payment ' . $statusLabel,
                'icon'         => '💳',
                'color'        => '#ef4444',
                'greeting'     => 'Dear ' . self::name($user) . ',',
                'message'      => 'Your online payment of LKR ' . $amount . ' was not completed. Please log in and try again, or choose offline payment. No money was taken if the payment failed.',
                'button_url'   => self::appUrl('/'),
                'button_text'  => 'Retry Payment',
            ]);
        });
    }

    public static function webhookError(string $title, string $detail, array $context = []): void
    {
        self::defer(function () use ($title, $detail, $context) {
            // Avoid mail floods if PayHere keeps retrying
            $key = 'webhook_error:' . md5($title . $detail);
            if (!Cache::add($key, 1, now()->addMinutes(30))) {
                return;
            }
            self::toAdmin('system_alert', [
                'subject_line' => 'System Alert: ' . $title,
                'heading'      => $title,
                'icon'         => '🛑',
                'color'        => '#dc2626',
                'message'      => $detail,
                'details'      => array_map(fn($v) => is_scalar($v) ? (string) $v : json_encode($v), $context),
            ]);
        });
    }

    public static function jobFailed(string $job, \Throwable $e): void
    {
        // At most one mail per job per hour
        if (!Cache::add('job_failed:' . md5($job), 1, now()->addHour())) {
            return;
        }
        self::toAdmin('system_alert', [
            'subject_line' => 'Scheduled Job Failed: ' . $job,
            'heading'      => 'Scheduled Job Failed',
            'icon'         => '🛑',
            'color'        => '#dc2626',
            'message'      => 'A scheduled background job failed and may need your attention.',
            'details'      => [
                'Job'     => $job,
                'Time'    => self::fmt(now()),
                'Error'   => mb_substr($e->getMessage(), 0, 300),
                'File'    => basename($e->getFile()) . ':' . $e->getLine(),
            ],
        ]);
    }

    public static function bounceBlocklisted(string $email, int $count): void
    {
        self::defer(fn() => self::toAdmin('system_alert', [
            'subject_line' => 'Email Address Blocklisted: ' . $email,
            'heading'      => 'Email Blocklisted',
            'icon'         => '📭',
            'color'        => '#f59e0b',
            'message'      => 'This email address kept bouncing and will no longer receive emails. Please contact the student/teacher and update their email address.',
            'details'      => [
                'Email'   => $email,
                'Bounces' => $count,
                'Owner'   => optional(User::where('email', $email)->first())->full_name
                              ?? optional(User::where('email', $email)->first())->name,
            ],
        ]));
    }

    // ------------------------------------------------------------------
    // Wishes
    // ------------------------------------------------------------------

    public static function birthday(User $student): bool
    {
        return self::toUser($student, 'birthday', [
            'subject_line' => 'Happy Birthday ' . self::name($student) . '! 🎂',
            'heading'      => 'Happy Birthday!',
            'icon'         => '🎂',
            'color'        => '#ec4899',
            'greeting'     => 'Dear ' . self::name($student) . ',',
            'message'      => "Wishing you a wonderful birthday full of joy and success!\n\nFrom all of us at TiT Education - keep learning, keep shining. 🌟",
        ]);
    }

    public static function wishes(User $student, string $title, string $message): bool
    {
        return self::toUser($student, 'festival_wishes', [
            'subject_line' => $title . ' - TiT Education',
            'heading'      => $title,
            'icon'         => '🎉',
            'color'        => '#f59e0b',
            'greeting'     => 'Dear ' . self::name($student) . ',',
            'message'      => $message,
        ]);
    }

    // ------------------------------------------------------------------
    // Attendance statistics (used by the absent-alert command)
    // ------------------------------------------------------------------

    public static function attendedIds(int $scheduleId): array
    {
        return Attendance::where('zoom_schedule_id', $scheduleId)
            ->where('status', 'present')
            ->pluck('user_id')
            ->all();
    }
}
