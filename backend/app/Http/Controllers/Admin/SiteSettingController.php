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
     * Display the Footer settings page.
     */
    public function footer()
    {
        return view('admin.settings.footer');
    }

    /**
     * Display the Register Form settings page.
     */
    public function register()
    {
        return view('admin.settings.register');
    }

    /**
     * Display the Admission Fees settings page.
     */
    public function admissionFees()
    {
        return view('admin.settings.admission_fees');
    }

    /**
     * Store admission fees settings.
     */
    public function storeAdmissionFees(Request $request)
    {
        $fees = $request->input('fees', []);
        
        // Ensure values are properly cast (boolean for enabled, float for amount)
        $formattedFees = [];
        foreach ($fees as $grade => $data) {
            $formattedFees[$grade] = [
                'enabled' => isset($data['enabled']) && $data['enabled'] == '1',
                'amount' => isset($data['amount']) ? (float)$data['amount'] : 0,
            ];
        }

        SiteSetting::set('admission_fees_config', json_encode($formattedFees), 'general');

        return redirect()->back()->with('success', 'Admission fees settings updated successfully.');
    }

    /**
     * Store site settings.
     */
    public function store(Request $request)
    {
        $settings = $request->except(['_token', 'learning_notes_pdf', 'learning_pastpapers_pdf', 'learning_recordings_file', 'frontend_logo', 'admin_logo', 'site_favicon', 'cta_box_icon', 'cta_box_title', 'cta_box_subtitle']);
        
        foreach ($settings as $key => $value) {
            // Determine group based on key prefix
            $group = 'general';
            if (str_starts_with($key, 'learning_')) $group = 'learning';
            elseif (str_starts_with($key, 'classes_')) $group = 'classes';
            elseif (str_starts_with($key, 'contact_')) $group = 'contact';
            elseif (str_starts_with($key, 'social_')) $group = 'social';
            elseif (str_starts_with($key, 'footer_')) $group = 'footer';
            elseif (str_starts_with($key, 'hero_')) $group = 'hero';
            elseif (str_starts_with($key, 'admin_')) $group = 'admin_identity';
            elseif (str_starts_with($key, 'register_')) $group = 'register';
            elseif (str_starts_with($key, 'topbar_')) $group = 'topbar';
            elseif (str_starts_with($key, 'nav_')) $group = 'navigation';
            elseif (collect(['about_', 'stats_', 'why_', 'love_us_', 'mobile_'])->contains(fn($prefix) => str_starts_with($key, $prefix))) $group = 'sections';

            if (is_array($value)) {
                // Special handling for classes_types: preserve all fields, sanitize image paths
                if ($key === 'classes_types') {
                    $cleanedTypes = [];
                    foreach ($value as $typeItem) {
                        if (is_array($typeItem)) {
                            // Sanitize image path: strip any full URL prefix, keep only relative path
                            if (!empty($typeItem['image'])) {
                                $img = $typeItem['image'];
                                if (str_contains($img, 'uploads/settings/')) {
                                    $parts = explode('uploads/settings/', $img);
                                    $typeItem['image'] = 'uploads/settings/' . end($parts);
                                }
                            }
                            $cleanedTypes[] = $typeItem;
                        }
                    }
                    $value = json_encode(array_values($cleanedTypes));
                } else {
                    $value = json_encode(array_values(array_filter($value)));
                }
            }

            if ($value === null) {
                $value = '';
            }

            SiteSetting::set($key, $value, $group);
        }

        // Handle logo/favicon removals
        $logoTypes = ['admin_logo', 'frontend_logo', 'site_favicon'];
        foreach ($logoTypes as $type) {
            $removeKey = 'remove_' . ($type == 'site_favicon' ? 'favicon' : $type);
            if ($request->has($removeKey) && $request->get($removeKey) == '1') {
                $currentLogo = SiteSetting::get($type);
                if ($currentLogo && file_exists(public_path($currentLogo))) {
                    @unlink(public_path($currentLogo));
                }
                \App\Models\SiteSetting::where('key', $type)->delete();
                
                // Extra clearing for URLs
                if ($type == 'frontend_logo') \App\Models\SiteSetting::where('key', 'logo_url')->delete();
                if ($type == 'site_favicon') \App\Models\SiteSetting::where('key', 'site_favicon_url')->delete();
            }
        }

        // Handle file uploads (Admin Logo)
        if ($request->hasFile('admin_logo')) {
            $logo = $request->file('admin_logo');
            $name = 'admin_logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $path = 'uploads/settings';
            $destinationPath = public_path($path);
            if (!file_exists($destinationPath)) mkdir($destinationPath, 0777, true);
            $logo->move($destinationPath, $name);
            $logoPath = $path . '/' . $name;
            $oldLogo = SiteSetting::get('admin_logo');
            if ($oldLogo && file_exists(public_path($oldLogo))) { @unlink(public_path($oldLogo)); }
            SiteSetting::set('admin_logo', $logoPath, 'admin_identity');
        }

        // Handle file uploads (Frontend Logo)
        if ($request->hasFile('frontend_logo')) {
            $logo = $request->file('frontend_logo');
            $name = 'frontend_logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $path = 'uploads/settings';
            $destinationPath = public_path($path);
            if (!file_exists($destinationPath)) mkdir($destinationPath, 0777, true);
            $logo->move($destinationPath, $name);
            $logoPath = $path . '/' . $name;
            $oldLogo = SiteSetting::get('frontend_logo');
            if ($oldLogo && file_exists(public_path($oldLogo))) { @unlink(public_path($oldLogo)); }
            SiteSetting::set('frontend_logo', $logoPath, 'general');
            SiteSetting::set('logo_url', asset($logoPath), 'general');
        }

        // Handle file uploads (Favicon)
        if ($request->hasFile('site_favicon')) {
            $logo = $request->file('site_favicon');
            $name = 'favicon_' . time() . '.' . $logo->getClientOriginalExtension();
            $path = 'uploads/settings';
            $destinationPath = public_path($path);
            if (!file_exists($destinationPath)) mkdir($destinationPath, 0777, true);
            $logo->move($destinationPath, $name);
            $logoPath = $path . '/' . $name;
            $oldLogo = SiteSetting::get('site_favicon');
            if ($oldLogo && file_exists(public_path($oldLogo))) { @unlink(public_path($oldLogo)); }
            SiteSetting::set('site_favicon', $logoPath, 'general');
            SiteSetting::set('site_favicon_url', asset($logoPath), 'general');
        }

        return redirect()->back()->with('success', 'Topbar & Header settings updated successfully');
    }

    public function updateBranding(Request $request)
    {
        $updated = false;
        
        if ($request->has('admin_company_name')) {
            SiteSetting::set('admin_company_name', $request->admin_company_name, 'admin_identity');
            $updated = true;
        }

        if ($request->hasFile('admin_logo')) {
            $logo = $request->file('admin_logo');
            $logoName = 'admin_logo_' . time() . '.' . $logo->getClientOriginalExtension();
            
            // Delete old logo
            $currentLogo = SiteSetting::get('admin_logo');
            if ($currentLogo && file_exists(public_path($currentLogo))) {
                unlink(public_path($currentLogo));
            }
            
            $logo->move(public_path('uploads/settings'), $logoName);
            SiteSetting::set('admin_logo', 'uploads/settings/' . $logoName, 'admin_identity');
            $updated = true;
        }

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Branding updated successfully',
                'logo_url' => SiteSetting::get('admin_logo') ? asset(SiteSetting::get('admin_logo')) : null,
                'name' => SiteSetting::get('admin_company_name', 'Zenix')
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No changes made'], 400);
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
            'file' => 'nullable|file|max:512000', // 500MB max
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
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
                'path' => asset('uploads/settings/' . $name),
                'relative_path' => 'uploads/settings/' . $name
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Upload failed']);
    }

    /**
     * Handle AJAX image deletion for settings
     */
    public function deleteImage(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'image_path' => 'required|string',
        ]);

        $path = $request->image_path;
        \Illuminate\Support\Facades\Log::info('Custom deleteImage called for path: ' . $path);

        // Ensure it's inside the uploads directory to prevent path traversal
        if (str_contains($path, 'uploads/settings/')) {
            // Strip any asset URL prefix if present
            $pathParts = explode('uploads/settings/', $path);
            $cleanPath = 'uploads/settings/' . end($pathParts);
            
            $fullPath = public_path($cleanPath);
            \Illuminate\Support\Facades\Log::info('Attempting to delete fullPath: ' . $fullPath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
                \Illuminate\Support\Facades\Log::info('Deleted successfully.');
                return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
            } else {
                \Illuminate\Support\Facades\Log::info('File does not exist: ' . $fullPath);
            }
        }

        return response()->json(['success' => false, 'message' => 'Image not found or invalid path.']);
    }
}
