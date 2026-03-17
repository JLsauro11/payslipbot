<?php

namespace App\Http\Controllers;
use App\Models\Area;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
class AreaController extends Controller
{
    public function index() {
        return view('area.index');
    }

    public function data()
    {
        $areas = Area::select('id', 'name')->get();
        return response()->json(['data' => $areas]);
    }

    public function show(Area $area)
    {
        return response()->json($area);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area_name' => 'required|string|max:255|unique:areas,name',
        ],[
            'area_name.required' => 'Area name is required.',
            'area_name.string' => 'Area name must be a valid text.',
            'area_name.max' => 'Area name cannot exceed 255 characters.',
            'area_name.unique' => 'This area name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        Area::create(['name' => $request->area_name]);
        return response()->json([
            'status' => true,
            'validation' => false,
            'message' => "Area added successfully!"
        ]);
    }

    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'area_name' => 'required|string|max:255|unique:areas,name,' . $id,
        ], [
            'area_name.required' => 'Area name is required.',
            'area_name.string' => 'Area name must be a valid text.',
            'area_name.max' => 'Area name cannot exceed 255 characters.',
            'area_name.unique' => 'This area name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validation' => true,
                'errors' => $validator->errors()
            ], 422);
        }

        $area->update(['name' => $request->area_name]);
        return response()->json([
            'status' => true,
            'validation' => false,
            'message' => "Area updated successfully!"
        ]);
    }

    public function destroy(Area $area)
    {
        $area->delete();  // ✅ Laravel auto-finds by ID
        return response()->json([
            'status' => true,
            'message' => "Area deleted successfully!"
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'No areas selected'
            ], 400);
        }

        $deletedCount = Area::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => true,
            'message' => "{$deletedCount} area(s) deleted successfully!"
        ]);
    }




}
