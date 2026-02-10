<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use Illuminate\Http\Request;

class TeacherMaterialController extends Controller
{
    public function index()
    {
        $materials = LearningMaterial::orderBy('created_at', 'desc')->get();
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
        ]);

        return response()->json($material, 201);
    }
}
