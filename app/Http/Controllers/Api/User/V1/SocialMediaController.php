<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\user\SocialMediaResource;
use App\Models\SocialMedia;
use Illuminate\Http\Request;

class SocialMediaController extends Controller
{
    /**
     * Display all social media information.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $socialMedia = SocialMedia::first();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Social media information retrieved successfully',
            'data' => new SocialMediaResource($socialMedia)
        ]);
    }
}
