<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\ExamTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ExamResultWebController extends Controller
{
    // ─── ADMIN: Blade View ───
    public function view()
    {
        $terms = \App\Models\ExamTerm::orderBy('name')->pluck('name');
        return view('admin.exam_results.index', compact('terms'));
    }

    // ─── PUBLIC: Search exam results ───
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'index_no' => 'required|string',
            'term'     => 'required|string',
            'grade'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please fill all search fields', 'errors' => $validator->errors()], 422);
        }

        $results = ExamResult::where('index_no', $request->index_no)
            ->where('term', $request->term)
            ->where('grade', $request->grade)
            ->when($request->year, fn($q, $year) => $q->where('year', $year))
            ->orderBy('subject')
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found for the given criteria', 'data' => []], 404);
        }

        // Calculate summary
        $totalMarks = $results->sum('marks');
        $subjectCount = $results->count();
        $average = $subjectCount > 0 ? round($totalMarks / $subjectCount, 2) : 0;

        return response()->json([
            'data' => $results,
            'summary' => [
                'student_name' => $results->first()->student_name,
                'index_no' => $results->first()->index_no,
                'term' => $results->first()->term,
                'grade' => $results->first()->grade,
                'year' => $results->first()->year,
                'total_marks' => $totalMarks,
                'subject_count' => $subjectCount,
                'average' => $average,
            ]
        ]);
    }

    // ─── PUBLIC: Get available terms ───
    public function getTerms()
    {
        $terms = ExamTerm::orderBy('name')->pluck('name')->unique()->values();
        return response()->json(['data' => $terms]);
    }

    // ─── PUBLIC: Get available grades (from existing results) ───
    public function getGrades()
    {
        $grades = ExamResult::select('grade')->distinct()->orderBy('grade')->pluck('grade');
        
        // If no results exist yet, return default grades
        if ($grades->isEmpty()) {
            $grades = collect([
                'Grade-1', 'Grade-2', 'Grade-3', 'Grade-4', 'Grade-5',
                'Grade-6', 'Grade-7', 'Grade-8', 'Grade-9', 'Grade-10',
                'Grade-11', 'Grade-12', 'Grade-13', 'O/L', 'A/L'
            ]);
        }

        return response()->json(['data' => $grades]);
    }

    // ─── PUBLIC: Get available years (from existing results) ───
    public function getYears()
    {
        $years = ExamResult::select('year')->distinct()->orderByDesc('year')->pluck('year')->filter();
        return response()->json(['data' => $years]);
    }

    // ─── ADMIN: List all results with filters ───
    public function index(Request $request)
    {
        $query = ExamResult::query();

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('index_no', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->term) {
            $query->where('term', $request->term);
        }
        if ($request->grade) {
            $query->where('grade', $request->grade);
        }
        if ($request->year) {
            $query->where('year', $request->year);
        }

        $results = $query->orderByDesc('created_at')->paginate($request->per_page ?? 50);

        return response()->json($results);
    }

    // ─── ADMIN: Store a single result ───
    public function store(Request $request)
    {
        \Log::info('ExamResultWebController@store called', $request->all());

        $validator = Validator::make($request->all(), [
            'student_name' => 'required|string|max:255',
            'index_no'     => 'required|string|max:50',
            'term'         => 'required|string|max:100',
            'grade'        => 'required|string|max:50',
            'subject'      => 'required|string|max:255',
            'marks'        => 'nullable|numeric|min:0|max:100',
            'result_grade' => 'nullable|string|max:10',
            'rank'         => 'nullable|integer|min:1',
            'year'         => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Custom duplicate entry check as requested
        $exists = ExamResult::where('index_no', $request->index_no)
                            ->where('term', $request->term)
                            ->where('subject', $request->subject)
                            ->where('grade', $request->grade)
                            ->exists();

        if ($exists) {
            return response()->json([
                'message' => "Idhu ku already result add pannirukanga (Index No: {$request->index_no}, Subject: {$request->subject}, Term: {$request->term})"
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['institute_id'] = $request->user()?->institute_id;

            $result = ExamResult::create($data);

            return response()->json(['message' => 'Result added successfully', 'data' => $result], 201);
        } catch (\Exception $e) {
            \Log::error('Exam Result Save Failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Backend Database Error: ' . $e->getMessage()], 500);
        }
    }

    // ─── ADMIN: Update a result ───
    public function update(Request $request, $id)
    {
        $result = ExamResult::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'student_name' => 'sometimes|string|max:255',
            'index_no'     => 'sometimes|string|max:50',
            'term'         => 'sometimes|string|max:100',
            'grade'        => 'sometimes|string|max:50',
            'subject'      => 'sometimes|string|max:255',
            'marks'        => 'nullable|numeric|min:0|max:100',
            'result_grade' => 'nullable|string|max:10',
            'rank'         => 'nullable|integer|min:1',
            'year'         => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Custom duplicate entry check as requested
        $updateIndex = $request->index_no ?? $result->index_no;
        $updateTerm = $request->term ?? $result->term;
        $updateSubject = $request->subject ?? $result->subject;
        $updateGrade = $request->grade ?? $result->grade;

        $exists = ExamResult::where('id', '!=', $id)
                            ->where('index_no', $updateIndex)
                            ->where('term', $updateTerm)
                            ->where('subject', $updateSubject)
                            ->where('grade', $updateGrade)
                            ->exists();

        if ($exists) {
            return response()->json([
                'message' => "Idhu ku already result add pannirukanga (Index No: {$updateIndex}, Subject: {$updateSubject}, Term: {$updateTerm})"
            ], 422);
        }

        $result->update($validator->validated());

        return response()->json(['message' => 'Result updated successfully', 'data' => $result]);
    }

    // ─── ADMIN: Delete a result ───
    public function destroy($id)
    {
        $result = ExamResult::findOrFail($id);
        $result->delete();

        return response()->json(['message' => 'Result deleted successfully']);
    }

    // ─── ADMIN: Bulk delete ───
    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        ExamResult::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => count($request->ids) . ' results deleted successfully']);
    }

    // ─── ADMIN: Import from file (Excel/CSV) ───
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,pdf,docx|max:10240',
            'term' => 'required|string',
            'grade' => 'required|string',
            'year' => 'nullable|string',
        ]);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $instituteId = $request->user()?->institute_id;

            $imported = 0;
            $errors = [];

            if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
                // Use PhpSpreadsheet (bundled with maatwebsite/excel or standalone)
                $imported = $this->parseSpreadsheet($file, $request->term, $request->grade, $request->year, $instituteId, $errors);
            } elseif ($extension === 'pdf') {
                $imported = $this->parsePdf($file, $request->term, $request->grade, $request->year, $instituteId, $errors);
            } elseif ($extension === 'docx') {
                $imported = $this->parseWord($file, $request->term, $request->grade, $request->year, $instituteId, $errors);
            }

            return response()->json([
                'message' => "{$imported} results imported successfully" . (count($errors) > 0 ? ' (with some skipped rows)' : ''),
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Parse spreadsheet file and import results.
     * Expected columns: student_name, index_no, subject, marks, result_grade, rank
     */
    private function parseSpreadsheet($file, $term, $grade, $year, $instituteId, &$errors)
    {
        $extension = $file->getClientOriginalExtension();
        $imported = 0;

        if ($extension === 'csv') {
            // CSV parsing
            $handle = fopen($file->getPathname(), 'r');
            $header = fgetcsv($handle); // First row = headers
            
            if (!$header) {
                $errors[] = 'Empty file or invalid CSV format';
                fclose($handle);
                return 0;
            }

            // Normalize headers
            $header = array_map(fn($h) => strtolower(trim($h)), $header);
            $rowNum = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                $data = array_combine($header, $row);
                
                if (empty($data['student_name'] ?? '') || empty($data['index_no'] ?? '')) {
                    $errors[] = "Row {$rowNum}: Missing student_name or index_no";
                    continue;
                }

                ExamResult::updateOrCreate([
                    'index_no'     => trim($data['index_no'] ?? ''),
                    'term'         => $term,
                    'grade'        => $grade,
                    'subject'      => trim($data['subject'] ?? ''),
                ], [
                    'student_name' => trim($data['student_name'] ?? ''),
                    'marks'        => is_numeric($data['marks'] ?? '') ? (float)$data['marks'] : null,
                    'result_grade' => trim($data['result_grade'] ?? $data['grade_result'] ?? ''),
                    'rank'         => is_numeric($data['rank'] ?? '') ? (int)$data['rank'] : null,
                    'year'         => $year,
                    'institute_id' => $instituteId,
                ]);
                $imported++;
            }

            fclose($handle);
        } else {
            // Excel parsing using PhpSpreadsheet
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                if (count($rows) < 2) {
                    $errors[] = 'File has no data rows';
                    return 0;
                }

                // First row = headers
                $header = array_map(fn($h) => strtolower(trim($h ?? '')), $rows[0]);

                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    $data = [];
                    foreach ($header as $colIdx => $colName) {
                        $data[$colName] = $row[$colIdx] ?? '';
                    }

                    if (empty(trim($data['student_name'] ?? '')) || empty(trim($data['index_no'] ?? ''))) {
                        $errors[] = "Row " . ($i + 1) . ": Missing student_name or index_no";
                        continue;
                    }

                    ExamResult::updateOrCreate([
                        'index_no'     => trim($data['index_no'] ?? ''),
                        'term'         => $term,
                        'grade'        => $grade,
                        'subject'      => trim($data['subject'] ?? ''),
                    ], [
                        'student_name' => trim($data['student_name'] ?? ''),
                        'marks'        => is_numeric($data['marks'] ?? '') ? (float)$data['marks'] : null,
                        'result_grade' => trim($data['result_grade'] ?? $data['grade_result'] ?? ''),
                        'rank'         => is_numeric($data['rank'] ?? '') ? (int)$data['rank'] : null,
                        'year'         => $year,
                        'institute_id' => $instituteId,
                    ]);
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = 'Excel parse error: ' . $e->getMessage();
            }
        }

        return $imported;
    }

    // ─── ADMIN: Export results to CSV ───
    public function export(Request $request)
    {
        $query = ExamResult::query();

        if ($request->term) $query->where('term', $request->term);
        if ($request->grade) $query->where('grade', $request->grade);
        if ($request->year) $query->where('year', $request->year);

        $results = $query->orderBy('index_no')->orderBy('subject')->get();

        $csvContent = "student_name,index_no,term,grade,subject,marks,result_grade,rank,year\n";
        foreach ($results as $r) {
            $csvContent .= "\"{$r->student_name}\",\"{$r->index_no}\",\"{$r->term}\",\"{$r->grade}\",\"{$r->subject}\",{$r->marks},\"{$r->result_grade}\",{$r->rank},\"{$r->year}\"\n";
        }

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="exam_results_export.csv"',
        ]);
    }

    // ─── ADMIN: Terms CRUD ───
    public function listTerms()
    {
        $terms = ExamTerm::orderBy('name')->get();
        return response()->json(['data' => $terms]);
    }

    public function storeTerm(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        
        $term = ExamTerm::firstOrCreate([
            'name' => $request->name,
            'institute_id' => $request->user()?->institute_id,
        ]);

        return response()->json(['message' => 'Term added', 'data' => $term], 201);
    }

    public function destroyTerm($id)
    {
        $term = ExamTerm::findOrFail($id);
        $term->delete();

        return response()->json(['message' => 'Term deleted']);
    }

    // ─── PRIVATE: PDF Parsing ───
    private function parsePdf($file, $term, $grade, $year, $instituteId, &$errors)
    {
        $imported = 0;
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();

            if (empty(trim($text))) {
                $errors[] = 'PDF-la readable text illa, please typed PDF or Excel use pannunga';
                return 0;
            }

            $lines = explode("\n", $text);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Split by tabs, pipes, or multiple spaces
                $parts = preg_split('/[\t|]+|\s{2,}/', $line);
                
                if (count($parts) >= 5) {
                    $index_no = trim($parts[0]);
                    $student_name = trim($parts[1]);
                    $subject = trim($parts[2]);
                    $marks = trim($parts[3]);
                    $result_grade = trim($parts[4]);
                    $rank = isset($parts[5]) ? trim($parts[5]) : null;

                    // Header row skip logic simply by checking if marks is numeric or result_grade is one char
                    if (strtolower($index_no) === 'index_no' || strtolower($student_name) === 'student_name') continue;

                    if ($index_no && $student_name && $subject) {
                        ExamResult::updateOrCreate([
                            'index_no'     => $index_no,
                            'term'         => $term,
                            'grade'        => $grade,
                            'subject'      => $subject,
                        ], [
                            'student_name' => $student_name,
                            'marks'        => is_numeric($marks) ? (float)$marks : null,
                            'result_grade' => $result_grade,
                            'rank'         => is_numeric($rank) ? (int)$rank : null,
                            'year'         => $year,
                            'institute_id' => $instituteId,
                        ]);
                        $imported++;
                    }
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'PDF parse error: ' . $e->getMessage();
        }
        return $imported;
    }

    // ─── PRIVATE: Word DOCX Parsing ───
    private function parseWord($file, $term, $grade, $year, $instituteId, &$errors)
    {
        $imported = 0;
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getPathname());
            $tables = [];

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        $tables[] = $element;
                    }
                }
            }

            if (empty($tables)) {
                $errors[] = 'Word document-la table structure illa, please use proper table.';
                return 0;
            }

            // Parse first table
            foreach ($tables[0]->getRows() as $rowIndex => $row) {
                if ($rowIndex === 0) continue; // Skip header

                $cells = $row->getCells();
                if (count($cells) >= 5) {
                    $index_no = trim($this->getCellText($cells[0]));
                    $student_name = trim($this->getCellText($cells[1]));
                    $subject = trim($this->getCellText($cells[2]));
                    $marks = trim($this->getCellText($cells[3]));
                    $result_grade = trim($this->getCellText($cells[4]));
                    $rank = isset($cells[5]) ? trim($this->getCellText($cells[5])) : null;

                    if (empty($index_no) || empty($student_name)) continue;

                    ExamResult::updateOrCreate([
                        'index_no'     => $index_no,
                        'term'         => $term,
                        'grade'        => $grade,
                        'subject'      => $subject,
                    ], [
                        'student_name' => $student_name,
                        'marks'        => is_numeric($marks) ? (float)$marks : null,
                        'result_grade' => $result_grade,
                        'rank'         => is_numeric($rank) ? (int)$rank : null,
                        'year'         => $year,
                        'institute_id' => $instituteId,
                    ]);
                    $imported++;
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Word doc parse error: ' . $e->getMessage();
        }
        return $imported;
    }

    private function getCellText($cell)
    {
        $text = '';
        foreach ($cell->getElements() as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($element->getElements() as $t) {
                    if ($t instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text .= $t->getText();
                    }
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                $text .= $element->getText();
            }
        }
        return $text;
    }
}
