<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\PublicKnowledgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFaqController extends Controller
{
    protected PublicKnowledgeService $knowledgeService;

    public function __construct(PublicKnowledgeService $knowledgeService)
    {
        $this->knowledgeService = $knowledgeService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $instituteId = $user->institute_id ?? 1;

        $query = Faq::where(function ($q) use ($instituteId) {
            $q->where('institute_id', $instituteId)->orWhereNull('institute_id');
        });

        if ($request->has('language')) {
            $query->where('language', $request->input('language'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $faqs = $query->orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['data' => $faqs]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:5000',
            'language' => 'nullable|string|in:en,ta,si',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $instituteId = $user->institute_id ?? 1;

        $faq = Faq::create([
            'institute_id' => $instituteId,
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'language' => $validated['language'] ?? 'en',
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->knowledgeService->clearCache($instituteId);

        return response()->json([
            'message' => 'FAQ created successfully.',
            'data' => $faq,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $instituteId = $user->institute_id ?? 1;

        $faq = Faq::where('id', $id)
            ->where(function ($q) use ($instituteId) {
                $q->where('institute_id', $instituteId)->orWhereNull('institute_id');
            })
            ->firstOrFail();

        $validated = $request->validate([
            'question' => 'sometimes|required|string|max:1000',
            'answer' => 'sometimes|required|string|max:5000',
            'language' => 'sometimes|string|in:en,ta,si',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $faq->update($validated);
        $this->knowledgeService->clearCache($instituteId);

        return response()->json([
            'message' => 'FAQ updated successfully.',
            'data' => $faq,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $instituteId = $user->institute_id ?? 1;

        $faq = Faq::where('id', $id)
            ->where(function ($q) use ($instituteId) {
                $q->where('institute_id', $instituteId)->orWhereNull('institute_id');
            })
            ->firstOrFail();

        $faq->delete();
        $this->knowledgeService->clearCache($instituteId);

        return response()->json(['message' => 'FAQ deleted successfully.']);
    }
}
