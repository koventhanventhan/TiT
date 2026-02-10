<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $settings = SiteSetting::all()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Display the About settings page.
     */
    public function about()
    {
        return view('admin.settings.about');
    }

    /**
     * Display the Contact settings page.
     */
    public function contact()
    {
        return view('admin.settings.contact');
    }

    /**
     * Display the Learning Site settings page.
     */
    public function learning()
    {
        $notes = \App\Models\LearningMaterial::where('type', 'note')->get();
        $pastPapers = \App\Models\LearningMaterial::where('type', 'past_paper')->get();
        $recordings = \App\Models\LearningMaterial::where('type', 'recording')->get();
        
        return view('admin.settings.learning', compact('notes', 'pastPapers', 'recordings'));
    }

    /**
     * Display the Classes settings page.
     */
    public function classes()
    {
        return view('admin.settings.classes');
    }

    /**
     * Store site settings.
     */
    public function store(Request $request)
    {
        $settings = $request->except(['_token', 'learning_notes_pdf', 'learning_pastpapers_pdf', 'learning_recordings_file']);
        
        foreach ($settings as $key => $value) {
            // Determine group based on key prefix
            $group = 'general';
            if (str_starts_with($key, 'learning_')) $group = 'learning';
            elseif (str_starts_with($key, 'classes_')) $group = 'classes';
            elseif (str_starts_with($key, 'contact_')) $group = 'contact';
            elseif (str_starts_with($key, 'social_')) $group = 'social';
            elseif (str_starts_with($key, 'footer_')) $group = 'footer';
            elseif (str_starts_with($key, 'hero_')) $group = 'hero';
            elseif (collect(['about_', 'stats_', 'why_', 'love_us_', 'mobile_'])->contains(fn($prefix) => str_starts_with($key, $prefix))) $group = 'sections';

            SiteSetting::set($key, $value, $group);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Store a learning material (Note, Past Paper, or Recording).
     */
    public function storeMaterial(Request $request)
    {
        $request->validate([
            'type' => 'required|in:note,past_paper,recording',
            'grade' => 'required|string',
            'title' => 'required|string|max:255',
            'file' => 'nullable|file|max:51200', // 50MB max
            'url' => 'nullable|string',
        ]);

        $data = [
            'type' => $request->type,
            'grade' => $request->grade,
            'title' => $request->title,
            'url' => $request->url,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $folder = 'uploads/' . $request->type . 's';
            $name = $request->type . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path($folder);
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            $fileSize = $this->formatBytes($file->getSize());
            $file->move($destinationPath, $name);
            
            $data['file_path'] = $folder . '/' . $name;
            $data['file_size'] = $fileSize;
        }

        \App\Models\LearningMaterial::create($data);

        return redirect()->back()->with('success', ucfirst($request->type) . ' added successfully.');
    }

    /**
     * Delete a learning material.
     */
    public function deleteMaterial($id)
    {
        $material = \App\Models\LearningMaterial::findOrFail($id);
        
        if ($material->file_path && file_exists(public_path($material->file_path))) {
            unlink(public_path($material->file_path));
        }
        
        $material->delete();
        
        return redirect()->back()->with('success', 'Material deleted successfully.');
    }

    /**
     * API endpoint to get all learning materials.
     */
    public function getMaterials(Request $request)
    {
        $query = \App\Models\LearningMaterial::query();

        if ($request->has('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->get());
    }

    /**
     * Helper to format bytes to human readable string.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * API endpoint to get all settings.
     */
    public function getSettings()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    /**
     * Handle AJAX image upload for settings
     */
    public function uploadImage(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/uploads/settings');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            $image->move($destinationPath, $name);
            
            return response()->json([
                'success' => true,
                'path' => asset('uploads/settings/' . $name)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Upload failed']);
    }
}
