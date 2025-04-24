<?php

namespace App\Http\Controllers\api\user\V1;


use App\Http\Controllers\Controller;
use App\Models\PlayerRequest;
use App\Models\Teams;
use App\Models\Players;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\PlayerRequestResource;

class PlayerRequestController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['sender', 'receiver'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:match_date,starting_time',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'date' => 'nullable|date'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = PlayerRequest::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $requests = $query->paginate($paginationSize);

        return PlayerRequestResource::collection($requests);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['sender', 'receiver'];
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

        $query = PlayerRequest::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $playerRequest = $query->find($id);

        if (!$playerRequest) {
            return response()->json(['message' => 'Player Request not found'], 404);
        }

        return new PlayerRequestResource($playerRequest);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'sender' => 'required|integer|exists:players,id_player',
                'receiver' => 'required|integer',
                'match_date' => 'required|date',
                'starting_time' => 'required|date_format:H:i:s',
                'message' => 'nullable|string|max:50',
                'type' => 'nullable|string|in:PLAYER_REQUEST,TEAM_REQUEST'
            ]);

            // If this is a team request, we need to make sure receiver is valid team ID
            // and then find its captain to set as the actual receiver
            if (isset($validatedData['type']) && $validatedData['type'] === 'TEAM_REQUEST') {
                $team = Teams::find($validatedData['receiver']);

                if (!$team) {
                    return response()->json([
                        'error' => [
                            'receiver' => ['The selected team does not exist.']
                        ]
                    ], 422);
                }

                if (!$team->capitain) {
                    return response()->json([
                        'error' => [
                            'receiver' => ['The selected team does not have a captain.']
                        ]
                    ], 422);
                }

                // Set the receiver to be the team captain
                $validatedData['receiver'] = $team->capitain;
            } else {
                // For regular player requests, make sure the receiver exists
                $receiverExists = Players::where('id_player', $validatedData['receiver'])->exists();

                if (!$receiverExists) {
                    return response()->json([
                        'error' => [
                            'receiver' => ['The selected receiver player does not exist.']
                        ]
                    ], 422);
                }
            }
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        }

        try {
            $playerRequest = PlayerRequest::create($validatedData);
            return response()->json([
                'message' => 'Player Request created successfully',
                'data' => new PlayerRequestResource($playerRequest)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Player Request',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id): JsonResponse
    {
        $playerRequest = PlayerRequest::find($id);

        if (!$playerRequest) {
            return response()->json(['message' => 'Player Request not found'], 404);
        }

        try {
            $playerRequest->delete();
            return response()->json([
                'message' => 'Player Request deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Player Request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('date')) {
            $query->whereDate('match_date', $request->input('date'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('message', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'match_date');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['match_date', 'starting_time'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('match_date', 'desc')
                  ->orderBy('starting_time', 'desc');
        }
    }
}
