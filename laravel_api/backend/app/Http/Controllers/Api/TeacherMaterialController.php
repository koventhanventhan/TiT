<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use Illuminate\Http\Request;

class TeacherMaterialController extends Controller
{
    public function index(Request $request)
    {
        $materials = LearningMaterial::where('teacher_id', $request->user()->id)
                        ->where(function ($q) { $q->where('type', '!=', 'recording')->orWhereNull('zoom_schedule_id'); })
                        ->orderBy('created_at', 'desc')
                        ->get();
        return response()->json($materials);
    }

    public function store(Request $request)
    {
        $type = strtolower($request->input('type', ''));
        $isStudyDoc = in_array($type, ['note', 'past_paper', 'paper', 'pdf', 'document'], true);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string', // note, past_paper, paper, recording, video, pdf, document, etc.
            'subject' => $isStudyDoc ? 'required|string|max:100' : 'nullable|string|max:100',
            'grade' => $isStudyDoc ? 'required|string|max:50' : 'nullable|string|max:50',
            'medium' => $isStudyDoc ? 'required|string|in:tamil,english,sinhala,all,both' : 'nullable|string|in:tamil,english,sinhala,all,both',
            'file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip,mp4,mov,mp3|max:51200',
            'file_path' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!\App\Services\FirebaseStorageUrlValidator::isValidFilePathOrUrl($value)) {
                        $fail('The ' . $attribute . ' is invalid.');
                    }
                },
            ],
            'file_size' => 'nullable|string',
            'url' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $filePath = $validated['file_path'] ?? null;
        $fileHash = null;
        $fileSize = $validated['file_size'] ?? null;

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $fileHash = hash_file('sha256', $uploadedFile->getRealPath());
            $filePath = $uploadedFile->store('materials', 'public');
            if (!$fileSize) {
                $fileSize = round($uploadedFile->getSize() / 1024 / 1024, 2) . ' MB';
            }
        } elseif (!empty($filePath) && !\Illuminate\Support\Str::startsWith($filePath, 'http')) {
            $localFullPath = storage_path('app/public/' . $filePath);
            if (file_exists($localFullPath)) {
                $fileHash = hash_file('sha256', $localFullPath);
            }
        }

        $instituteId = (int) ($request->user()->institute_id ?? 1);

        // Duplicate SHA-256 detection within institute
        $warning = null;
        if (!empty($fileHash)) {
            $existing = LearningMaterial::where('institute_id', $instituteId)
                ->where('file_hash', $fileHash)
                ->first();
            if ($existing) {
                $warning = "A file with identical content already exists in this institute: \"{$existing->title}\".";
            }
        }

        // Grade normalization on save (canonical grade format)
        $canonicalGrade = \App\Support\GradeMatcher::formatCanonical($validated['grade'] ?? null);

        // Map 'both' to canonical 'all' on store
        $mediumVal = $validated['medium'] ?? 'all';
        if ($mediumVal === 'both') {
            $mediumVal = 'all';
        }

        $material = LearningMaterial::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'subject' => $validated['subject'] ?? null,
            'grade' => $canonicalGrade,
            'medium' => $mediumVal,
            'description' => $validated['description'] ?? null,
            'file_path' => $filePath,
            'file_hash' => $fileHash,
            'file_size' => $fileSize,
            'url' => $validated['url'] ?? null,
            'teacher_id' => $request->user()->id,
            'institute_id' => $instituteId,
        ]);

        $responsePayload = $material->toArray();
        if ($warning) {
            $responsePayload['warning'] = $warning;
        }

        return response()->json($responsePayload, 201);
    }

    public function retry(Request $request, $id)
    {
        $material = LearningMaterial::where('teacher_id', $request->user()->id)->find($id);
        if (!$material) {
            return response()->json(['message' => 'Material not found.'], 404);
        }

        if (!in_array($material->rag_status, ['failed', 'quality_pending', 'low_quality', 'unsupported'], true)) {
            return response()->json(['message' => 'Only failed, quality-pending, or unsupported materials can be retried.'], 422);
        }

        $material->updateQuietly([
            'rag_status' => 'pending',
            'rag_error' => null,
        ]);

        \App\Jobs\IngestLearningMaterialJob::dispatch($material->id);

        return response()->json([
            'message' => 'Material re-indexing has been scheduled.',
            'material' => $material,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $material = LearningMaterial::where('teacher_id', $request->user()->id)->find($id);
        if (!$material) {
            if (LearningMaterial::where('id', $id)->exists()) {
                return response()->json(['message' => 'Unauthorized to delete this material.'], 403);
            }
            return response()->json(['message' => 'Material not found.'], 404);
        }
        
        if ($material->file_path && \Storage::disk('public')->exists($material->file_path)) {
            \Storage::disk('public')->delete($material->file_path);
        }
        
        $material->delete();
        
        return response()->json(['message' => 'Material deleted successfully.']);
    }

    public function download(Request $request)
    {
        $rawUri = $request->getRequestUri();
        if (str_contains($rawUri, '%00') || str_contains($rawUri, "\0")) {
            return response('Invalid path', 400);
        }

        $path = $request->query('path');
        if (!$path || str_contains($path, '..') || str_contains($path, "\0")) {
            return response('Invalid path', 400);
        }

        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[a-zA-Z]:/', $path)) {
            return response('Invalid path', 400);
        }

        $baseDir = realpath(storage_path('app/public'));
        $targetFile = storage_path('app/public/' . $path);
        $fullPath = realpath($targetFile);
        
        if ($fullPath && $baseDir && str_starts_with($fullPath, $baseDir) && file_exists($fullPath)) {
            return response()->download($fullPath);
        }
        
        return response('File not found on server', 404);
    }
}
