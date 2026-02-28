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
        $employees = Employee::select('id', 'employee_id', 'name', 'password', 'position', 'department', 'status')->get();

        return response()->json([
            'data' => $employees
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => 'required|unique:employees,employee_id|max:20',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'status' => 'required|in:Active,Inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ Auto-generate RS8-XXXX password (4 random digits)
        $password = 'RS8-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);


        Employee::create([
            'employee_id' => $request->employee_number,
            'name' => $request->name,
            'password' => $password,
            'position' => $request->position,
            'department' => $request->department,
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
                Rule::unique('employees', 'employee_id')->ignore($employee->id)
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
        $payslips = Payslip::where('employee_id', $employee->employee_id)->get();

        // ✅ 2. Delete ALL associated files
        foreach ($payslips as $payslip) {
            $filePath = public_path('payslips/' . $payslip->payslip);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        // ✅ 3. Delete ALL payslips (DB records)
        Payslip::where('employee_id', $employee->employee_id)->delete();

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
                $payslips = Payslip::where('employee_id', $employee->employee_id)->get();
                foreach ($payslips as $payslip) {
                    $filePath = public_path('payslips/' . $payslip->payslip);
                    if (File::exists($filePath)) {
                        File::delete($filePath);
                    }
                }
                Payslip::where('employee_id', $employee->employee_id)->delete();
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
