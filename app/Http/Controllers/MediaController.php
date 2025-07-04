<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    public function index()
    {
        return response()->json(Media::all());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'type' => 'required|in:photo,video,document',
            'category_id' => 'required|integer',
            'file' => 'required|file|max:20480', // max 20MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uploadedFile = $request->file('file');
        $cloudinaryUpload = Cloudinary::upload($uploadedFile->getRealPath(), [
            'folder' => 'media_academie',
            'resource_type' => $request->type === 'video' ? 'video' : 'auto',
        ]);

        $media = Media::create([
            'title' => $request->title,
            'type' => $request->type,
            'category_id' => $request->category_id,
            'file_path' => $cloudinaryUpload->getSecurePath(),
            'cloudinary_public_id' => $cloudinaryUpload->getPublicId(),
        ]);

        return response()->json($media, 201);
    }

    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        $media->title = $request->title ?? $media->title;
        $media->category_id = $request->category_id ?? $media->category_id;

        // Nouveau fichier ?
        if ($request->hasFile('file')) {
            // Supprimer l’ancien fichier de Cloudinary
            if ($media->cloudinary_public_id) {
                Cloudinary::destroy($media->cloudinary_public_id, [
                    'resource_type' => $media->type === 'video' ? 'video' : 'image'
                ]);
            }

            $uploadedFile = $request->file('file');
            $upload = Cloudinary::upload($uploadedFile->getRealPath(), [
                'folder' => 'media_academie',
                'resource_type' => $request->type === 'video' ? 'video' : 'auto',
            ]);

            $media->file_path = $upload->getSecurePath();
            $media->cloudinary_public_id = $upload->getPublicId();
        }

        $media->save();
        return response()->json($media);
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        // Supprimer de Cloudinary
        if ($media->cloudinary_public_id) {
            Cloudinary::destroy($media->cloudinary_public_id, [
                'resource_type' => $media->type === 'video' ? 'video' : 'image'
            ]);
        }

        $media->delete();
        return response()->json(['message' => 'Média supprimé avec succès']);
    }
}
