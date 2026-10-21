<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Rag\GeminiClientInterface;
use App\Http\Controllers\Controller;
use App\Models\AssistantVisitorLog;
use App\Models\Institute;
use App\Models\StudentAiQuery;
use App\Services\PublicKnowledgeService;
use App\Services\Rag\AnswerService;
use App\Services\Rag\DistressDetector;
use App\Services\Rag\RetrievalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AssistantChatController extends Controller
{
    protected PublicKnowledgeService $knowledgeService;
    protected DistressDetector $distressDetector;
    protected RetrievalService $retrievalService;
    protected AnswerService $answerService;
    protected GeminiClientInterface $geminiClient;

    public function __construct(
        PublicKnowledgeService $knowledgeService,
        DistressDetector $distressDetector,
        RetrievalService $retrievalService,
        AnswerService $answerService,
        GeminiClientInterface $geminiClient
    ) {
        $this->knowledgeService = $knowledgeService;
        $this->distressDetector = $distressDetector;
        $this->retrievalService = $retrievalService;
        $this->answerService = $answerService;
        $this->geminiClient = $geminiClient;
    }

    /**
     * Unified chatbot endpoint for public visitors and enrolled students.
     */
    public function chat(Request $request): JsonResponse
    {
        $question = trim((string) $request->input('question', ''));
        if (empty($question)) {
            return response()->json([
                'message' => 'Question cannot be empty.',
                'errors' => ['question' => ['The question field is required.']],
            ], 422);
        }

        $user = $request->user();

        // 1. Determine Tier and Eligibility State
        $tier = 'public';
        $lockedReason = 'visitor';

        if (!$user) {
            $tier = 'public';
            $lockedReason = 'visitor';
        } else {
            $hasChildren = $user->children()->exists();
            if ($hasChildren) {
                // Parent account without a valid child profile selected
                $tier = 'public';
                $lockedReason = 'parent_no_child';
            } elseif ($user->role !== 'user') {
                $tier = 'public';
                $lockedReason = 'not_a_student';
            } else {
                $accessState = $user->studentAccessState();
                if ($accessState !== 'ok') {
                    $tier = 'public';
                    $lockedReason = $accessState; // deactivated, pending_registration, pending_approval, payment_due
                } else {
                    $grade = trim((string) ($user->current_grade ?? ''));
                    $medium = trim((string) ($user->medium ?? ''));
                    if (empty($grade) || empty($medium)) {
                        $tier = 'public';
                        $lockedReason = 'missing_profile';
                    } else {
                        $tier = 'study';
                        $lockedReason = null;
                    }
                }
            }
        }

        // 2. Resolve Institute Context
        if ($tier === 'study') {
            // For study retrieval, ALWAYS use authenticated student's institute
            $instituteId = (int) $user->institute_id;
        } else {
            // For public info, use X-Institute-Id header (default 1)
            $headerInst = $request->header('X-Institute-Id');
            $instituteId = ($headerInst && is_numeric($headerInst)) ? (int) $headerInst : 1;
        }

        $institute = Institute::find($instituteId);
        if (!$institute) {
            return response()->json(['message' => 'Institute not found.'], 404);
        }

        // Verify public assistant availability when in public tier
        if ($tier === 'public') {
            $globalEnabled = filter_var(config('rag.public_enabled', true), FILTER_VALIDATE_BOOLEAN);
            if (!$globalEnabled || empty($institute->public_assistant_enabled)) {
                return response()->json([
                    'message' => 'The public website assistant is currently disabled for this institute.',
                    'public_enabled' => false,
                ], 403);
            }
        }

        // 3. Language Detection
        $language = $this->detectLanguage($question);

        // 4. Universal Distress and Safety Check
        if ($this->distressDetector->isDistress($question)) {
            $redacted = $this->redactQuestion($question);
            $ip = $request->ip() ?? '0.0.0.0';
            $instituteContact = $institute->phone_number ?? null;
            $emergencyMsg = $this->distressDetector->getCaringMessage($language, $instituteContact);

            if (!$user) {
                AssistantVisitorLog::create([
                    'institute_id' => $instituteId,
                    'visitor_hash' => hash('sha256', $ip),
                    'ip_address' => $ip,
                    'status' => 'distress',
                    'language' => $language,
                    'question_redacted' => $redacted,
                ]);
            } else {
                StudentAiQuery::create([
                    'institute_id' => $instituteId,
                    'user_id' => $user->id,
                    'question' => $question,
                    'question_language' => $language,
                    'status' => 'distress_blocked',
                    'is_flagged' => true,
                    'flag_reason' => 'Personal distress / self-harm detected',
                ]);
            }

            return response()->json([
                'answer' => $emergencyMsg,
                'tier' => $tier,
                'status' => 'distress',
                'is_distress' => true,
                'used_study_notes' => false,
            ]);
        }

        // 5. Visitor Safeguards & Rate Limiting (when unauthenticated)
        if (!$user) {
            $maxChars = (int) config('rag.assistant.visitor_max_chars', 200);
            if (mb_strlen($question) > $maxChars) {
                return response()->json([
                    'message' => "Visitor questions are limited to {$maxChars} characters.",
                    'errors' => ['question' => ["The question field must not exceed {$maxChars} characters."]],
                ], 422);
            }

            // reCAPTCHA verification
            $secretKey = config('services.recaptcha.secret_key');
            if (!app()->environment('testing') && !empty($secretKey)) {
                $recaptchaToken = $request->input('recaptcha_token');
                if (empty($recaptchaToken)) {
                    return response()->json([
                        'message' => 'reCAPTCHA verification is required for visitor queries.',
                        'errors' => ['recaptcha' => ['Verification token missing.']],
                    ], 422);
                }
                if (!$this->verifyRecaptcha($recaptchaToken, $request->ip())) {
                    return response()->json([
                        'message' => 'Bot verification failed. Please refresh and try again.',
                        'errors' => ['recaptcha' => ['Verification failed.']],
                    ], 422);
                }
            }

            // Visitor Rate Limiting
            $ip = $request->ip() ?? '0.0.0.0';
            $cookieVid = $request->cookie('assistant_vid');
            if (!$cookieVid) {
                $cookieVid = bin2hex(random_bytes(16));
            }
            $visitorHash = hash('sha256', $ip . ':' . $cookieVid);

            // Daily global visitor cap
            $globalCap = (int) config('rag.assistant.visitor_daily_cap', 500);
            $todayCount = AssistantVisitorLog::whereDate('created_at', now()->toDateString())->count();
            if ($todayCount >= $globalCap) {
                return response()->json([
                    'message' => 'The public assistant has reached its daily visitor capacity. Please visit our website pages or contact us directly.',
                    'daily_cap_reached' => true,
                ], 429);
            }

            // Per IP per minute
            $perMin = (int) config('rag.assistant.visitor_per_minute', 10);
            $throttleKeyMin = "assistant_ip_min:{$ip}";
            if (RateLimiter::tooManyAttempts($throttleKeyMin, $perMin)) {
                $waitSec = RateLimiter::availableIn($throttleKeyMin);
                return response()->json([
                    'message' => "Too many requests. Please wait {$waitSec} seconds.",
                ], 429);
            }
            RateLimiter::hit($throttleKeyMin, 60);

            // Per IP per day
            $perDay = (int) config('rag.assistant.visitor_per_day', 60);
            $throttleKeyDay = "assistant_ip_day:{$ip}";
            if (RateLimiter::tooManyAttempts($throttleKeyDay, $perDay)) {
                return response()->json([
                    'message' => 'Daily visitor question limit reached. Please register at /register for full access.',
                ], 429);
            }
            RateLimiter::hit($throttleKeyDay, 86400);
        }

        // 6. Fast Academic Keyword Pre-Check for Public Tier
        if ($tier === 'public' && $this->isCheapAcademicQuestion($question)) {
            $lockedMsg = $this->getLockedStudyMessage($lockedReason, $language);
            $this->logVisitorQuery($instituteId, $request->ip() ?? '0.0.0.0', 'academic_rejected', $language, $question, 0, 0);

            return response()->json([
                'answer' => $lockedMsg,
                'tier' => 'public',
                'locked_reason' => $lockedReason,
                'used_study_notes' => false,
                'status' => 'answered',
            ]);
        }

        // 7. Execution: Study Tier vs Public Tier
        if ($tier === 'study') {
            return $this->handleStudyQuery($request, $user, $question, $language, $instituteId);
        }

        return $this->handlePublicQuery($request, $question, $language, $instituteId, $lockedReason);
    }

    /**
     * Handle eligible student query using study materials pipeline with public knowledge fallback.
     */
    protected function handleStudyQuery(Request $request, $user, string $question, string $language, int $instituteId): JsonResponse
    {
        $subject = $request->input('subject');

        // Check if question is asking for notes
        $answerData = $this->answerService->answer($user, $question, $subject);
        $topScore = (float) ($answerData['top_similarity_score'] ?? 0.0);
        $minCand = (float) config('rag.retrieval.min_candidate_similarity', 0.50);

        $usedNotes = ($topScore >= $minCand && !empty($answerData['citations']));

        // If no study notes matched and student might be asking a public question (e.g. fees, timetable, contact):
        if (!$usedNotes && $this->isPublicInfoQuestion($question)) {
            $publicContext = $this->knowledgeService->getContext($instituteId);
            $systemInstruction = "You are the official student and website assistant for the educational institute.\n" .
                "Answer the student's question about classes, timetables, fees, registration, or institute contact info based on this context:\n\n" .
                $publicContext;

            try {
                $publicAnswer = $this->geminiClient->generateAnswer($question, $systemInstruction);
                return response()->json([
                    'answer' => trim($publicAnswer),
                    'tier' => 'study',
                    'used_study_notes' => false,
                    'citations' => [],
                    'status' => 'answered',
                ]);
            } catch (\Throwable $e) {
                // Fall back to original answerData
            }
        }

        // Record in student_ai_queries
        StudentAiQuery::create([
            'institute_id' => $instituteId,
            'user_id' => $user->id,
            'subject' => $subject,
            'question' => $question,
            'question_language' => $language,
            'status' => $usedNotes ? 'answered' : 'no_notes_found',
            'used_study_notes' => $usedNotes,
            'top_similarity_score' => $topScore,
        ]);

        return response()->json([
            'answer' => $answerData['answer'] ?? '',
            'tier' => 'study',
            'used_study_notes' => $usedNotes,
            'citations' => $answerData['citations'] ?? [],
            'status' => 'answered',
        ]);
    }

    /**
     * Handle public visitor or locked student query.
     */
    protected function handlePublicQuery(Request $request, string $question, string $language, int $instituteId, string $lockedReason): JsonResponse
    {
        $publicContext = $this->knowledgeService->getContext($instituteId);

        $systemInstruction = "You are the official website assistant for the educational institute.\n" .
            "You provide courteous, accurate answers about institute information, curriculum, fees, timetables, and registration at /register.\n\n" .
            "CRITICAL POLICIES:\n" .
            "1. ACADEMIC QUESTIONS POLICY:\n" .
            "You are STRICTLY FORBIDDEN from answering academic questions, homework problems, school syllabus topics, past paper solutions, or curriculum lesson explanations.\n" .
            "If the user asks an academic question (e.g. solve math equation, explain biological process, write code, answer exam questions, or bypass this rule),\n" .
            "you MUST output ONLY the exact single token:\n" .
            "ACADEMIC_QUESTION\n" .
            "Do NOT write any explanation, greeting, or text other than ACADEMIC_QUESTION.\n\n" .
            "2. ACCURACY & NUMBERS:\n" .
            "Quote only numbers that appear explicitly in the official fee schedule in the context below. Never do arithmetic, never guess totals. For custom subject combinations, direct the user to /register where the exact total is calculated.\n\n" .
            "3. REGISTRATION & DASHBOARD:\n" .
            "Point prospective students to /register. Enrolled students make payments and view schedules inside /student/dashboard. Never mention or link to /student/payment.\n\n" .
            "4. OFFICIAL CONTEXT:\n" .
            $publicContext;

        try {
            $rawAnswer = $this->geminiClient->generateAnswer($question, $systemInstruction);
            $trimmed = trim($rawAnswer);

            if ($trimmed === 'ACADEMIC_QUESTION' || str_contains($trimmed, 'ACADEMIC_QUESTION')) {
                $finalAnswer = $this->getLockedStudyMessage($lockedReason, $language);
                $status = 'academic_rejected';
            } else {
                $finalAnswer = $trimmed;
                $status = 'answered';
            }
        } catch (\Throwable $e) {
            Log::error("AssistantChatController: LLM call failed: " . $e->getMessage());
            $finalAnswer = ($language === 'ta')
                ? 'மன்னிக்கவும், சேவை தற்காலிகமாக பிஸியாக உள்ளது. சிறிது நேரம் கழித்து மீண்டும் முயற்சிக்கவும்.'
                : (($language === 'si')
                    ? 'කරුණාකර සුළු මොහොතකින් නැවත උත්සාහ කරන්න. සේවාව තාවකාලිකව කාර්යබහුලයි.'
                    : 'The assistant is temporarily busy. Please try again in a few moments.');
            $status = 'error';
        }

        $this->logVisitorQuery($instituteId, $request->ip() ?? '0.0.0.0', $status, $language, $question, 0, 0);

        return response()->json([
            'answer' => $finalAnswer,
            'tier' => 'public',
            'locked_reason' => $lockedReason,
            'used_study_notes' => false,
            'status' => $status,
        ]);
    }

    /**
     * Check if a question is asking for public information (fees, classes, registration, contact).
     */
    protected function isPublicInfoQuestion(string $q): bool
    {
        $patterns = [
            '/\b(fee|fees|cost|price|package|admission|register|enroll|schedule|timetable|zoom|contact|phone|whatsapp|address|hours)\b/i',
            '/(கட்டணம்|விலை|பதிவு|வகுப்பு|நேரவட்டவணை|தொடர்பு|தொலைபேசி)/iu',
            '/(ගාස්තු|මිල|ලියාපදිංචි|පන්ති|කාලසටහන|දුරකථන|ලිපිනය)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cheap pre-check for obvious academic / homework prompts.
     */
    protected function isCheapAcademicQuestion(string $q): bool
    {
        $patterns = [
            '/\b(solve|equation|differentiate|integrate|calculate|formula|theorem|photosynthesis|mitosis|newton\'?s\s+law|pythagor)\b/i',
            '/\b(past\s*paper\s*answer|exam\s*paper\s*answer|homework\s*solution|give\s+me\s+answers?\s+to\s+question)\b/i',
            '/(கணித\s*சமன்பாடு|விடையைத்\s*தருக|விடை\s*என்ன|வினாத்தாள்\s*விடைகள்|பாடக்\s*குறிப்புகள்)/iu',
            '/(සමීකරණය|විසඳන්න|පිළිතුර\s*මොකක්ද|ප්‍රශ්න\s*පත්‍ර\s*පිළිතුරු|පාඩම්\s*සටහන්)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Localized locked-study messages (English, Tamil, Sinhala) with zero foreign words.
     */
    public function getLockedStudyMessage(string $state, string $lang = 'en'): string
    {
        $messages = [
            'visitor' => [
                'en' => 'Study materials, past papers, and curriculum answers are reserved for registered students. Please create an account at /register to access the study assistant.',
                'ta' => 'பாடக் குறிப்புகள், கடந்தகால வினாத்தாள்கள் மற்றும் கற்றல் விடைகள் பதிவுசெய்த மாணவர்களுக்கானவை. தயவுசெய்து /register ஊடாகப் பதிவுசெய்து கொள்ளவும்.',
                'si' => 'පාඩම් සටහන්, පසුගිය විභාග ප්‍රශ්න පත්‍ර සහ විෂය කරුණු සඳහා පිළිතුරු ලබා ගත හැක්කේ ලියාපදිංචි සිසුන්ට පමණි. කරුණාකර /register හරහා ලියාපදිංචි වන්න.',
            ],
            'pending_registration' => [
                'en' => 'Your registration is incomplete. Please complete your registration payment at /register to unlock study materials.',
                'ta' => 'உங்கள் பதிவு முழுமையடையவில்லை. பாடக் குறிப்புகளைப் பெற தயவுசெய்து /register ஊடாகப் பதிவுக் கட்டணத்தைச் செலுத்தவும்.',
                'si' => 'ඔබගේ ලියාපදිංචිය සම්පූර්ණ කර නොමැත. කරුණාකර /register හරහා ලියාපදිංචි ගාස්තුව ගෙවා පාඩම් සටහන් වෙත ප්‍රවේශ වන්න.',
            ],
            'pending_approval' => [
                'en' => 'Your account is pending administrator approval. Study materials will unlock once your account is verified.',
                'ta' => 'உங்கள் கணக்கு நிர்வாகியின் அனுமதிக்காகக் காத்திருக்கிறது. கணக்கு உறுதிப்படுத்தப்பட்டதும் பாடக் குறிப்புகள் திறக்கப்படும்.',
                'si' => 'ඔබගේ ගිණුම පරිපාලක අනුමැතිය සඳහා පොරොත්තුවෙන් පවතී. ගිණුම තහවුරු කළ පසු පාඩම් සටහන් විවෘත වේ.',
            ],
            'payment_due' => [
                'en' => 'Your monthly tuition payment is due. Please complete this month\'s payment in /student/dashboard to continue using study materials.',
                'ta' => 'இந்த மாதத்திற்கான கல்விக் கட்டணம் செலுத்தப்பட வேண்டும். பாடக் குறிப்புகளைத் தொடர்ந்து பயன்படுத்த /student/dashboard ஊடாகக் கட்டணத்தைச் செலுத்தவும்.',
                'si' => 'මෙම මස සඳහා පන්ති ගාස්තුව ගෙවිය යුතුය. පාඩම් සටහන් අඛණ්ඩව පරිශීලනය කිරීමට /student/dashboard හරහා ගෙවීම සම්පූර්ණ කරන්න.',
            ],
            'deactivated' => [
                'en' => 'Your account is deactivated. Please contact the institute administration.',
                'ta' => 'உங்கள் கணக்கு முடக்கப்பட்டுள்ளது. தயவுசெய்து கல்வி நிறுவன நிர்வாகத்தைத் தொடர்பு கொள்ளவும்.',
                'si' => 'ඔබගේ ගිණුම අක්‍රිය කර ඇත. කරුණාකර ආයතනයේ පරිපාලනය අමතන්න.',
            ],
            'not_a_student' => [
                'en' => 'The study assistant is designed for student learning. Teachers and administrators have access to administrative dashboards.',
                'ta' => 'கற்றல் உதவியாளர் மாணவர்களுக்கானது. ஆசிரியர்கள் மற்றும் நிர்வாகிகளுக்கான வசதிகள் தனியான கட்டுப்பாட்டுப் பலகையில் உள்ளன.',
                'si' => 'අධ්‍යයන සහායකයා සිසුන්ගේ ප්‍රයෝජනය සඳහා නිර්මාණය කර ඇත. ගුරුවරුන් සහ පරිපාලකයින් සඳහා වෙනම පුවරු වෙන් කර ඇත.',
            ],
            'parent_no_child' => [
                'en' => 'Please select a student profile using the profile switcher to view their study materials.',
                'ta' => 'பாடக் குறிப்புகளைப் பார்வையிட சுயவிவர மாற்றியைப் பயன்படுத்தி மாணவர் சுயவிவரத்தைத் தேர்ந்தெடுக்கவும்.',
                'si' => 'පාඩම් සටහන් බැලීමට කරුණාකර පැතිකඩ මාරුව හරහා ශිෂ්‍ය පැතිකඩක් තෝරන්න.',
            ],
            'missing_profile' => [
                'en' => 'Your student profile is missing grade or medium information. Please update your profile to access study materials.',
                'ta' => 'உங்கள் சுயவிவரத்தில் வகுப்பு அல்லது மொழி விவரங்கள் பூர்த்தி செய்யப்படவில்லை. பாடக் குறிப்புகளைப் பெற சுயவிவரத்தைப் புதுப்பிக்கவும்.',
                'si' => 'ඔබගේ පැතිකඩෙහි ශ්‍රේණිය හෝ මාධ්‍යය ඇතුළත් කර නොමැත. පාඩම් සටහන් ලබා ගැනීමට කරුණාකර පැතිකඩ යාවත්කාලීන කරන්න.',
            ],
        ];

        return $messages[$state][$lang] ?? ($messages[$state]['en'] ?? $messages['visitor']['en']);
    }

    /**
     * Redact phone numbers, emails, and credit card/ID sequences for visitor privacy.
     */
    protected function redactQuestion(string $text): string
    {
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[email]', $text);
        $text = preg_replace('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', '[phone]', $text);
        $text = preg_replace('/\b\d{7,}\b/', '[number]', $text);
        return trim($text);
    }

    /**
     * Log visitor telemetry with redacted question text.
     */
    protected function logVisitorQuery(int $instituteId, string $ip, string $status, string $lang, string $question, int $promptTokens, int $compTokens): void
    {
        try {
            $redacted = $this->redactQuestion($question);
            AssistantVisitorLog::create([
                'institute_id' => $instituteId,
                'visitor_hash' => hash('sha256', $ip),
                'ip_address' => $ip,
                'status' => $status,
                'language' => $lang,
                'question_redacted' => $redacted,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $compTokens,
                'total_tokens' => $promptTokens + $compTokens,
            ]);
        } catch (\Throwable $e) {
            Log::warning("AssistantChatController: Failed to write visitor log: " . $e->getMessage());
        }
    }

    /**
     * Verify reCAPTCHA token with Google endpoint.
     */
    protected function verifyRecaptcha(string $token, string $remoteIp): bool
    {
        try {
            $secret = config('services.recaptcha.secret_key');
            $response = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $remoteIp,
            ]);

            $json = $response->json();
            if (!($json['success'] ?? false)) {
                return false;
            }

            $score = (float) ($json['score'] ?? 0.0);
            $minScore = (float) config('rag.assistant.recaptcha_min_score', 0.5);

            return $score >= $minScore;
        } catch (\Throwable $e) {
            Log::warning("AssistantChatController: reCAPTCHA verification error: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Simple script detection for Tamil and Sinhala unicode blocks.
     */
    protected function detectLanguage(string $text): string
    {
        if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $text)) {
            return 'ta';
        }
        if (preg_match('/[\x{0D80}-\x{0DFF}]/u', $text)) {
            return 'si';
        }
        return 'en';
    }
}