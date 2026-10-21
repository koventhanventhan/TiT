<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\Subject;
use App\Models\ZoomSchedule;
use Illuminate\Support\Facades\Cache;

/**
 * Public Knowledge Context Builder.
 *
 * Compiles real institute information, subject offerings, timetables, FAQs,
 * and contact settings into a cached markdown context for the assistant prompt.
 */
class PublicKnowledgeService
{
    protected FeeCalculator $feeCalculator;

    public function __construct(FeeCalculator $feeCalculator)
    {
        $this->feeCalculator = $feeCalculator;
    }

    /**
     * Get the cache key for an institute's public knowledge.
     */
    public function getCacheKey(int $instituteId): string
    {
        return "assistant_public_knowledge_inst_{$instituteId}";
    }

    /**
     * Clear cached knowledge for an institute.
     */
    public function clearCache(int $instituteId = 1): void
    {
        Cache::forget($this->getCacheKey($instituteId));
    }

    /**
     * Build or retrieve cached public knowledge context.
     */
    public function getContext(int $instituteId = 1): string
    {
        $ttl = (int) config('rag.assistant.public_cache_ttl', 300);

        return Cache::remember($this->getCacheKey($instituteId), $ttl, function () use ($instituteId) {
            return $this->compileContext($instituteId);
        });
    }

    /**
     * Compile real database records into knowledge context.
     */
    public function compileContext(int $instituteId = 1): string
    {
        $institute = Institute::find($instituteId);
        $instituteName = $institute ? $institute->name : 'TiT Education';

        $sections = [];

        // 1. Institute Header
        $sections[] = "# {$instituteName} - Public Information\n" .
            "You are the official website assistant for {$instituteName}. " .
            "Provide helpful, concise, and courteous information to prospective students, parents, and visitors.";

        // 2. Official Contact Information (dedicated assistant_public_contact group ONLY)
        $phone = SiteSetting::get('assistant_public_contact_phone');
        $whatsapp = SiteSetting::get('assistant_public_contact_whatsapp');
        $email = SiteSetting::get('assistant_public_contact_email');
        $address = SiteSetting::get('assistant_public_contact_address');
        $hours = SiteSetting::get('assistant_public_contact_hours');

        $hasContact = !empty(trim($phone ?? '')) || !empty(trim($whatsapp ?? '')) ||
            !empty(trim($email ?? '')) || !empty(trim($address ?? '')) || !empty(trim($hours ?? ''));

        $contactSection = "## Official Contact Details\n";
        if ($hasContact) {
            if (!empty($phone)) $contactSection .= "- Phone: {$phone}\n";
            if (!empty($whatsapp)) $contactSection .= "- WhatsApp: {$whatsapp}\n";
            if (!empty($email)) $contactSection .= "- Email: {$email}\n";
            if (!empty($address)) $contactSection .= "- Address: {$address}\n";
            if (!empty($hours)) $contactSection .= "- Operating Hours: {$hours}\n";
        } else {
            $contactSection .= "For inquiries, please visit our official Contact page at /contact.\n";
        }
        $contactSection .= "- Note: For further assistance, direct users to the Contact page at /contact.\n";
        $sections[] = trim($contactSection);

        // 3. How to Register
        $sections[] = "## Student Registration & Enrollment\n" .
            "- Prospective students can enroll online by visiting /register.\n" .
            "- Registration Steps:\n" .
            "  1. Go to /register.\n" .
            "  2. Select your Grade, Medium (Tamil, Sinhala, English), and Stream (for A/L).\n" .
            "  3. Choose your individual subjects or select a discounted package bundle.\n" .
            "  4. Complete student contact details and submit the admission fee.\n" .
            "- Once approved by the administrator, students log in and access live classes, learning notes, past papers, and monthly fee payments directly inside /student/dashboard.\n" .
            "- Important: Never link to or mention /student/payment.";

        // 4. Subject Offerings (Source of truth: subjects table)
        $subjects = Subject::where('status', 'active')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $subjSection = "## Curriculum & Subjects Offered\n";
        if ($subjects->isEmpty()) {
            $subjSection .= "Subject details are available on the Classes page at /classes.\n";
        } else {
            $byCat = $subjects->groupBy('category');
            foreach ($byCat as $cat => $items) {
                $subjNames = $items->pluck('name')->unique()->implode(', ');
                $subjSection .= "- " . ucfirst(str_replace('_', ' ', $cat)) . ": {$subjNames}\n";
            }
        }
        $sections[] = trim($subjSection);

        // 5. Timetable & Upcoming Classes
        $schedules = ZoomSchedule::where('scheduled_at', '>=', now()->subDays(1))
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        $scheduleSection = "## Upcoming Class Schedule Summary\n";
        if ($schedules->isEmpty()) {
            $scheduleSection .= "Regular weekly Zoom classes are held according to term schedules. Enrolled students view full schedules inside /student/dashboard.\n";
        } else {
            foreach ($schedules as $sch) {
                $time = $sch->scheduled_at ? $sch->scheduled_at->format('l, M j at g:i A') : 'Scheduled';
                $scheduleSection .= "- {$sch->title} ({$sch->grade}, {$sch->medium}): {$time} ({$sch->duration} mins)\n";
            }
        }
        $sections[] = trim($scheduleSection);

        // 6. Fee Schedule from FeeCalculator
        $sections[] = "## Fee Schedule\n" . $this->feeCalculator->formatFeeContextForPrompt($instituteId);

        // 7. Active FAQs
        $faqs = Faq::where('is_active', true)
            ->where(function ($q) use ($instituteId) {
                $q->where('institute_id', $instituteId)->orWhereNull('institute_id');
            })
            ->orderBy('sort_order')
            ->get();

        if ($faqs->isNotEmpty()) {
            $faqSection = "## Frequently Asked Questions (Official Answers)\n";
            foreach ($faqs as $faq) {
                $faqSection .= "Q: {$faq->question}\n";
                $faqSection .= "A: {$faq->answer}\n\n";
            }
            $sections[] = trim($faqSection);
        }

        return implode("\n\n", $sections);
    }
}
