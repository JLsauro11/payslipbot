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
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'bio_number' => $employee->bio_number,
                'area' => $employee->area?->name ?? '-',
            'name' => $employee->name,
            'password' => $employee->password,
            'position' => $employee->position?->name ?? '-',
            'department' => $employee->department?->name ?? '-',
            'status' => $employee->status
        ];
    });

        return response()->json(['data' => $employees]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => 'required|unique:employees,employee_id|max:20',  // ✅ FIXED
            'bio_number' => 'required|string|max:20|unique:employees,bio_number',
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'position_id' => 'required|exists:positions,id',
            'department_id' => 'required|exists:departments,id',  // ✅ departments, not areas
            'status' => 'required|in:Active,Inactive',
            'password' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $password = $request->password ?: 'RS8-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        Employee::create([
            'employee_id' => $request->employee_number,      // ✅ Form → DB mapping
            'bio_number' => $request->bio_number,
            'area_id' => $request->area_id,
            'name' => $request->name,
            'position_id' => $request->position_id,
            'department_id' => $request->department_id,
            'password' => $password,
            'status' => $request->status
        ]);

        return response()->json([
            'status' => true,
            'validation' => false,
            'message' => "Employee added successfully!"
        ]);
    }


    public function show(Employee $employee)
    {
        return response()->json($employee);
    }

    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => [
                'required',
                'max:20',
                Rule::unique('employees', 'id')->ignore($employee->id)
            ],
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'status' => 'required|in:Active,Inactive',
             'password' => 'nullable|min:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [
            'employee_id' => $request->employee_number,
            'name' => $request->name,
            'position' => $request->position,
            'department' => $request->department,
            'status' => $request->status
        ];

        // ✅ Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = $request->password;  // Keep plain text
        }

        $employee->update($updateData);

        return response()->json([
            'status' => true,
            'validation' => false,
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
