<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademieCoach;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\AcademieCoachResource;
use Illuminate\Support\Facades\File;

class AcademieCoachController extends Controller
{
    private $imageFolder = 'images/coach';
    private $baseUrl = 'http://localhost:8000/';

    public function __construct()
    {
        // Create the images/coach directory if it doesn't exist
        $path = public_path($this->imageFolder);
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true);
        }
    }

    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['academie'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:nom,id_academie',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'id_academie' => 'nullable|integer'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = AcademieCoach::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $coaches = $query->paginate($paginationSize);

        return AcademieCoachResource::collection($coaches);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['academie'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = AcademieCoach::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $coach = $query->find($id);

        if (!$coach) {
            return response()->json(['message' => 'Coach not found'], 404);
        }

        return new AcademieCoachResource($coach);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_academie' => 'required|integer|exists:academie,id_academie',
                'nom' => 'required|string|max:20',
                'pfp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'description' => 'nullable|string',
                'instagram' => 'nullable|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            if ($request->hasFile('pfp')) {
                $image = $request->file('pfp');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path($this->imageFolder), $imageName);
                $validatedData['pfp'] = $this->baseUrl . $this->imageFolder . '/' . $imageName;
            }

            $coach = AcademieCoach::create($validatedData);
            return response()->json([
                'message' => 'Coach created successfully',
                'data' => new AcademieCoachResource($coach)
            ], 201);
        } catch (\Exception $e) {
            // Clean up uploaded file if creation fails
            if (isset($imageName) && File::exists(public_path($this->imageFolder . '/' . $imageName))) {
                File::delete(public_path($this->imageFolder . '/' . $imageName));
            }
            return response()->json([
                'message' => 'Failed to create Coach',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_academie' => 'required|integer|exists:academie,id_academie',
                'nom' => 'required|string|max:20',
                'pfp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'description' => 'nullable|string',
                'instagram' => 'nullable|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $coach = AcademieCoach::find($id);

        if (!$coach) {
            return response()->json(['message' => 'Coach not found'], 404);
        }

        try {
            if ($request->hasFile('pfp')) {
                // Delete old image if it exists
                if ($coach->pfp && File::exists(public_path($coach->pfp))) {
                    File::delete(public_path($coach->pfp));
                }

                $image = $request->file('pfp');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path($this->imageFolder), $imageName);
                $validatedData['pfp'] = $this->baseUrl . $this->imageFolder . '/' . $imageName;
            }

            $coach->update($validatedData);
            return response()->json([
                'message' => 'Coach updated successfully',
                'data' => new AcademieCoachResource($coach)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Coach',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $coach = AcademieCoach::find($id);

        if (!$coach) {
            return response()->json(['message' => 'Coach not found'], 404);
        }

        try {
            // Delete associated image if it exists
            if ($coach->pfp && File::exists(public_path($coach->pfp))) {
                File::delete(public_path($coach->pfp));
            }

            $coach->delete();
            return response()->json([
                'message' => 'Coach deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Coach',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('id_academie')) {
            $query->where('id_academie', $request->input('id_academie'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('nom', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_coach');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['nom', 'id_academie'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_coach', 'desc');
        }
    }
}