<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;

class EmployeeController extends Controller
{
    public function index() {
        return view('employee.index');
    }

    public function data()
    {
        $employees = Employee::with(['area', 'position', 'department'])->get()->map(function ($employee) {
            $fname   = $employee->first_name ?? '';
            $lname   = $employee->last_name ?? '';
            $middle  = $employee->middle_initial ? ' ' . $employee->middle_initial : '';
            $suffix  = $employee->suffix ? ' ' . trim($employee->suffix) : '';

            if ($suffix) {
                // If has suffix: "Last, First Suffix Middle Initial"
                $displayName = trim("{$lname}, {$fname}{$suffix}{$middle}");
            } else {
                // If no suffix: "Last, First Middle Initial"
                $displayName = trim("{$lname}, {$fname}{$middle}");
            }

            return [
                'id'           => $employee->id,
                'employee_id'  => $employee->employee_id,
                'bio_number'   => $employee->bio_number,
                'area'         => $employee->area?->name ?? '-',
            'display_name' => $displayName ?: '-',     // e.g. "Doe, John Jr. J." or "Doe, John J."
            'password'     => $employee->password,
            'position'     => $employee->position?->name ?? '-',
            'department'   => $employee->department?->name ?? '-',
            'status'       => $employee->status
        ];
    });

        return response()->json($employees);
    }

    public function employeesForPayslip()
    {
        $employees = Employee::with(['area'])->get()->map(function ($employee) {
            $fname   = $employee->first_name ?? '';
            $lname   = $employee->last_name ?? '';
            $middle  = $employee->middle_initial ? ' ' . strtoupper($employee->middle_initial) : '';
            $suffix  = $employee->suffix ? ' ' . trim($employee->suffix) : '';

            if ($suffix) {
                // With suffix: "Last, First Suffix Middle Initial"
                $formattedName = trim("{$lname}, {$fname}{$suffix}{$middle}");
            } else {
                // Without suffix: "Last, First Middle Initial"
                $formattedName = trim("{$lname}, {$fname}{$middle}");
            }

            return [
                'employee_id' => $employee->employee_id,
                'name'        => $formattedName ?: '-',      // e.g. "Doe, John Jr. J." or "Doe, John J."
                'bio_number'  => $employee->bio_number,
                'area_name'   => $employee->area?->name ?? '-'
        ];
    });

        return response()->json(['data' => $employees]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => 'required|unique:employees,employee_id|max:20',
            'bio_number' => 'required|string|max:20',
            'area_id' => 'required|exists:areas,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_initial' => 'nullable|string|max:10',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|in:Active,Inactive',
            'password' => 'nullable|string|max:20'
        ]);

        $validator->after(function ($validator) use ($request) {
            $exists = Employee::where('bio_number', $request->bio_number)
                ->where('area_id', $request->area_id)
                ->exists();
            if ($exists) {
                $validator->errors()->add('bio_number', 'This BIO number already exists in the selected area.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $password = $request->password ?: 'RS8-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        Employee::create([
            'employee_id' => $request->employee_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_initial' => $request->middle_initial,
            'suffix' => $request->suffix, // ✅ new
            'bio_number' => $request->bio_number,
            'area_id' => $request->area_id,
            'position_id' => $request->position_id,
            'department_id' => $request->department_id,
            'password' => $password,
            'status' => $request->status
        ]);


        return response()->json([
            'status' => true,
            'message' => "Employee {$request->first_name} {$request->last_name} added successfully!"
        ]);
    }

    public function show(Employee $employee)
    {
        $employee->load(['area', 'position', 'department']);

        return response()->json([
            'id' => $employee->id,
            'employee_number' => $employee->employee_id,
            'bio_number' => $employee->bio_number ?? '',
            'first_name' => $employee->first_name ?? '',
            'last_name' => $employee->last_name ?? '',
            'middle_initial' => $employee->middle_initial ?? '',
            'suffix' => $employee->suffix ?? '', // ✅ return suffix
            'position_id' => $employee->position_id,
            'department_id' => $employee->department_id,
            'area_id' => $employee->area_id,
            'status' => $employee->status ?? 'Active'
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => [
                'required',
                'max:20',
                Rule::unique('employees', 'employee_id')->ignore($employee->id)
            ],
            'bio_number' => 'required|string|max:20',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_initial' => 'nullable|string|max:10',
            'area_id' => 'required|exists:areas,id',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|in:Active,Inactive',
            'password' => 'nullable|min:4'
        ]);

        $validator->after(function ($validator) use ($request, $employee) {
            $exists = Employee::where('bio_number', $request->bio_number)
                ->where('area_id', $request->area_id)
                ->where('id', '!=', $employee->id)
                ->exists();
            if ($exists) {
                $validator->errors()->add('bio_number', 'This BIO number already exists in the selected area.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [
            'employee_id' => $request->employee_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_initial' => $request->middle_initial,
            'suffix' => $request->suffix, // ✅ include suffix
            'bio_number' => $request->bio_number,
            'area_id' => $request->area_id,
            'position_id' => $request->position_id,
            'department_id' => $request->department_id,
            'status' => $request->status
        ];


        if ($request->filled('password')) {
            $updateData['password'] = $request->password;
        }

        $employee->update($updateData);

        return response()->json([
            'status' => true,
            'message' => "Employee updated successfully!"
        ]);
    }

    public function destroy(Employee $employee)
    {
        // ✅ 1. Get ALL payslips for this employee first
        $payslips = Payslip::where('id', $employee->id)->get();

        // ✅ 2. Delete ALL associated files
        foreach ($payslips as $payslip) {
            $filePath = public_path('payslips/' . $payslip->payslip);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        // ✅ 3. Delete ALL payslips (DB records)
        Payslip::where('id', $employee->id)->delete();

        // ✅ 4. Delete employee
        $employee->delete();

        return response()->json([
            'status' => true,
            'message' => 'Employee and all payslips deleted successfully!'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'No items selected'], 400);
        }

        $deletedCount = 0;

        // ✅ FIX: Process each ID individually OR pass $ids to closure
        foreach ($ids as $id) {
            $employee = Employee::find($id);
            if ($employee) {
                // Delete payslips first
                $payslips = Payslip::where('id', $employee->id)->get();
                foreach ($payslips as $payslip) {
                    $filePath = public_path('payslips/' . $payslip->payslip);
                    if (File::exists($filePath)) {
                        File::delete($filePath);
                    }
                }
                Payslip::where('id', $employee->id)->delete();
                $employee->delete();
                $deletedCount++;
            }
        }

        return response()->json([
            'status' => true,
            'message' => "{$deletedCount} employee(s) deleted successfully!"
        ]);
    }

}
