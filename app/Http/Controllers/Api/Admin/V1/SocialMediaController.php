<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\SocialMediaResource;
use App\Models\SocialMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'x' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'localisation' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:255',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $socialMedia = SocialMedia::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Social media information created successfully',
            'data' => new SocialMediaResource($socialMedia)
        ]);
    }   
    /**
     * Update the social media information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'x' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'localisation' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:255',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $socialMedia = SocialMedia::first();
        
        if (!$socialMedia) {
            $socialMedia = new SocialMedia();
        }
        
        $socialMedia->fill($request->only([
            'instagram', 'facebook', 'x', 'whatsapp', 
            'email', 'localisation', 'telephone', 'address'
        ]));
        
        $socialMedia->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Social media information updated successfully',
            'data' => new SocialMediaResource($socialMedia)
        ]);
    }
    public function destroy($id)
    {
        $socialMedia = SocialMedia::find($id);
        $socialMedia->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Social media information deleted successfully'
        ]);
    }

}
