<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\StudentIndexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentIndexBulkController extends Controller
{
    protected StudentIndexService $indexService;

    public function __construct(StudentIndexService $indexService)
    {
        $this->indexService = $indexService;
    }

    /**
     * Display the dedicated "Assign index numbers to existing students" tool page.
     */
    public function index(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $instituteId = auth()->user()->institute_id ?: 1;
        $institute = Institute::find($instituteId);

        // Fetch distinct grades present in users table for this institute with counts
        $gradesData = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->whereNotNull('current_grade')
            ->where('current_grade', '!=', '')
            ->select('current_grade', DB::raw('count(*) as total_count'), DB::raw('sum(case when index_number is not null then 1 else 0 end) as assigned_count'))
            ->groupBy('current_grade')
            ->get();

        $mapping = $this->indexService->getGradeBatchMapping($instituteId);

        // Count eligible unassigned students
        $unassignedCount = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->where(function ($q) {
                $q->whereNull('registration_status')
                  ->orWhereNotIn('registration_status', ['pending', 'rejected']);
            })
            ->whereNull('deactivated_at')
            ->whereNull('index_number')
            ->count();

        $totalActiveCount = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->where(function ($q) {
                $q->whereNull('registration_status')
                  ->orWhereNotIn('registration_status', ['pending', 'rejected']);
            })
            ->whereNull('deactivated_at')
            ->count();

        return view('admin.students.assign_index_tool', [
            'institute' => $institute,
            'gradesData' => $gradesData,
            'mapping' => $mapping,
            'unassignedCount' => $unassignedCount,
            'totalActiveCount' => $totalActiveCount,
        ]);
    }

    /**
     * Preview bulk assignment without making any DB writes.
     */
    public function preview(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        $instituteId = (int) (auth()->user()->institute_id ?: 1);

        $query = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->where(function ($q) {
                $q->whereNull('registration_status')
                  ->orWhereNotIn('registration_status', ['pending', 'rejected']);
            })
            ->whereNull('index_number');

        if (!$request->boolean('include_deactivated')) {
            $query->whereNull('deactivated_at');
        }

        if ($request->filled('grades') && is_array($request->grades)) {
            $grades = array_filter($request->grades);
            if (!empty($grades)) {
                $query->whereIn('current_grade', $grades);
            }
        }

        if ($request->filled('medium') && $request->medium !== 'all') {
            $med = strtolower(trim((string) $request->medium));
            if ($med === 'both') {
                $query->where('medium', 'both');
            } elseif ($med === 'english') {
                $query->where(function ($q) {
                    $q->where('medium', 'english')->orWhere('medium', 'both');
                });
            } elseif ($med === 'tamil') {
                $query->where(function ($q) {
                    $q->where('medium', 'tamil')->orWhere('medium', 'both');
                });
            }
        }

        $order = $request->input('order', 'registered');
        if ($order === 'name') {
            $query->orderBy('full_name', 'asc')->orderBy('id', 'asc');
        } else {
            $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        }

        $students = $query->get();

        $batchMode = $request->input('batch_source', 'mapping');
        $singleBatch = $request->filled('single_batch_year') ? (int) $request->single_batch_year : null;
        $mapping = $this->indexService->getGradeBatchMapping($instituteId);

        $preview = $this->indexService->previewBulkAssign(
            $students,
            $instituteId,
            $batchMode,
            $singleBatch,
            $mapping
        );

        return response()->json([
            'success' => true,
            'total_students' => $preview['total_students'],
            'skipped_count' => $preview['skipped_count'],
            'grade_summaries' => $preview['grade_summaries'],
            'skipped' => $preview['skipped'],
            'preview_rows' => $preview['preview_rows'],
        ]);
    }

    /**
     * Process one chunk of students (default 100).
     */
    public function runChunk(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        $instituteId = (int) (auth()->user()->institute_id ?: 1);
        $chunkSize = min(max((int) $request->input('chunk_size', 100), 10), 200);
        $excludeIds = (array) $request->input('exclude_ids', []);

        $query = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->where(function ($q) {
                $q->whereNull('registration_status')
                  ->orWhereNotIn('registration_status', ['pending', 'rejected']);
            })
            ->whereNull('index_number');

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', array_map('intval', $excludeIds));
        }

        if (!$request->boolean('include_deactivated')) {
            $query->whereNull('deactivated_at');
        }

        if ($request->filled('grades') && is_array($request->grades)) {
            $grades = array_filter($request->grades);
            if (!empty($grades)) {
                $query->whereIn('current_grade', $grades);
            }
        }

        if ($request->filled('medium') && $request->medium !== 'all') {
            $med = strtolower(trim((string) $request->medium));
            if ($med === 'both') {
                $query->where('medium', 'both');
            } elseif ($med === 'english') {
                $query->where(function ($q) {
                    $q->where('medium', 'english')->orWhere('medium', 'both');
                });
            } elseif ($med === 'tamil') {
                $query->where(function ($q) {
                    $q->where('medium', 'tamil')->orWhere('medium', 'both');
                });
            }
        }

        $order = $request->input('order', 'registered');
        if ($order === 'name') {
            $query->orderBy('full_name', 'asc')->orderBy('id', 'asc');
        } else {
            $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        }

        $students = $query->limit($chunkSize)->get();

        $institute = Institute::find($instituteId);
        $format = $institute?->getIndexFormat() ?? 'grade';

        $batchMode = $request->input('batch_source', 'mapping');
        $singleBatch = $request->filled('single_batch_year') ? (int) $request->single_batch_year : null;
        $mapping = $this->indexService->getGradeBatchMapping($instituteId);

        $assignedInChunk = 0;
        $skippedInChunk = 0;
        $errorsInChunk = 0;
        $assignedList = [];
        $skippedList = [];
        $newlyExcludedIds = [];

        foreach ($students as $student) {
            if ($format === 'grade') {
                try {
                    $series = $this->indexService->deriveGradeSeries($student->current_grade);
                } catch (\Throwable $e) {
                    $skippedInChunk++;
                    $newlyExcludedIds[] = $student->id;
                    $skippedList[] = [
                        'student_id' => $student->id,
                        'name' => $student->full_name ?? $student->name,
                        'grade' => $student->current_grade,
                        'reason' => "Cannot derive grade series from {$student->current_grade}",
                    ];
                    continue;
                }

                try {
                    $indexNumber = $this->indexService->assign($student, $series);
                    $assignedInChunk++;
                    $assignedList[] = [
                        'student_id' => $student->id,
                        'name' => $student->full_name ?? $student->name,
                        'grade' => $student->current_grade,
                        'batch' => $series,
                        'index_number' => $indexNumber,
                    ];
                } catch (\Throwable $e) {
                    $errorsInChunk++;
                    $newlyExcludedIds[] = $student->id;
                    $skippedList[] = [
                        'student_id' => $student->id,
                        'name' => $student->full_name ?? $student->name,
                        'grade' => $student->current_grade,
                        'reason' => "Assignment error: " . $e->getMessage(),
                    ];
                }
                continue;
            }

            // Determine batch year (legacy batch mode)
            $batchYear = null;
            if ($batchMode === 'single' && $singleBatch) {
                try {
                    $batchYear = $this->indexService->normalizeBatchYear($singleBatch);
                } catch (\InvalidArgumentException $e) {
                    $batchYear = null;
                }
            } else {
                $batchYear = $this->indexService->resolveBatchForGrade($student->current_grade, $mapping);
            }

            if ($batchYear === null) {
                $skippedInChunk++;
                $newlyExcludedIds[] = $student->id;
                $skippedList[] = [
                    'student_id' => $student->id,
                    'name' => $student->full_name ?? $student->name,
                    'grade' => $student->current_grade,
                    'reason' => "No batch year for Grade {$student->current_grade}",
                ];
                continue;
            }

            try {
                $indexNumber = $this->indexService->assign($student, $batchYear);
                $assignedInChunk++;
                $assignedList[] = [
                    'student_id' => $student->id,
                    'name' => $student->full_name ?? $student->name,
                    'grade' => $student->current_grade,
                    'batch' => $batchYear,
                    'index_number' => $indexNumber,
                ];
            } catch (\Throwable $e) {
                $errorsInChunk++;
                $newlyExcludedIds[] = $student->id;
                $skippedList[] = [
                    'student_id' => $student->id,
                    'name' => $student->full_name ?? $student->name,
                    'grade' => $student->current_grade,
                    'reason' => "Assignment error: " . $e->getMessage(),
                ];
            }
        }

        // Count remaining eligible students
        $remainingCount = (clone $query)
            ->whereNotIn('id', array_merge($excludeIds, $newlyExcludedIds))
            ->count();

        $isDone = ($students->isEmpty() || $remainingCount === 0);

        // If completed, record ActivityLog entry
        if ($isDone && $request->boolean('is_last_step', false)) {
            $totalAssigned = (int) $request->input('total_assigned', $assignedInChunk);
            $totalSkipped = (int) $request->input('total_skipped', $skippedInChunk);
            $firstIndex = $request->input('first_index', !empty($assignedList) ? $assignedList[0]['index_number'] : 'N/A');
            $lastIndex = !empty($assignedList) ? end($assignedList)['index_number'] : $request->input('last_index', 'N/A');

            ActivityLogService::log(
                'student_index_bulk_assigned',
                "Bulk assigned index numbers to {$totalAssigned} student(s) ({$firstIndex} ... {$lastIndex})",
                [
                    'assigned_count' => $totalAssigned,
                    'skipped_count' => $totalSkipped,
                    'batch_source' => $batchMode,
                    'order' => $order,
                    'first_index' => $firstIndex,
                    'last_index' => $lastIndex,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'done' => $isDone,
            'processed_in_chunk' => $students->count(),
            'assigned_in_chunk' => $assignedInChunk,
            'skipped_in_chunk' => $skippedInChunk,
            'errors_in_chunk' => $errorsInChunk,
            'remaining' => $remainingCount,
            'assigned_list' => $assignedList,
            'skipped_list' => $skippedList,
            'newly_excluded_ids' => $newlyExcludedIds,
        ]);
    }

    /**
     * Download preview CSV.
     */
    public function exportPreview(Request $request): StreamedResponse
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $rows = (array) $request->input('rows', []);
        $fileName = 'index_preview_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student ID', 'Full Name', 'Grade', 'Batch Year', 'Index Number']);
            foreach ($rows as $row) {
                if (is_string($row)) {
                    $row = json_decode($row, true) ?: [];
                }
                fputcsv($handle, [
                    $row['student_id'] ?? '',
                    $row['name'] ?? '',
                    $row['grade'] ?? '',
                    $row['batch'] ?? '',
                    $row['index_number'] ?? '',
                ]);
            }
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * CSV Import view page.
     */
    public function importView(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $instituteId = auth()->user()->institute_id ?: 1;
        $institute = Institute::find($instituteId);

        return view('admin.students.import_index', [
            'institute' => $institute,
        ]);
    }

    /**
     * Preview uploaded CSV file.
     */
    public function importPreview(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $rows = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $headers = null;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$headers) {
                    $headers = array_map(function ($h) {
                        return strtolower(trim(preg_replace('/[\xEF\xBB\xBF]/', '', $h)));
                    }, $data);
                    continue;
                }
                if (count($data) === count($headers)) {
                    $rows[] = array_combine($headers, $data);
                } else {
                    $row = [];
                    foreach ($headers as $i => $h) {
                        $row[$h] = $data[$i] ?? null;
                    }
                    $rows[] = $row;
                }
            }
            fclose($handle);
        }

        $instituteId = (int) (auth()->user()->institute_id ?: 1);
        $preview = $this->indexService->previewAndValidateImport($rows, $instituteId);

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }

    /**
     * Execute the CSV import.
     */
    public function importRun(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        $request->validate([
            'rows' => 'nullable|array',
            'validated_rows' => 'nullable|array',
            'abort_on_error' => 'nullable|boolean',
        ]);

        $rows = $request->input('validated_rows') ?: $request->input('rows', []);
        $abortOnError = $request->boolean('abort_on_error', false);
        $instituteId = (int) (auth()->user()->institute_id ?: 1);

        try {
            $summary = $this->indexService->executeImport(
                $rows,
                $instituteId,
                $abortOnError,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'imported_count' => $summary['imported_count'],
                'skipped_count' => $summary['skipped_count'],
                'results' => $summary['results'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download CSV template for index number import.
     */
    public function downloadTemplate(): StreamedResponse
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $fileName = 'student_index_import_template.csv';
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['grade', 'medium', 'student_name', 'index_number']);
            fputcsv($handle, ['Grade 8', 'English', 'A. Example Student', 'TIT/08/001']);
            fputcsv($handle, ['Grade 8', 'English', 'B. Second Student', 'TIT/08/002']);
            fputcsv($handle, ['Grade 8', 'English', '-', 'TIT/08/003']);
            fputcsv($handle, ['Grade 8', 'Tamil', 'C. Third Student', 'TIT/08/015']);
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Display formal Index Number Lists grouped by Grade and Medium.
     */
    public function indexLists(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $instituteId = (int) (auth()->user()->institute_id ?: 1);
        $institute = Institute::find($instituteId);

        $grades = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->whereNotNull('current_grade')
            ->where('current_grade', '!=', '')
            ->distinct()
            ->orderBy('current_grade')
            ->pluck('current_grade')
            ->values()
            ->all();

        $selectedGrade = $request->input('grade');
        if (!$selectedGrade) {
            foreach ($grades as $g) {
                if (str_contains($g, '8')) {
                    $selectedGrade = $g;
                    break;
                }
            }
            if (!$selectedGrade && !empty($grades)) {
                $selectedGrade = $grades[0];
            }
            if (!$selectedGrade) {
                $selectedGrade = 'Grade 8';
            }
        }

        $students = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->where(function ($q) use ($selectedGrade) {
                $q->where('current_grade', $selectedGrade);
                $num = preg_replace('/[^0-9]/', '', $selectedGrade);
                if ($num) {
                    $q->orWhere('current_grade', 'Grade ' . $num)
                      ->orWhere('current_grade', (string) ((int) $num))
                      ->orWhere('current_grade', sprintf('%02d', (int) $num));
                }
            })
            ->where(function ($q) {
                $q->whereNull('registration_status')
                  ->orWhereNotIn('registration_status', ['pending', 'rejected']);
            })
            ->orderBy('index_number')
            ->orderBy('full_name')
            ->get();

        $englishStudents = $students->filter(function ($s) {
            return !empty($s->index_number) && in_array(strtolower($s->medium ?? ''), ['english', 'both']);
        })->sortBy(function ($s) {
            return $s->index_number;
        })->values();

        $tamilStudents = $students->filter(function ($s) {
            return !empty($s->index_number) && in_array(strtolower($s->medium ?? ''), ['tamil', 'both']);
        })->sortBy(function ($s) {
            return $s->index_number;
        })->values();

        $unassignedStudents = $students->filter(function ($s) {
            return empty($s->index_number);
        })->values();

        return view('admin.students.index_lists', compact(
            'institute',
            'grades',
            'selectedGrade',
            'englishStudents',
            'tamilStudents',
            'unassignedStudents'
        ));
    }

    /**
     * Download PDF list of index numbers for a specific grade and medium.
     */
    public function indexListsPdf(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $instituteId = (int) (auth()->user()->institute_id ?: 1);
        $institute = Institute::find($instituteId) ?: new Institute(['index_prefix' => 'TiT', 'name' => 'TiT Institute']);

        $grade = $request->input('grade', 'Grade 8');
        $medium = strtolower($request->input('medium', 'english'));
        if (!in_array($medium, ['english', 'tamil'])) {
            $medium = 'english';
        }

        $students = User::withoutGlobalScopes()
            ->where('role', 'user')
            ->where('institute_id', $instituteId)
            ->where(function ($q) use ($grade) {
                $q->where('current_grade', $grade);
                $num = preg_replace('/[^0-9]/', '', $grade);
                if ($num) {
                    $q->orWhere('current_grade', 'Grade ' . $num)
                      ->orWhere('current_grade', (string) ((int) $num))
                      ->orWhere('current_grade', sprintf('%02d', (int) $num));
                }
            })
            ->whereNotNull('index_number')
            ->where(function ($q) use ($medium) {
                $q->where('medium', $medium)->orWhere('medium', 'both');
            })
            ->where(function ($q) {
                $q->whereNull('registration_status')
                  ->orWhereNotIn('registration_status', ['pending', 'rejected']);
            })
            ->orderBy('index_number')
            ->get();

        $gradeNum = preg_replace('/[^0-9]/', '', $grade);
        $gradeDisplay = $gradeNum ? 'GRADE ' . sprintf('%02d', (int) $gradeNum) : strtoupper($grade);
        $mediumDisplay = strtoupper($medium) . ' MEDIUM';

        $pdfContent = $this->renderIndexListPdf($institute, $gradeDisplay, $mediumDisplay, $students);

        $filename = "Index_Numbers_{$gradeDisplay}_{$mediumDisplay}.pdf";
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Render the index list PDF using mPDF with Noto Sans Tamil.
     */
    protected function renderIndexListPdf(Institute $institute, string $gradeDisplay, string $mediumDisplay, $students): string
    {
        $html = view('pdf.index_list', [
            'institute' => $institute,
            'gradeDisplay' => $gradeDisplay,
            'mediumDisplay' => $mediumDisplay,
            'students' => $students,
        ])->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'tempDir' => $tempDir,
            'fontDir' => array_merge($fontDirs, [resource_path('fonts')]),
            'fontdata' => $fontData + [
                'notosanstamil' => [
                    'R' => 'NotoSansTamil-Regular.ttf',
                    'B' => 'NotoSansTamil-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
                'montserrat' => [
                    'B' => 'Montserrat-ExtraBold.ttf',
                ],
            ],
            'default_font' => 'notosanstamil',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            'useSubstitutions' => true,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 18,
            'margin_bottom' => 18,
        ]);

        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S');
    }
}
