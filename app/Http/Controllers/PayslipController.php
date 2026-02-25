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
                    $sub->where('name', 'like', "%{$search}%")
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

        $payslips = $query->orderBy($orderColumn, $orderDir)
            ->skip($request->start ?? 0)
            ->take($request->length ?? 25)
            ->get();

        return response()->json([
            'draw' => (int)($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $payslips->map(function ($payslip) {
                return [
                    'id' => $payslip->id,
                    'employee_id' => $payslip->employee->employee_id ?? $payslip->employee_id,
                    'name' => $payslip->employee->name ?? $payslip->name,
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
            return response()->json([
                'status' => false,
                'message' => 'No files uploaded!'
            ], 400);
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($files as $file) {
            try {
                $filename = $file->getClientOriginalName();
                $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

                // Parse filename: EMPLOYEEID_MM_DD_YYYY.pdf
                $parts = explode('_', $nameWithoutExt);

                if (count($parts) !== 4) {
                    $results['failed']++;
                    $results['errors'][] = "❌ {$filename}: Invalid format. Use EMPLOYEEID_MM_DD_YYYY.pdf";
                    continue;
                }

                [$employeeId, $month, $day, $year] = $parts;
                $payslipDate = sprintf('%02d/%02d/%04d', $month, $day, $year);

                // Validate date format and business rules
                $date = DateTime::createFromFormat('m/d/Y', $payslipDate);
                if (!$date || $date->format('m/d/Y') !== $payslipDate) {
                    $results['failed']++;
                    $results['errors'][] = "❌ {$filename}: Invalid date format";
                    continue;
                }

                $dayNum = (int)$date->format('d');
                $daysInMonth = (int)$date->format('t');

                if ($dayNum !== 15 && $dayNum !== $daysInMonth) {
                    $results['failed']++;
                    $results['errors'][] = "❌ {$filename}: Date must be 15th or last day of month";
                    continue;
                }

                // Check if employee exists
                $employee = Employee::where('employee_id', $employeeId)->first();
                if (!$employee) {
                    $results['failed']++;
                    $results['errors'][] = "❌ {$filename}: Employee {$employeeId} not found";
                    continue;
                }

                // Check for duplicate payslip
                $exists = Payslip::where('employee_id', $employeeId)
                    ->where('payslip_date', $payslipDate)
                    ->exists();

                if ($exists) {
                    $results['failed']++;
                    $results['errors'][] = "⚠️ {$filename}: Payslip already exists for {$employeeId} on {$payslipDate}";
                    continue;
                }

                // Generate final filename (use original for consistency)
                $finalFilename = $filename;

                // Ensure directory exists
                $directory = public_path('payslips');
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                // Move file
                $file->move($directory, $finalFilename);

                // Create record
                Payslip::create([
                    'employee_id' => $employeeId,
                    'name' => $employee->name,
                    'payslip' => $finalFilename,
                    'payslip_date' => $payslipDate
                ]);

                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "❌ {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

// ✅ FIXED MESSAGE LOGIC
        $message = '';
        if ($results['success'] > 0) {
            $message .= "✅ {$results['success']} payslip(s) uploaded successfully!";
        }
        if ($results['failed'] > 0) {
            if ($results['success'] > 0) {
                $message .= ' ';  // Add space only if there's success message
            }
            $message .= "❌ {$results['failed']} failed. Check details below:";
        }

// Clean up empty message
        if (empty($message)) {
            $message = 'No valid files processed.';
        }

        return response()->json([
            'status' => $results['success'] > 0,
            'message' => $message,
            'details' => $results
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,employee_id',
            'payslip_file' => 'required|file|mimes:pdf|max:10000',
            'payslip_date' => [
                'required',
                'date_format:m/d/Y',
                function ($attribute, $value, $fail) {
                    $date = DateTime::createFromFormat('m/d/Y', $value);
                    if (!$date) {
                        return $fail('Invalid date format. Use MM/DD/YYYY.');
                    }

                    $day = (int)$date->format('d');
                    $daysInMonth = (int)$date->format('t');

                    if ($day !== 15 && $day !== $daysInMonth) {
                        $fail("Payslip date must be the 15th ({$date->format('m/15/Y')}) OR last day of the month ({$date->format('m/' . $daysInMonth . '/Y')}).");
                    }
                },
                // ✅ NEW: Validate filename matches payslip date
                function ($attribute, $value, $fail) use ($request) {
                    $file = $request->file('payslip_file');
                    if (!$file) return;

                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                    // Expected pattern: EMPLOYEEID_MM_DD_YYYY
                    if (!preg_match('/^(\d+)_(\d{2})_(\d{2})_(\d{4})$/', $filename, $matches)) {
                        return $fail('Payslip filename must follow format: EMPLOYEEID_MM_DD_YYYY.pdf');
                    }

                    $fileEmployeeId = $matches[1];
                    $fileMonth = sprintf('%02d', (int)$matches[2]);
                    $fileDay = sprintf('%02d', (int)$matches[3]);
                    $fileYear = $matches[4];

                    // Compare with form employee_id and payslip_date
                    $formEmployeeId = $request->employee_id;
                    $formDate = DateTime::createFromFormat('m/d/Y', $value);
                    $formMonth = $formDate->format('m');
                    $formDay = $formDate->format('d');
                    $formYear = $formDate->format('Y');

                    if ($fileEmployeeId !== $formEmployeeId ||
                        $fileMonth !== $formMonth ||
                        $fileDay !== $formDay ||
                        $fileYear !== $formYear) {

                        $expectedFilename = "{$formEmployeeId}_{$formMonth}_{$formDay}_{$formYear}.pdf";
                        $fail("Filename date ({$fileMonth}/{$fileDay}/{$fileYear}) doesn't match payslip date ({$value}). Expected: {$expectedFilename}");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        // Rest of your existing code remains the same...
        $date = DateTime::createFromFormat('m/d/Y', $request->payslip_date);
        $exists = Payslip::where('employee_id', $request->employee_id)
            ->where('payslip_date', $request->payslip_date)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Payslip for this employee and date already exists!'
            ], 409);
        }

        $employee = Employee::where('employee_id', $request->employee_id)->firstOrFail();
        $file = $request->file('payslip_file');

        $filename = $request->employee_id . '_' .
            $date->format('m') . '_' .
            $date->format('d') . '_' .
            $date->format('Y') . '.pdf';

        $directory = public_path('payslips');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = $file->move($directory, $filename);

        Payslip::create([
            'employee_id' => $request->employee_id,
            'name' => $employee->name,
            'payslip' => $filename,
            'payslip_date' => $date->format('m/d/Y')
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payslip uploaded successfully!'
        ]);
    }

    public function show(Payslip $payslip)
    {
        return response()->json($payslip);
    }

    public function update(Request $request, Payslip $payslip)
    {
        $validator = Validator::make($request->all(), [
            'payslip_date' => [
                'required',
                'date_format:m/d/Y',
                function ($attribute, $value, $fail) {
                    $date = DateTime::createFromFormat('m/d/Y', $value);
                    if (!$date) {
                        return $fail('Invalid date format. Use MM/DD/YYYY.');
                    }

                    $day = (int)$date->format('d');
                    $daysInMonth = (int)$date->format('t');

                    if ($day !== 15 && $day !== $daysInMonth) {
                        $fail("Payslip date must be the 15th ({$date->format('m/15/Y')}) OR last day of the month ({$date->format('m/' . $daysInMonth . '/Y')}).");
                    }
                },
            ],
            'payslip_file' => 'nullable|file|mimes:pdf|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $newDate = DateTime::createFromFormat('m/d/Y', $request->payslip_date);
        $newFilename = $payslip->employee_id . '_' .
            $newDate->format('m') . '_' .
            $newDate->format('d') . '_' .
            $newDate->format('Y') . '.pdf';

        $data = [
            'payslip_date' => $request->payslip_date,
            'payslip' => $newFilename  // ✅ Correct column name
        ];

        if ($request->hasFile('payslip_file')) {
            $file = $request->file('payslip_file');
            if ($payslip->payslip && file_exists(public_path('payslips/' . $payslip->payslip))) {
                unlink(public_path('payslips/' . $payslip->payslip));
            }
            $file->move(public_path('payslips'), $newFilename);
        }
        else {
            // ✅ FIXED: Correct column name
            $oldFilename = $payslip->payslip;  // ← KEY FIX
            $oldPath = public_path('payslips/' . $oldFilename);
            $newPath = public_path('payslips/' . $newFilename);

            if (file_exists($oldPath)) {
                rename($oldPath, $newPath);
            }
        }

        $payslip->update($data);

        return response()->json([
            'status' => true,
            'message' => "Payslip updated successfully!"
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