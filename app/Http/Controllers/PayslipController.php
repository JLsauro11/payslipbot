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
        $query = Payslip::with('employee');

        // ✅ DATE FILTERING - Compatible with your JS
        if ($request->filled('start_date')) {
            $query->whereDate('payslip_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('payslip_date', '<=', $request->end_date);
        }

        $payslips = $query->get();

        return response()->json([
            'data' => $payslips->map(function ($payslip) {
                return [
                    'id' => $payslip->id,
                    'employee_id' => $payslip->employee->employee_id ?? $payslip->employee_id,
                    'name' => $payslip->employee->name ?? $payslip->name,
                    'payslip' => $payslip->payslip,
                    // ✅ YOUR EXISTING DATE FORMATTING - UNCHANGED
                    'payslip_date' => $payslip->payslip_date ?
                        (is_object($payslip->payslip_date) ?
                            $payslip->payslip_date->format('m/d/Y') :
                            date('m/d/Y', strtotime($payslip->payslip_date))
                        ) : null,
                ];
            })
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
                    $daysInMonth = (int)$date->format('t'); // Total days in month

                    // Only allow 15th OR last day of month
                    if ($day !== 15 && $day !== $daysInMonth) {
                        $fail("Payslip date must be the 15th ({$date->format('m/15/Y')}) OR last day of the month ({$date->format('m/' . $daysInMonth . '/Y')}).");
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

        // ✅ CHECK FOR DUPLICATE FIRST
        $date = DateTime::createFromFormat('m/d/Y', $request->payslip_date);
        $exists = Payslip::where('employee_id', $request->employee_id)
            ->where('payslip_date', $request->payslip_date)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Payslip for this employee and date already exists!'
            ], 409); // 409 Conflict
        }

        $employee = Employee::where('employee_id', $request->employee_id)->firstOrFail();
        $file = $request->file('payslip_file');
        $date = DateTime::createFromFormat('m/d/Y', $request->payslip_date);

        $filename = $request->employee_id . '_' .
            $date->format('m') . '_' .
            $date->format('d') . '_' .
            $date->format('Y') . '.pdf';

        // ✅ CREATE public/payslips/ folder if not exists
        $directory = public_path('payslips');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // ✅ MOVE file directly to public/payslips/
        $path = $file->move($directory, $filename);

        Payslip::create([
            'employee_id' => $request->employee_id,
            'name' => $employee->name,
            'payslip' => $filename,  // ✅ Relative path
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