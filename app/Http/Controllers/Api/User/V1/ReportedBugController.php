<?php

namespace App\Http\Controllers\api\user\V1;


use App\Http\Controllers\Controller;
use App\Models\ReportedBug;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\ReportedBugResource;

class ReportedBugController extends Controller
{
   
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_compte' => 'required|integer|exists:compte,id_compte',
                'description' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $bug = ReportedBug::create($validatedData);
            return response()->json([
                'message' => 'Bug report created successfully',
                'data' => new ReportedBugResource($bug)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Bug report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $bug = ReportedBug::find($id);

        if (!$bug) {
            return response()->json(['message' => 'Bug report not found'], 404);
        }

        try {
            $bug->delete();
            return response()->json([
                'message' => 'Bug report deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Bug report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

} 