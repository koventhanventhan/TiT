<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ZoomSchedule;
use App\Models\Timetable;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationMail;

class ClassCreationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_created_notification_logic()
    {
        Mail::fake();

        // Admin
        User::factory()->create(['role' => 'admin', 'email' => 'admin@tit.com']);

        // Teacher
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@example.com',
        ]);

        // Matching student
        $matchingStudent = User::factory()->create([
            'role' => 'user',
            'email' => 'student1@example.com',
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics', 'Science']),
            'phone_number' => '0771234567',
        ]);

        // Non-matching student (different grade)
        $diffGradeStudent = User::factory()->create([
            'role' => 'user',
            'email' => 'student2@example.com',
            'current_grade' => 'Grade 9',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics']),
        ]);

        // Non-matching student (different medium)
        $diffMediumStudent = User::factory()->create([
            'role' => 'user',
            'email' => 'student3@example.com',
            'current_grade' => 'Grade 10',
            'medium' => 'tamil',
            'selected_subjects' => json_encode(['Mathematics']),
        ]);

        // Non-matching student (different subject)
        $diffSubjectStudent = User::factory()->create([
            'role' => 'user',
            'email' => 'student4@example.com',
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['History']),
        ]);

        // Non-matching student (invalid email, no phone)
        $invalidEmailStudent = User::factory()->create([
            'role' => 'user',
            'email' => 'invalid@student.local',
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics']),
            'phone_number' => null,
        ]);

        // Act: Use the ClassNotifier directly to test the logic
        $notifier = app(\App\Services\ClassNotifier::class);
        $students = $notifier->getMatchingStudents('Grade 10', 'english', 'Mathematics');
        $studentIds = $students->pluck('id')->toArray();

        // Assert matches
        $this->assertContains($matchingStudent->id, $studentIds);
        $this->assertNotContains($diffGradeStudent->id, $studentIds);
        $this->assertNotContains($diffMediumStudent->id, $studentIds);
        $this->assertNotContains($diffSubjectStudent->id, $studentIds);
        $this->assertNotContains($invalidEmailStudent->id, $studentIds);

        // Act: Send emails
        $notifier->sendClassCreatedEmails(
            'Test Math Class',
            'Monday @ 10:00',
            'Mathematics',
            'Grade 10',
            'http://zoom.us/j/123',
            [$teacher->id],
            $studentIds
        );

        // Assert emails were sent correctly
        Mail::assertSent(NotificationMail::class, function ($mail) use ($teacher, $matchingStudent) {
            $hasTeacher = $mail->hasTo($teacher->email);
            $hasStudent = $mail->hasTo($matchingStudent->email);
            return ($hasTeacher || $hasStudent) && $mail->type === 'class_created';
        });

        Mail::assertNotSent(NotificationMail::class, function ($mail) use ($invalidEmailStudent) {
            return $mail->hasTo($invalidEmailStudent->email);
        });
    }
}
