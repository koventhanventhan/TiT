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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string', // note, paper, recording, video, PDF, etc.
            'subject' => 'nullable|string',
            'grade' => 'nullable|string',
            'medium' => 'nullable|string|in:tamil,english,all',
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
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('materials', 'public');
        }

        $material = LearningMaterial::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'subject' => $validated['subject'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'medium' => $validated['medium'] ?? 'all',
            'description' => $validated['description'] ?? null,
            'file_path' => $filePath,
            'file_size' => $validated['file_size'] ?? null,
            'url' => $validated['url'] ?? null,
            'teacher_id' => $request->user()->id,
            'institute_id' => $request->user()->institute_id ?? 1,
        ]);

        return response()->json($material, 201);
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
