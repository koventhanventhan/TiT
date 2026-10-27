<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\StudentIndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Display the Grade Promotion Schedule settings page.
     */
    public function gradePromotionSchedule()
    {
        return view('admin.settings.grade_promotion_schedule');
    }

    /**
     * Store grade promotion schedule settings.
     */
    public function storeGradePromotionSchedule(Request $request)
    {
        $schedule = $request->input('schedule', []);

        $formattedSchedule = [];
        foreach (range(1, 13) as $grade) {
            $data = $schedule[$grade] ?? [];
            $enabled = isset($data['enabled']) && $data['enabled'] == '1';
            $date = !empty($data['date']) ? $data['date'] : null;

            // If toggle is ON but no date is set, turn it off
            if ($enabled && !$date) {
                $enabled = false;
            }

            $formattedSchedule[(string)$grade] = [
                'enabled' => $enabled,
                'date' => $date,
            ];
        }

        SiteSetting::set('grade_promotion_schedule', json_encode($formattedSchedule), 'general');

        return redirect()->back()->with('success', 'Grade promotion schedule updated successfully.');
    }

    /**
     * Display Index Number Batches settings page.
     */
    public function indexBatches(StudentIndexService $indexService)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $instituteId = auth()->check() ? auth()->user()->institute_id : null;
        $instituteId = $instituteId ?: 1;

        // Query distinct grades that really exist in users.current_grade for this institute + student counts
        $existingGrades = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->whereNotNull('current_grade')
            ->where('current_grade', '!=', '')
            ->select('current_grade', DB::raw('count(*) as count'))
            ->groupBy('current_grade')
            ->get();

        // Ensure standard grades (Grade 1 .. Grade 13) are available, along with distinct grades
        $gradeMap = [];
        for ($i = 1; $i <= 13; $i++) {
            $gradeMap[(string) $i] = [
                'grade_key' => (string) $i,
                'display' => "Grade {$i}",
                'count' => 0,
            ];
        }

        foreach ($existingGrades as $row) {
            $raw = trim((string) $row->current_grade);
            if (preg_match('/(\d+)/', $raw, $m)) {
                $num = (string) (int) $m[1];
                if (isset($gradeMap[$num])) {
                    $gradeMap[$num]['count'] += (int) $row->count;
                    continue;
                }
            }
            if (!isset($gradeMap[$raw])) {
                $gradeMap[$raw] = [
                    'grade_key' => $raw,
                    'display' => $raw,
                    'count' => (int) $row->count,
                ];
            } else {
                $gradeMap[$raw]['count'] += (int) $row->count;
            }
        }

        $savedMapping = $indexService->getGradeBatchMapping($instituteId);

        return view('admin.settings.index_batches', [
            'grades' => $gradeMap,
            'mapping' => $savedMapping,
        ]);
    }

    /**
     * Store Index Number Batches settings.
     */
    public function storeIndexBatches(Request $request, StudentIndexService $indexService)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate([
            'batches' => 'nullable|array',
            'batches.*' => 'nullable|integer|min:20|max:99',
        ]);

        $batches = $request->input('batches', []);
        $instituteId = auth()->check() ? auth()->user()->institute_id : 1;

        $indexService->setGradeBatchMapping($batches, $instituteId);

        return redirect()->back()->with('success', 'Index number batch mapping saved successfully.');
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
     * Issue a short-lived Firebase custom token for admin direct storage uploads.
     */
    public function firebaseToken(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $tokenService = \App\Services\FirebaseTokenService::fromConfig();
        if (!$tokenService->isConfigured()) {
            return response()->json([
                'message' => 'Firebase service account is not configured.'
            ], 503);
        }

        try {
            $instituteId = (int) ($user->institute_id ?? 1);
            $token = $tokenService->createCustomToken(
                'admin-' . $user->id,
                [
                    'admin' => true,
                    'institute' => 'i' . $instituteId,
                ]
            );

            return response()->json([
                'token' => $token,
                'institute_id' => $instituteId,
                'web_config' => config('services.firebase.web'),
            ])->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Firebase custom token minting failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to mint Firebase token.'], 500);
        }
    }

    /**
     * Store a learning material (Note, Past Paper, or Recording).
     */
    public function storeMaterial(Request $request)
    {
        $isStudyDoc = in_array($request->type, ['note', 'past_paper'], true);

        $request->validate([
            'type' => 'required|in:note,past_paper,recording',
            'grade' => $isStudyDoc ? 'required|string|max:50' : 'nullable|string|max:50',
            'subject' => $isStudyDoc ? 'required|string|max:100' : 'nullable|string|max:100',
            'medium' => $isStudyDoc ? 'required|string|in:tamil,english,sinhala,all' : 'nullable|string|in:tamil,english,sinhala,all',
            'title' => 'required|string|max:255',
            'file' => 'nullable|file|max:512000', // 500MB max
            'file_url' => 'nullable|url|max:2048',
            'file_size' => 'nullable|string|max:50',
            'url' => 'nullable|string',
        ]);

        $hasFile = $request->hasFile('file');
        $hasFileUrl = $request->filled('file_url');
        $hasUrl = $request->filled('url');

        if ($request->type === 'recording') {
            if (!$hasFile && !$hasFileUrl && !$hasUrl) {
                return $this->manualValidationError($request, 'file', 'Please provide a file, a storage URL, or a video URL.');
            }
        } else {
            if (!$hasFile && !$hasFileUrl) {
                return $this->manualValidationError($request, 'file', 'Please select a file to upload.');
            }
        }

        $canonicalGrade = \App\Support\GradeMatcher::formatCanonical($request->grade);

        $data = [
            'type' => $request->type,
            'grade' => $canonicalGrade,
            'subject' => $request->subject,
            'medium' => $request->medium ?? 'all',
            'title' => $request->title,
            'url' => $request->url,
            'institute_id' => (int) (auth()->user()->institute_id ?? 1),
        ];

        $fileHash = null;
        $warning = null;

        if ($hasFileUrl) {
            $fileUrl = $request->input('file_url');
            $instituteId = (int) (auth()->user()->institute_id ?? 1);

            if (!$this->isValidFirebaseStorageUrl($fileUrl, $instituteId)) {
                return $this->manualValidationError($request, 'file_url', 'Invalid or unauthorized storage URL.');
            }

            $data['file_path'] = $fileUrl;
            $data['file_size'] = $request->input('file_size');
        } elseif ($hasFile) {
            $file = $request->file('file');
            $fileHash = hash_file('sha256', $file->getRealPath());
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
            $data['file_hash'] = $fileHash;

            $instituteId = (int) (auth()->user()->institute_id ?? 1);
            if (!empty($fileHash)) {
                $existing = \App\Models\LearningMaterial::where('institute_id', $instituteId)
                    ->where('file_hash', $fileHash)
                    ->first();
                if ($existing) {
                    $warning = "Note: A file with identical content already exists in this institute (\"{$existing->title}\").";
                }
            }
        }

        \App\Models\LearningMaterial::create($data);

        $redirect = redirect()->back()->with('success', ucfirst($request->type) . ' added successfully.');
        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    /**
     * Validate whether a storage URL is a legitimate Firebase storage URL for the given institute.
     */
    private function isValidFirebaseStorageUrl(string $url, int $instituteId): bool
    {
        return \App\Services\FirebaseStorageUrlValidator::isValidForInstitute($url, $instituteId);
    }

    /**
     * Return a validation error response suitable for both JSON/AJAX and standard form submissions.
     */
    private function manualValidationError(Request $request, string $field, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
            ], 422);
        }

        return redirect()->back()->withErrors([$field => $message])->withInput();
    }

    /**
     * Delete a learning material.
     */
    public function deleteMaterial($id)
    {
        $material = \App\Models\LearningMaterial::findOrFail($id);
        
        $filePath = $material->file_path;
        if ($filePath && !str_starts_with($filePath, 'http://') && !str_starts_with($filePath, 'https://')) {
            $uploadsDir = realpath(public_path('uploads'));
            $realTarget = realpath(public_path($filePath));
            if ($uploadsDir && $realTarget && is_file($realTarget)) {
                $allowedPrefix = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                if (str_starts_with($realTarget, $allowedPrefix)) {
                    @unlink($realTarget);
                }
            }
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

        $query->where(function ($q) { $q->where('type', '!=', 'recording')->orWhereNull('zoom_schedule_id'); });

        // Apply configurable day-limit for automated recordings
        $visibilityDays = (int) SiteSetting::get('recording_visibility_days', 2);
        $query->where(function ($q) use ($visibilityDays) {
            $q->where('type', '!=', 'recording')
              ->orWhereNull('zoom_schedule_id') // Manually uploaded recordings don't have zoom_schedule_id
              ->orWhere('created_at', '>=', now()->subDays($visibilityDays));
        });

        $materials = $query->get()->reject(function ($m) {
            return $m->type === 'recording' && \App\Models\SiteSetting::isRecordingGradeDisabled($m->grade);
        });

        return response()->json($materials->values());
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
    public function getSettings(\Illuminate\Http\Request $request)
    {
        $settings = SiteSetting::all()->pluck('value', 'key')->toArray();

        $instituteId = $request->header('X-Institute-Id', 1);
        $institute = \App\Models\Institute::find($instituteId);

        $globalPublicEnabled = filter_var(config('rag.public_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $recaptchaSecret = config('services.recaptcha.secret_key');
        $env = config('app.env') ?: app()->environment();
        $isLocalOrTesting = in_array($env, ['local', 'testing'], true);

        // reCAPTCHA fail closed: If not local or testing and RECAPTCHA_SECRET_KEY is empty, public widget is hidden.
        if (!$isLocalOrTesting && empty($recaptchaSecret)) {
            $settings['public_assistant_enabled'] = false;
        } else {
            $settings['public_assistant_enabled'] = (bool) ($globalPublicEnabled && ($institute?->public_assistant_enabled ?? false));
        }

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

    /**
     * Display Index Number Series management page.
     */
    public function indexSeries()
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $instituteId = auth()->user()->institute_id ?: 1;
        $institute = Institute::findOrFail($instituteId);

        $counters = StudentIndexCounter::where('institute_id', $instituteId)
            ->orderBy('batch_year', 'asc')
            ->get();

        $studentsPerSeries = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->whereNotNull('batch_year')
            ->select('batch_year', DB::raw('count(*) as count'), DB::raw('max(index_seq) as max_seq'))
            ->groupBy('batch_year')
            ->get()
            ->keyBy('batch_year');

        $seriesData = [];
        foreach ($counters as $c) {
            $bYear = (int) $c->batch_year;
            $seriesStr = str_pad((string) $bYear, 2, '0', STR_PAD_LEFT);
            $stRow = $studentsPerSeries->get($bYear);
            $stCount = $stRow ? (int) $stRow->count : 0;
            $highestIssued = $stRow ? (int) $stRow->max_seq : 0;

            $seriesData[] = [
                'id' => $c->id,
                'batch_year' => $bYear,
                'series' => $seriesStr,
                'last_seq' => (int) $c->last_seq,
                'next_seq' => (int) $c->last_seq + 1,
                'students_count' => $stCount,
                'highest_issued' => $highestIssued,
            ];
        }

        // Summary of how many students would be in "old format" after the switch
        $allStudents = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->whereNotNull('index_number')
            ->where('index_number', '!=', '')
            ->get(['id', 'index_number']);

        $prefix = $institute->getIndexPrefix();

        $dummyGradeInst = new Institute([
            'index_format' => 'grade',
            'index_prefix' => $prefix,
            'index_seq_start' => 1,
        ]);

        $dummyBatchInst = new Institute([
            'index_format' => 'batch',
            'index_prefix' => $prefix,
            'index_seq_start' => 100,
        ]);

        $oldIfGrade = 0;
        $oldIfBatch = 0;
        foreach ($allStudents as $st) {
            if (StudentIndexService::isOldOrWrongFormat($st->index_number, $dummyGradeInst)) {
                $oldIfGrade++;
            }
            if (StudentIndexService::isOldOrWrongFormat($st->index_number, $dummyBatchInst)) {
                $oldIfBatch++;
            }
        }

        return view('admin.settings.index_series', [
            'institute' => $institute,
            'seriesList' => $seriesData,
            'totalStudentsWithIndex' => $allStudents->count(),
            'oldIfGrade' => $oldIfGrade,
            'oldIfBatch' => $oldIfBatch,
        ]);
    }

    /**
     * Update institute index configuration (format, prefix, start number).
     */
    public function updateIndexConfig(Request $request)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate([
            'index_format' => 'required|in:grade,batch',
            'index_prefix' => 'required|string|max:10',
            'index_seq_start' => 'required|integer|min:1',
        ]);

        $instituteId = auth()->user()->institute_id ?: 1;
        $institute = Institute::findOrFail($instituteId);

        $oldConfig = [
            'format' => $institute->index_format,
            'prefix' => $institute->index_prefix,
            'start' => $institute->index_seq_start,
        ];

        $institute->update([
            'index_format' => $request->input('index_format'),
            'index_prefix' => trim($request->input('index_prefix')),
            'index_seq_start' => (int) $request->input('index_seq_start'),
        ]);

        ActivityLogService::log(
            'index_config_updated',
            "Updated index config: format={$request->input('index_format')}, prefix={$request->input('index_prefix')}, start={$request->input('index_seq_start')}",
            ['old' => $oldConfig, 'new' => $institute->only(['index_format', 'index_prefix', 'index_seq_start'])]
        );

        return redirect()->back()->with('success', 'Index configuration updated successfully.');
    }

    /**
     * Update next automatic number for a series counter.
     */
    public function updateIndexCounter(Request $request)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate([
            'series' => 'required',
            'next' => 'required|integer|min:1',
        ]);

        $seriesInt = (int) $request->input('series');
        if ($seriesInt < 1 || $seriesInt > 99) {
            return redirect()->back()->with('error', 'Series must be a number between 01 and 99.');
        }

        $next = (int) $request->input('next');
        $instituteId = auth()->user()->institute_id ?: 1;
        $seriesStr = str_pad((string) $seriesInt, 2, '0', STR_PAD_LEFT);

        $highestIssued = $this->getHighestIssuedSeq($instituteId, $seriesInt);

        if ($highestIssued > 0 && $next <= $highestIssued) {
            return redirect()->back()->with('error', "Next number must be greater than highest number issued in series {$seriesStr} ({$highestIssued}). Provided: {$next}.");
        }

        $counter = StudentIndexCounter::firstOrNew([
            'institute_id' => $instituteId,
            'batch_year' => $seriesInt,
        ]);

        $oldLast = $counter->exists ? $counter->last_seq : null;
        $oldNext = $counter->exists ? ((int) $counter->last_seq + 1) : null;

        $counter->last_seq = $next - 1;
        $counter->save();

        ActivityLogService::log(
            'index_counter_updated',
            "Updated next automatic index number for series {$seriesStr} from {$oldNext} to {$next}",
            [
                'series' => $seriesStr,
                'old_last_seq' => $oldLast,
                'new_last_seq' => $counter->last_seq,
                'next' => $next,
                'highest_issued' => $highestIssued,
            ]
        );

        return redirect()->back()->with('success', "Series {$seriesStr} next automatic number set to {$next}.");
    }

    /**
     * Add a new series counter.
     */
    public function addIndexSeries(Request $request)
    {
        return $this->updateIndexCounter($request);
    }

    /**
     * Helper to find highest issued sequence number in a series for an institute.
     */
    private function getHighestIssuedSeq(int $instituteId, int $seriesInt): int
    {
        $seriesStr = str_pad((string) $seriesInt, 2, '0', STR_PAD_LEFT);
        $users = User::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where(function ($q) use ($seriesInt, $seriesStr) {
                $q->where('batch_year', $seriesInt)
                  ->orWhere('index_number', 'LIKE', "%/{$seriesStr}/%")
                  ->orWhere('index_number', 'LIKE', "%/{$seriesInt}/%");
            })
            ->get(['id', 'index_seq', 'index_number']);

        $highest = 0;
        foreach ($users as $u) {
            if ($u->index_seq && (int) $u->index_seq > $highest) {
                $highest = (int) $u->index_seq;
            }
            if ($u->index_number && preg_match('/\/(\d{3,})$/', $u->index_number, $m)) {
                $seqVal = (int) $m[1];
                if ($seqVal > $highest) {
                    $highest = $seqVal;
                }
            }
        }
        return $highest;
    }
}
