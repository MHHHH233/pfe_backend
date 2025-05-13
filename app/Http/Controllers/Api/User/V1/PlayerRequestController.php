<?php

namespace App\Http\Controllers\api\user\V1;


use App\Http\Controllers\Controller;
use App\Models\PlayerRequest;
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
                        $validIncludes = ['sender', 'receiver', 'team', 'sender.compte', 'receiver.compte'];
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
                'date' => 'nullable|date',
                'request_type' => 'nullable|string|in:match,team',
                'status' => 'nullable|string|in:pending,accepted,rejected,expired,cancelled'
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
                        $validIncludes = ['sender', 'receiver', 'team', 'sender.compte', 'receiver.compte'];
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
                'receiver' => 'required|integer|exists:players,id_player',
                'match_date' => 'required_if:request_type,match|nullable|date',
                'starting_time' => 'required_if:request_type,match|nullable|date_format:H:i:s',
                'message' => 'nullable|string|max:255',
                'request_type' => 'nullable|string|in:match,team',
                'team_id' => 'required_if:request_type,team|nullable|exists:teams,id_teams'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            // Set default request type if not provided
            if (!isset($validatedData['request_type'])) {
                $validatedData['request_type'] = 'match';
            }
            
            // Set default values for team invitations
            if ($validatedData['request_type'] === 'team') {
                $validatedData['match_date'] = now()->format('Y-m-d');
                $validatedData['starting_time'] = now()->format('H:i:s');
            }
            
            // Set default expiration date if not provided
            if (!isset($validatedData['expires_at'])) {
                $validatedData['expires_at'] = now()->addDays(7);
            }
            
            $validatedData['status'] = 'pending';
            
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

    /**
     * Accept a player request
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept($id): JsonResponse
    {
        $playerRequest = PlayerRequest::find($id);

        if (!$playerRequest) {
            return response()->json(['message' => 'Player Request not found'], 404);
        }

        if ($playerRequest->status !== PlayerRequest::STATUS_PENDING) {
            return response()->json(['message' => 'This request cannot be accepted'], 400);
        }

        try {
            if ($playerRequest->accept()) {
                return response()->json([
                    'message' => 'Player Request accepted successfully',
                    'data' => new PlayerRequestResource($playerRequest)
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Failed to accept request, it may be expired'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to accept Player Request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a player request
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id): JsonResponse
    {
        $playerRequest = PlayerRequest::find($id);

        if (!$playerRequest) {
            return response()->json(['message' => 'Player Request not found'], 404);
        }

        if ($playerRequest->status !== PlayerRequest::STATUS_PENDING) {
            return response()->json(['message' => 'This request cannot be cancelled'], 400);
        }

        try {
            $playerRequest->status = PlayerRequest::STATUS_CANCELLED;
            $playerRequest->save();
            
            return response()->json([
                'message' => 'Player Request cancelled successfully',
                'data' => new PlayerRequestResource($playerRequest)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel Player Request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get player requests (sent or received)
     * 
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getPlayerRequests(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'player_id' => 'required|integer|exists:players,id_player',
                'type' => 'nullable|string|in:sent,received,all',
                'status' => 'nullable|string|in:pending,accepted,rejected,expired,cancelled,all',
                'request_type' => 'nullable|string|in:match,team,all',
                'include' => 'nullable|string',
                'paginationSize' => 'nullable|integer|min:1'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = PlayerRequest::query();
        $playerId = $request->input('player_id');
        $type = $request->input('type', 'all');
        
        // Filter by player (sender or receiver)
        if ($type === 'sent') {
            $query->where('sender', $playerId);
        } elseif ($type === 'received') {
            $query->where('receiver', $playerId);
        } else {
            $query->where(function($q) use ($playerId) {
                $q->where('sender', $playerId)
                  ->orWhere('receiver', $playerId);
            });
        }
        
        // Apply other filters
        if ($request->has('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('request_type') && $request->input('request_type') !== 'all') {
            $query->where('request_type', $request->input('request_type'));
        }
        
        // Always include these relationships to avoid the error
        $includes = ['sender', 'receiver'];
        
        // Include additional relationships if specified
        if ($request->has('include')) {
            $requestedIncludes = explode(',', $request->input('include'));
            $validIncludes = ['sender', 'receiver', 'team', 'sender.compte', 'receiver.compte'];
            $filteredIncludes = array_intersect($requestedIncludes, $validIncludes);
            
            // Merge with our required includes
            $includes = array_unique(array_merge($includes, $filteredIncludes));
        }
        
        $query->with($includes);
        
        // Apply sorting
        $query->orderBy('created_at', 'desc');
        
        // Paginate results
        $paginationSize = $request->input('paginationSize', 10);
        $requests = $query->paginate($paginationSize);
        
        return PlayerRequestResource::collection($requests);
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('date')) {
            $query->whereDate('match_date', $request->input('date'));
        }
        
        if ($request->has('request_type')) {
            $query->where('request_type', $request->input('request_type'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
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