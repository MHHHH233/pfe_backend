<?php

namespace App\Http\Controllers\api\user\V1;


use App\Http\Controllers\Controller;
use App\Models\Academie;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\AcademieResource;


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
}
