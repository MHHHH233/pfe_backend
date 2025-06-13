<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Academie;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AcademieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $academies = Academie::all();
        return response()->json($academies);
    }

    public function show($id): JsonResponse
    {
        $academie = Academie::find($id);

        if (!$academie) {
            return response()->json(['message' => 'Academie not found'], 404);
        }

        return response()->json($academie);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_creation' => 'required|date',
                'plan_base' => 'nullable|numeric',
                'plan_premium' => 'nullable|numeric',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $academie = Academie::create($validatedData);

        return response()->json([
            'message' => 'Academie created successfully',
            'data' => $academie
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date_creation' => 'required|date',
                'plan_base' => 'nullable|numeric',
                'plan_premium' => 'nullable|numeric',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $academie = Academie::find($id);

        if (!$academie) {
            return response()->json(['message' => 'Academie not found'], 404);
        }

        $academie->update($validatedData);

        return response()->json([
            'message' => 'Academie updated successfully',
            'data' => $academie
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $academie = Academie::find($id);

        if (!$academie) {
            return response()->json(['message' => 'Academie not found'], 404);
        }

        $academie->delete();

        return response()->json(['message' => 'Academie deleted successfully'], 204);
    }
}
