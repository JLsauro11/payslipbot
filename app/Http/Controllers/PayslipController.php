<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use DateTime;
use Carbon\Carbon;

class PayslipController extends Controller
{
    public function index()
    {
        return view('payslip.index');
    }

    public function data(Request $request)
    {
        $query = Payslip::with('employee')->select('payslips.*');

        // ✅ FIXED: Parse FILTER dates AND convert DB format for TEXT field
        $startDate = null;
        if ($request->filled('start_date')) {
            try {
                $date = \Carbon\Carbon::createFromFormat('m/d/Y', $request->start_date);
                if ($date && $date->format('m/d/Y') === $request->start_date) {
                    $startDate = $date->format('m/d/Y');  // ✅ SAME FORMAT AS DB!
                }
            } catch (\Exception $e) {
                \Log::error('Invalid start_date: ' . $request->start_date);
            }
        }

        $endDate = null;
        if ($request->filled('end_date')) {
            try {
                $date = \Carbon\Carbon::createFromFormat('m/d/Y', $request->end_date);
                if ($date && $date->format('m/d/Y') === $request->end_date) {
                    $endDate = $date->format('m/d/Y');  // ✅ SAME FORMAT AS DB!
                }
            } catch (\Exception $e) {
                \Log::error('Invalid end_date: ' . $request->end_date);
            }
        }

        // Apply filters - TEXT field comparison
        if ($startDate) {
            $query->where('payslip_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('payslip_date', '<=', $endDate);
        }

        // Global search - FIX date search too!
        if ($request->filled('search.value')) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                })->orWhere('payslip_date', 'like', "%{$search}%");
            });
        }

        // Column ordering
        $orderColumn = $request->input('columns.' . ($request->order[0]['column'] ?? 1) . '.name', 'id');
        $orderDir = $request->input('order.0.dir', 'desc');

        $totalRecords = Payslip::count();
        $filteredRecords = clone $query;
        $filteredRecords = $filteredRecords->count();

        $payslips = $query
            ->orderBy($orderColumn, $orderDir)
            ->skip($request->start ?? 0)
            ->take($request->length ?? 25)
            ->get();

        return response()->json([
            'draw' => (int)($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $payslips->map(function ($payslip) {
                $employee = $payslip->employee;

                $fname   = $employee?->first_name ?? '';
            $lname   = $employee?->last_name ?? '';
            $middle  = $employee?->middle_initial ? ' ' . $employee->middle_initial : '';
            $suffix  = $employee?->suffix ? ' ' . trim($employee->suffix) : '';

            if ($suffix) {
                $name = trim("{$lname}, {$fname}{$suffix}{$middle}");
            } else {
                $name = trim("{$lname}, {$fname}{$middle}");
            }

            return [
                'id' => $payslip->id,
                'employee_id' => $payslip->employee_id,
                'name' => $name ?: '-',
                'payslip' => $payslip->payslip ? $payslip->payslip : '-',
                'payslip_date' => $payslip->payslip_date ?: null,
            ];
        })
        ]);
    }

    public function multiStore(Request $request)
    {
        $files = $request->file('payslip_files');
        if (!$files || count($files) === 0) {
            return response()->json(['status' => false, 'message' => 'No files uploaded!'], 400);
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($files as $file) {
            try {
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                // ✅ Space check
                if (preg_match('/EMP\s+(\d+)/', $filename, $spaceMatches)) {
                    $results['failed']++;
                    $results['errors'][] = "❌ {$file->getClientOriginalName()}: 'EMP{$spaceMatches[1]}' contains space";
                    continue;
                }

                // ✅ Flexible regex
                if (!preg_match('/(.+?)[_^](\d{8})_(\d{8})_EMP(\d+)/i', $filename, $dateMatches)) {
                    $results['failed']++;
                    $results['errors'][] = "❌ {$file->getClientOriginalName()}: Invalid format";
                    continue;
                }

                $areaFromFile = trim($dateMatches[1]);
                $startDateStr = $dateMatches[2];
                $endDateStr = $dateMatches[3];
                $fileEmployeeBio = $dateMatches[4];

                // ✅ Name extraction
                $namePart = substr($filename, strpos($filename, "EMP{$fileEmployeeBio}") + strlen("EMP{$fileEmployeeBio}"));
                $namePart = trim(ltrim($namePart, '_^'));
                $fileNameFromFilename = trim($namePart);

                $startDate = DateTime::createFromFormat('Ymd', $startDateStr);
                $endDate = DateTime::createFromFormat('Ymd', $endDateStr);

                if (!$startDate || !$endDate) {
                    $results['failed']++;
                    $results['errors'][] = "❌ {$file->getClientOriginalName()}: Invalid date";
                    continue;
                }

                $fileEmployeeBioFull = 'EMP' . $fileEmployeeBio;

                $employee = Employee::join('areas', 'employees.area_id', '=', 'areas.id')
                    ->where(function($query) use ($fileEmployeeBioFull, $fileEmployeeBio) {
                        $query->where('employees.bio_number', $fileEmployeeBioFull)
                            ->orWhere('employees.bio_number', $fileEmployeeBio);
                    })
                    ->whereRaw('UPPER(TRIM(areas.name)) = UPPER(TRIM(?))', [$areaFromFile])
                    ->select(
                        'employees.employee_id',
                        'employees.first_name',
                        'employees.last_name',
                        'employees.middle_initial',
                        'employees.suffix'
                    )
                    ->first();

                if (!$employee) {
                    $results['failed']++;
                    $results['errors'][] = "❌ {$file->getClientOriginalName()}: No employee '{$areaFromFile}' EMP{$fileEmployeeBio}";
                    continue;
                }

                // Build formatted name (same logic everywhere)
                $fname   = $employee->first_name ?? '';
                $lname   = $employee->last_name ?? '';
                $middle  = $employee->middle_initial ? ' ' . $employee->middle_initial : '';
                $suffix  = $employee->suffix ? ' ' . trim($employee->suffix) : '';

                if ($suffix) {
                    $name = trim("{$lname}, {$fname}{$suffix}{$middle}");
                } else {
                    $name = trim("{$lname}, {$fname}{$middle}");
                }

// ✅ Name verification using new fields (strict)
                if (!empty($fileNameFromFilename)) {
                    $dbNameUpper = strtoupper($name); // "SAURO, JHON LEWIS A"
                    $fileNameUpper = strtoupper($fileNameFromFilename); // "SAURO, JHON LEWIS JACKSON A"

                    // Extract Last, First only: "Sauro, Jhon Lewis"
                    $dbParts = explode(',', $name, 2);
                    $dbLast = trim($dbParts[0] ?? '');
                    $dbFirst = trim($dbParts[1] ?? '');
                    $dbBase = trim("{$dbLast}, {$dbFirst}");

                    $fileParts = explode(',', $fileNameFromFilename, 2);
                    $fileLast = trim($fileParts[0] ?? '');
                    $fileFirst = trim($fileParts[1] ?? '');
                    $fileBase = trim("{$fileLast}, {$fileFirst}");

                    if (strtoupper($dbBase) !== strtoupper($fileBase)) {
                        $results['failed']++;
                        $results['errors'][] = "❌ {$file->getClientOriginalName()}: Name mismatch. Expected '{$dbBase}', got file name '{$fileBase}'";
                        continue;
                    }
                }


                // ✅ Payslip logic
                $payslipMonth = $endDate->format('m');
                $payslipYear = $endDate->format('Y');
                $endDay = (int)$endDate->format('d');
                $daysInMonth = (int)$endDate->format('t');
                $payslipDay = ($endDay <= 15) ? 15 : $daysInMonth;
                $payslipDateStr = "{$payslipMonth}/{$payslipDay}/{$payslipYear}";
                $payslipDate = DateTime::createFromFormat('m/d/Y', $payslipDateStr);

                $exists = Payslip::where('employee_id', $employee->employee_id)
                    ->where('payslip_date', $payslipDateStr)
                    ->exists();

                if ($exists) {
                    $results['failed']++;
                    $results['errors'][] = "⚠️ {$file->getClientOriginalName()}: Duplicate {$payslipDateStr}";
                    continue;
                }

                $saveFilename = $employee->employee_id . '_' .
                    $payslipDate->format('m') . '_' .
                    $payslipDate->format('d') . '_' .
                    $payslipDate->format('Y') . '.pdf';

                $directory = public_path('payslips');
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                $file->move($directory, $saveFilename);

                Payslip::create([
                    'employee_id' => $employee->employee_id,
                    'name' => $name, // keep if you still have `name` in payslips
                    'payslip' => $saveFilename,
                    'payslip_date' => $payslipDateStr
                ]);

                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "❌ {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        $message = $results['success'] > 0
            ? "✅ {$results['success']} uploaded successfully!"
            : "No files uploaded successfully";

        if ($results['failed'] > 0) {
            $message .= "<br>❌ {$results['failed']} failed";
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'details' => $results
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,employee_id',
            'payslip_file' => 'required|file|mimes:pdf|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('payslip_file');
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // ✅ Space check
        if (preg_match('/EMP\s+(\d+)/', $filename, $spaceMatches)) {
            return response()->json([
                'status' => false,
                'message' => "Invalid filename: 'EMP{$spaceMatches[1]}' contains space. Use 'EMP{$spaceMatches[1]}' without space."
            ], 422);
        }

        // ✅ FLEXIBLE regex (handles ^ and _)
        if (!preg_match('/(.+?)[_^](\d{8})_(\d{8})_EMP(\d+)/i', $filename, $matches)) {
            return response()->json([
                'status' => false,
                'message' => 'Filename must be: AREA^YYYYMMDD_YYYYMMDD_EMPXXX^NAME.pdf'
            ], 422);
        }

        $areaFromFile = trim($matches[1]);
        $startDateStr = $matches[2];
        $endDateStr = $matches[3];
        $fileEmployeeBio = $matches[4];

        // ✅ EXTRACT NAME from filename
        $namePart = substr($filename, strpos($filename, "EMP{$fileEmployeeBio}") + strlen("EMP{$fileEmployeeBio}"));
        $fileNameFromFilename = trim(ltrim($namePart, '_^'));

        $startDate = DateTime::createFromFormat('Ymd', $startDateStr);
        $endDate = DateTime::createFromFormat('Ymd', $endDateStr);

        if (!$startDate || !$endDate) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid date format in filename'
            ], 422);
        }

        // ✅ Find employee by FILE area + bio
        $fileEmployeeBioFull = 'EMP' . $fileEmployeeBio;
        $employeeFromFile = Employee::join('areas', 'employees.area_id', '=', 'areas.id')
            ->where(function($query) use ($fileEmployeeBioFull, $fileEmployeeBio) {
                $query->where('employees.bio_number', $fileEmployeeBioFull)
                    ->orWhere('employees.bio_number', $fileEmployeeBio);
            })
            ->whereRaw('UPPER(TRIM(areas.name)) = UPPER(TRIM(?))', [$areaFromFile])
            ->select(
                'employees.employee_id',
                'employees.first_name',
                'employees.last_name',
                'employees.middle_initial',
                'employees.suffix'
            )
            ->first();

        if (!$employeeFromFile) {
            return response()->json([
                'status' => false,
                'message' => "No employee found for '{$areaFromFile}' EMP{$fileEmployeeBio}"
            ], 409);
        }

        // ✅ Get SELECTED employee
        $selectedEmployee = Employee::where('employee_id', $request->employee_id)->firstOrFail();

        // ✅ CHECK 1: Employee ID must match
        if ($selectedEmployee->employee_id !== $employeeFromFile->employee_id) {
            return response()->json([
                'status' => false,
                'message' => "Selected employee {$selectedEmployee->employee_id} doesn't match file employee {$employeeFromFile->employee_id}"
            ], 409);
        }

// ✅ Build expected name from new fields
        $fname   = $employeeFromFile->first_name ?? '';
        $lname   = $employeeFromFile->last_name ?? '';
        $middle  = $employeeFromFile->middle_initial ? ' ' . $employeeFromFile->middle_initial : '';
        $suffix  = $employeeFromFile->suffix ? ' ' . trim($employeeFromFile->suffix) : '';

        if ($suffix) {
            $expectedName = trim("{$lname}, {$fname}{$suffix}{$middle}");
        } else {
            $expectedName = trim("{$lname}, {$fname}{$middle}");
        }

// ✅ Strict base‑name match (Last, First)
        $dbParts = explode(',', $expectedName, 2);
        $dbLast = trim($dbParts[0] ?? '');
        $dbFirst = trim($dbParts[1] ?? '');
        $dbBase = trim("{$dbLast}, {$dbFirst}");

        $fileParts = explode(',', $fileNameFromFilename, 2);
        $fileLast = trim($fileParts[0] ?? '');
        $fileFirst = trim($fileParts[1] ?? '');
        $fileBase = trim("{$fileLast}, {$fileFirst}");

        if (strtoupper($dbBase) !== strtoupper($fileBase)) {
            return response()->json([
                'status' => false,
                'message' => "Name mismatch! File name '{$fileBase}' doesn't match expected '{$dbBase}'"
            ], 409);
        }



        // ✅ Calculate payslip date + rest unchanged...
        $payslipMonth = $endDate->format('m');
        $payslipYear = $endDate->format('Y');
        $endDay = (int)$endDate->format('d');
        $daysInMonth = (int)$endDate->format('t');
        $payslipDay = ($endDay <= 15) ? 15 : $daysInMonth;
        $payslipDateStr = "{$payslipMonth}/{$payslipDay}/{$payslipYear}";
        $payslipDate = DateTime::createFromFormat('m/d/Y', $payslipDateStr);

        $exists = Payslip::where('employee_id', $request->employee_id)
            ->where('payslip_date', $payslipDateStr)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => "Payslip for {$payslipDateStr} already exists!"
            ], 409);
        }

        $saveFilename = $request->employee_id . '_' . $payslipDate->format('m') . '_' . $payslipDate->format('d') . '_' . $payslipDate->format('Y') . '.pdf';

        $directory = public_path('payslips');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        $file->move($directory, $saveFilename);

        $fname   = $employeeFromFile->first_name ?? '';
        $lname   = $employeeFromFile->last_name ?? '';
        $middle  = $employeeFromFile->middle_initial ? ' ' . $employeeFromFile->middle_initial : '';
        $suffix  = $employeeFromFile->suffix ? ' ' . trim($employeeFromFile->suffix) : '';

        if ($suffix) {
            $name = trim("{$lname}, {$fname}{$suffix}{$middle}");
        } else {
            $name = trim("{$lname}, {$fname}{$middle}");
        }

        Payslip::create([
            'employee_id' => $request->employee_id,
            'name' => $name,
            'payslip' => $saveFilename,
            'payslip_date' => $payslipDateStr
        ]);

        return response()->json([
            'status' => true,
            'message' => "Payslip uploaded successfully for {$payslipDateStr}!"
        ]);
    }

    public function show(Payslip $payslip)
    {
        return response()->json($payslip);
    }

    public function update(Request $request, Payslip $payslip)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,employee_id',
            'payslip_file' => 'required|file|mimes:pdf|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('payslip_file');
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        if (preg_match('/EMP\s+(\d+)/', $filename, $spaceMatches)) {
            return response()->json([
                'status' => false,
                'message' => "Invalid filename: 'EMP{$spaceMatches[1]}' contains space. Use 'EMP{$spaceMatches[1]}' without space."
            ], 422);
        }

        if (!preg_match('/(\d{8})_(\d{8})_EMP(\d+)/', $filename, $matches)) {
            return response()->json([
                'status' => false,
                'message' => 'Filename must contain pattern YYYYMMDD_YYYYMMDD_EMPXXX (no spaces in EMP code)'
            ], 422);
        }

        $startDateStr = $matches[1];
        $endDateStr = $matches[2];
        $fileEmployeeBio = $matches[3];
        $areaFromFile = trim(explode('^', $filename)[0]);

        $startDate = DateTime::createFromFormat('Ymd', $startDateStr);
        $endDate = DateTime::createFromFormat('Ymd', $endDateStr);

        if (!$startDate || !$endDate) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid date format in filename'
            ], 422);
        }

        $fileEmployeeBioFull = 'EMP' . $fileEmployeeBio;
        $employeeFromFile = Employee::join('areas', 'employees.area_id', '=', 'areas.id')
            ->where(function($query) use ($fileEmployeeBioFull, $fileEmployeeBio) {
                $query->where('employees.bio_number', $fileEmployeeBioFull)
                    ->orWhere('employees.bio_number', $fileEmployeeBio);
            })
            ->whereRaw('UPPER(areas.name) = ?', [strtoupper($areaFromFile)])
            ->select(
                'employees.employee_id',
                'employees.name as employee_name',
                'employees.bio_number',
                'areas.name as area_name'
            )
            ->first();

        if (!$employeeFromFile) {
            return response()->json([
                'status' => false,
                'message' => "No employee found for area '{$areaFromFile}' and bio 'EMP{$fileEmployeeBio}'"
            ], 409);
        }

        $selectedEmployee = Employee::where('employee_id', $request->employee_id)->firstOrFail();
        if ($selectedEmployee->employee_id !== $employeeFromFile->employee_id) {
            return response()->json([
                'status' => false,
                'message' => "Selected employee {$selectedEmployee->employee_id} doesn't match file employee {$employeeFromFile->employee_id} ({$areaFromFile} EMP{$fileEmployeeBio})"
            ], 409);
        }

        // ✅ FIXED: Create $payslipDate BEFORE using it
        $payslipMonth = $endDate->format('m');
        $payslipYear = $endDate->format('Y');
        $endDay = (int)$endDate->format('d');
        $daysInMonth = (int)$endDate->format('t');
        $payslipDay = ($endDay <= 15) ? 15 : $daysInMonth;
        $payslipDateStr = "{$payslipMonth}/{$payslipDay}/{$payslipYear}";

        $payslipDate = DateTime::createFromFormat('m/d/Y', $payslipDateStr); // ✅ MISSING LINE

        $exists = Payslip::where('employee_id', $request->employee_id)
            ->where('payslip_date', $payslipDateStr)
            ->where('id', '!=', $payslip->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => "Payslip for {$payslipDateStr} already exists!"
            ], 409);
        }

        // Delete old file
        if ($payslip->payslip && file_exists(public_path('payslips/' . $payslip->payslip))) {
            unlink(public_path('payslips/' . $payslip->payslip));
        }

        // ✅ Now $payslipDate is defined and safe to use
        $saveFilename = $request->employee_id . '_' .
            $payslipDate->format('m') . '_' .  // ✅ SAFE
            $payslipDate->format('d') . '_' .  // ✅ SAFE
            $payslipDate->format('Y') . '.pdf'; // ✅ SAFE

        $directory = public_path('payslips');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file->move($directory, $saveFilename);

        $payslip->update([
            'employee_id' => $request->employee_id,
            'name' => $name,
            'payslip' => $saveFilename,
            'payslip_date' => $payslipDateStr
        ]);

        return response()->json([
            'status' => true,
            'message' => "Payslip updated successfully for {$payslipDateStr}!"
        ]);
    }

    public function destroy(Payslip $payslip)
    {
        // ✅ 1. Build full file path
        $filePath = public_path('payslips/' . $payslip->payslip);

        // ✅ 2. Delete file if exists
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        // ✅ 3. Delete database record
        $payslip->delete();

        return response()->json([
            'status' => true,
            'message' => 'Payslip deleted successfully!'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'No items selected'], 400);
        }

        // Delete files first
        $payslips = Payslip::whereIn('id', $ids)->get();
        foreach ($payslips as $payslip) {
            $filePath = public_path('payslips/' . $payslip->payslip);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        // Count actual deletions for accurate message
        $deletedCount = Payslip::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => true,
            'message' => "{$deletedCount} payslip(s) deleted successfully!"
        ]);
    }




}