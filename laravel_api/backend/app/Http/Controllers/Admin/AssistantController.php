<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\IngestLearningMaterialJob;
use App\Models\Faq;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\SiteSetting;
use App\Models\StudentAiQuery;
use App\Services\ActivityLogService;
use App\Services\PublicAssistantStatusService;
use App\Services\PublicKnowledgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantController extends Controller
{
    protected PublicAssistantStatusService $statusService;
    protected PublicKnowledgeService $knowledgeService;

    public function __construct(
        PublicAssistantStatusService $statusService,
        PublicKnowledgeService $knowledgeService
    ) {
        $this->statusService = $statusService;
        $this->knowledgeService = $knowledgeService;
    }

    /**
     * Resolve the current authenticated institute.
     */
    protected function resolveInstitute(): Institute
    {
        $user = auth()->user();
        $institute = Institute::current()
            ?? ($user ? $user->institute : null)
            ?? Institute::find($user?->institute_id ?: 1);

        if (!$institute) {
            abort(404, 'Institute not found.');
        }

        return $institute;
    }

    /**
     * Display the AI Assistant management page.
     */
    public function index(Request $request): View
    {
        $institute = $this->resolveInstitute();
        $instituteId = (int) $institute->id;

        // 1. Status evaluation via shared predicate
        $status = $this->statusService->check($institute);

        // 2. Official contact details
        $contacts = [
            'phone' => SiteSetting::getForInstitute('assistant_public_contact_phone', $instituteId, ''),
            'whatsapp' => SiteSetting::getForInstitute('assistant_public_contact_whatsapp', $instituteId, ''),
            'email' => SiteSetting::getForInstitute('assistant_public_contact_email', $instituteId, ''),
            'address' => SiteSetting::getForInstitute('assistant_public_contact_address', $instituteId, ''),
            'hours' => SiteSetting::getForInstitute('assistant_public_contact_hours', $instituteId, ''),
        ];

        // 3. FAQ manager (this institute only)
        $faqs = Faq::where('institute_id', $instituteId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // 4. Knowledge status & materials telemetry
        $materialsQuery = LearningMaterial::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->whereNotNull('file_path')
            ->where(function ($q) {
                $q->where('type', '!=', 'recording')
                  ->orWhereNull('zoom_schedule_id');
            });

        $statusCounts = (clone $materialsQuery)
            ->selectRaw('COALESCE(rag_status, "pending") as status_key, count(*) as count')
            ->groupBy('status_key')
            ->pluck('count', 'status_key')
            ->toArray();

        $failedMaterials = (clone $materialsQuery)
            ->where('rag_status', 'failed')
            ->select('id', 'title', 'subject', 'grade', 'institute_id', 'rag_error', 'updated_at')
            ->latest('updated_at')
            ->limit(20)
            ->get();

        // 5. Last 7 days query volume and quality rates
        $queriesQuery = StudentAiQuery::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('created_at', '>=', now()->subDays(7));

        $totalQueries = (clone $queriesQuery)->count();
        $answeredCount = (clone $queriesQuery)->where('status', 'answered')->count();
        $noNotesFoundCount = (clone $queriesQuery)->where('status', 'no_notes_found')->count();
        $ratedCount = (clone $queriesQuery)->whereNotNull('feedback_rating')->count();
        $thumbsDownCount = (clone $queriesQuery)->where('feedback_rating', 'down')->count();
        $safetyBlockCount = (clone $queriesQuery)->where(function ($q) {
            $q->where('status', 'blocked_safety')->orWhere('flag_reason', 'safety_block');
        })->count();

        $noNotesFoundRate = $totalQueries > 0 ? round(($noNotesFoundCount / $totalQueries) * 100, 1) : 0.0;
        $thumbsDownRate = $ratedCount > 0 ? round(($thumbsDownCount / $ratedCount) * 100, 1) : 0.0;

        $qualityRates = [
            'total_queries' => $totalQueries,
            'answered_count' => $answeredCount,
            'no_notes_found_count' => $noNotesFoundCount,
            'no_notes_found_rate_percent' => $noNotesFoundRate,
            'rated_queries_count' => $ratedCount,
            'thumbs_down_count' => $thumbsDownCount,
            'thumbs_down_rate_percent' => $thumbsDownRate,
            'safety_block_count' => $safetyBlockCount,
        ];

        return view('admin.assistant.index', [
            'institute' => $institute,
            'status' => $status,
            'contacts' => $contacts,
            'faqs' => $faqs,
            'materialsCounts' => [
                'ready' => $statusCounts['ready'] ?? 0,
                'processing' => $statusCounts['processing'] ?? 0,
                'failed' => $statusCounts['failed'] ?? 0,
                'unsupported' => $statusCounts['unsupported'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
                'total' => array_sum($statusCounts),
            ],
            'failedMaterials' => $failedMaterials,
            'qualityRates' => $qualityRates,
        ]);
    }

    /**
     * Toggle public assistant switch.
     */
    public function togglePublic(Request $request): JsonResponse|RedirectResponse
    {
        $institute = $this->resolveInstitute();
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $targetState = (bool) $validated['enabled'];

        if ($targetState) {
            $refusalReasons = [];
            if (!$this->statusService->canTurnOn($institute, $refusalReasons)) {
                $errorMessage = 'Refusing to enable public assistant: ' . implode('. ', $refusalReasons);
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['enabled' => $refusalReasons],
                    ], 422);
                }
                return back()->with('error', $errorMessage);
            }
        }

        $institute->update(['public_assistant_enabled' => $targetState]);
        ActivityLogService::log(
            'assistant_public_toggle',
            "Public assistant " . ($targetState ? 'enabled' : 'disabled') . " for institute #{$institute->id} ({$institute->name})",
            ['state' => $targetState ? 'on' : 'off', 'public_assistant_enabled' => $targetState],
            $institute->id
        );
        $this->knowledgeService->clearCache($institute->id);

        $msg = 'Public website assistant ' . ($targetState ? 'enabled successfully.' : 'disabled.');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'public_assistant_enabled' => $targetState,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Toggle study assistant switch (rag_enabled).
     */
    public function toggleStudy(Request $request): JsonResponse|RedirectResponse
    {
        $institute = $this->resolveInstitute();
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $targetState = (bool) $validated['enabled'];
        $institute->update(['rag_enabled' => $targetState]);

        ActivityLogService::log(
            'study_assistant_toggle',
            "Study assistant " . ($targetState ? 'enabled' : 'disabled') . " for institute #{$institute->id} ({$institute->name})",
            ['rag_enabled' => $targetState],
            $institute->id
        );

        $msg = 'Study assistant (RAG) ' . ($targetState ? 'enabled.' : 'disabled.');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'rag_enabled' => $targetState,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Update official contact details for this institute.
     */
    public function updateContacts(Request $request): JsonResponse|RedirectResponse
    {
        $institute = $this->resolveInstitute();
        $instituteId = (int) $institute->id;

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\s\-()]{7,30}$/'],
            'whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\s\-()]{7,30}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'hours' => ['nullable', 'string', 'max:200'],
        ], [
            'phone.regex' => 'The telephone number must contain 7-30 digits or phone characters (+, -, spaces, parentheses).',
            'whatsapp.regex' => 'The WhatsApp number must contain 7-30 digits or phone characters (+, -, spaces, parentheses).',
            'email.email' => 'Please provide a valid official e-mail address.',
        ]);

        foreach ($validated as $field => $value) {
            $key = "assistant_public_contact_{$field}";
            SiteSetting::setForInstitute($key, trim((string) $value), $instituteId, 'assistant_public_contact');
        }

        $this->knowledgeService->clearCache($instituteId);
        ActivityLogService::log(
            'assistant_contact_update',
            "Updated assistant public contact details for institute #{$instituteId}",
            $validated,
            $instituteId
        );

        $msg = 'Official contact details saved successfully.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Store new FAQ.
     */
    public function storeFaq(Request $request): JsonResponse|RedirectResponse
    {
        $institute = $this->resolveInstitute();
        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:5000',
            'language' => 'nullable|string|in:en,ta,si',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = Faq::create([
            'institute_id' => $institute->id,
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'language' => $validated['language'] ?? 'en',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active') ? (bool) $validated['is_active'] : true,
        ]);

        $this->knowledgeService->clearCache($institute->id);
        ActivityLogService::log('faq_created', "Created FAQ #{$faq->id} for institute #{$institute->id}", ['faq_id' => $faq->id], $institute->id);

        $msg = 'FAQ added successfully.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $faq,
            ], 201);
        }

        return back()->with('success', $msg);
    }

    /**
     * Update existing FAQ.
     */
    public function updateFaq(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $institute = $this->resolveInstitute();
        $faq = Faq::where('id', $id)
            ->where('institute_id', $institute->id)
            ->firstOrFail();

        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:5000',
            'language' => 'nullable|string|in:en,ta,si',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq->update([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'language' => $validated['language'] ?? $faq->language,
            'sort_order' => $validated['sort_order'] ?? $faq->sort_order,
            'is_active' => $request->has('is_active') ? (bool) $validated['is_active'] : $faq->is_active,
        ]);

        $this->knowledgeService->clearCache($institute->id);
        ActivityLogService::log('faq_updated', "Updated FAQ #{$faq->id} for institute #{$institute->id}", ['faq_id' => $faq->id], $institute->id);

        $msg = 'FAQ updated successfully.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $faq,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete an FAQ.
     */
    public function destroyFaq(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $institute = $this->resolveInstitute();
        $faq = Faq::where('id', $id)
            ->where('institute_id', $institute->id)
            ->firstOrFail();

        $faq->delete();
        $this->knowledgeService->clearCache($institute->id);
        ActivityLogService::log('faq_deleted', "Deleted FAQ #{$id} for institute #{$institute->id}", ['faq_id' => $id], $institute->id);

        $msg = 'FAQ deleted successfully.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Re-index a failed learning material.
     */
    public function reindexMaterial(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $institute = $this->resolveInstitute();
        $material = LearningMaterial::withoutGlobalScopes()
            ->where('id', $id)
            ->where('institute_id', $institute->id)
            ->firstOrFail();

        $material->update([
            'rag_status' => 'pending',
            'rag_error' => null,
        ]);

        IngestLearningMaterialJob::dispatch($material->id);
        ActivityLogService::log('material_reindexed', "Dispatched re-indexing job for material #{$material->id} ('{$material->title}')", ['material_id' => $material->id], $institute->id);

        $msg = "Material #{$material->id} ('{$material->title}') has been queued for re-indexing.";

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}

