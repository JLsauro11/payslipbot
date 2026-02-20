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
        $employees = Employee::select('id', 'employee_id', 'name', 'position', 'department', 'status')->get();

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

        Employee::create([
            'employee_id' => $request->employee_number,
            'name' => $request->name,
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
            'status' => 'required|in:Active,Inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $employee->update([
            'employee_id' => $request->employee_number,
            'name' => $request->name,
            'position' => $request->position,
            'department' => $request->department,
            'status' => $request->status
        ]);

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


}
