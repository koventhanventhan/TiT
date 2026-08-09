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
                        ->orderBy('created_at', 'desc')
                        ->get();
        return response()->json($materials);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string', // PDF, Video, Link, etc.
            'subject' => 'nullable|string',
            'grade' => 'nullable|string',
            'file' => 'nullable|file|max:20480',
            'url' => 'nullable|string',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('materials', 'public');
        }

        $material = LearningMaterial::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'subject' => $validated['subject'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'file_path' => $filePath,
            'url' => $validated['url'] ?? null,
            'teacher_id' => $request->user()->id,
        ]);

        return response()->json($material, 201);
    }

    public function destroy($id)
    {
        $material = LearningMaterial::findOrFail($id);
        
        if ($material->file_path && \Storage::disk('public')->exists($material->file_path)) {
            \Storage::disk('public')->delete($material->file_path);
        }
        
        $material->delete();
        
        return response()->json(['message' => 'Material deleted successfully.']);
    }

    public function download(Request $request)
    {
        $path = $request->query('path');
        if (!$path || str_contains($path, '..')) {
            return response('Invalid path', 400);
        }

        $fullPath = storage_path('app/public/' . $path);
        
        if (file_exists($fullPath)) {
            return response()->download($fullPath);
        }
        
        return response('File not found on server', 404);
    }
}
