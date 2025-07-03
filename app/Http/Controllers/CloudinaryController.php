<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Media;
use Cloudinary\Cloudinary;

class CloudinaryController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = app('cloudinary');
    }

    /**
     * Upload media to Cloudinary and store info in database
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|in:image,video',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $type = $request->input('type');
            $folder = $type === 'image' ? 'images' : 'videos';

            // Upload to Cloudinary
            $uploadResult = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'folder' => $folder,
                    'resource_type' => $type
                ]
            );

            // Create media record in database
            $media = Media::create([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'type' => $type,
                'url' => $uploadResult['secure_url'],
                'public_id' => $uploadResult['public_id'],
                'format' => $uploadResult['format'],
                'size' => $uploadResult['bytes']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => $media,
                'cloudinary' => $uploadResult
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all media from database
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        $media = Media::orderBy('created_at', 'desc')->get();
        return response()->json($media);
    }

    /**
     * Get media by type
     *
     * @param  string  $type
     * @return \Illuminate\Http\Response
     */
    public function getByType($type)
    {
        if (!in_array($type, ['image', 'video'])) {
            return response()->json(['error' => 'Invalid media type'], 400);
        }

        $media = Media::where('type', $type)
                      ->orderBy('created_at', 'desc')
                      ->get();
                      
        return response()->json($media);
    }

    /**
     * Delete media from Cloudinary and database
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $media = Media::find($id);
        
        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        try {
            // Delete from Cloudinary
            $this->cloudinary->uploadApi()->destroy($media->public_id, [
                'resource_type' => $media->type
            ]);
            
            // Delete from database
            $media->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }
} 