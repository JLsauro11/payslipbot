<?php
namespace App\Http\Controllers;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index() {
        return view('department.index');
    }

    public function data()
    {
        $departments = Department::select('id', 'name')->get();
        return response()->json(['data' => $departments]);
    }

    public function show(Department $department)
    {
        return response()->json($department);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:255|unique:departments,name',
            ],[
                'department_name.required' => 'Department name is required.',
                'department_name.string' => 'Department name must be a valid text.',
                'department_name.max' => 'Department name cannot exceed 255 characters.',
                'department_name.unique' => 'This department name already exists.',
            ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        Department::create(['name' => $request->department_name]);
        return response()->json([
            'status' => true,
            'validation' => false,
            'message' => "Department added successfully!"
        ]);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:255|unique:departments,name,' . $id,
        ], [
            'department_name.required' => 'Department name is required.',
            'department_name.string' => 'Department name must be a valid text.',
            'department_name.max' => 'Department name cannot exceed 255 characters.',
            'department_name.unique' => 'This department name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $department->update(['name' => $request->department_name]);
        return response()->json([
            'status' => true,
            'validation' => false,
            'message' => "Department updated successfully!"
        ]);
    }

    public function destroy(Department $department)
    {
        $department->delete();  // ✅ Laravel auto-finds by ID
        return response()->json([
            'status' => true,
            'message' => "Department deleted successfully!"
        ]);
    }


    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'No departments selected'
            ], 400);
        }

        $deletedCount = Department::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => true,
            'message' => "{$deletedCount} department(s) deleted successfully!"
        ]);
    }
}
