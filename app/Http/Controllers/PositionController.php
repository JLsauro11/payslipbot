<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Position;
use Illuminate\Support\Facades\Validator;
class PositionController extends Controller
{
    public function index() {
        return view('position.index');
    }

    public function data()
    {
        $positions = Position::select('id', 'name')->get();
        return response()->json(['data' => $positions]);
    }

    public function show(Position $position)
    {
        return response()->json($position);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'position_name' => 'required|string|max:255|unique:positions,name',
        ],[
            'position_name.required' => 'Position name is required.',
            'position_name.string' => 'Position name must be a valid text.',
            'position_name.max' => 'Position name cannot exceed 255 characters.',
            'position_name.unique' => 'This position name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        Position::create(['name' => $request->position_name]);
        return response()->json([
            'status' => true,
            'validation' => false,
            'message' => "Position added successfully!"
        ]);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'position_name' => 'required|string|max:255|unique:positions,name,' . $id,
        ], [
            'position_name.required' => 'Position name is required.',
            'position_name.string' => 'Position name must be a valid text.',
            'position_name.max' => 'Position name cannot exceed 255 characters.',
            'position_name.unique' => 'This position name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $position->update(['name' => $request->position_name]);
        return response()->json([
            'status' => true,
            'validation' => false,
            'message' => "Position updated successfully!"
        ]);
    }

    public function destroy(Position $position)
    {
        $position->delete();  // ✅ Laravel auto-finds by ID
        return response()->json([
            'status' => true,
            'message' => "Position deleted successfully!"
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'No positions selected'
            ], 400);
        }

        $deletedCount = Position::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => true,
            'message' => "{$deletedCount} position(s) deleted successfully!"
        ]);
    }
}
