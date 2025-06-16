<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use Illuminate\Http\Request;

class CoachController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('category_id')) {
            return Coach::where('category_id', $request->category_id)
                      ->with('category')
                      ->get();
        }

        return Coach::with('category')->get();
    }

    public function store(Request $request)
    {
        return Coach::create($request->all());
    }

    public function show(Coach $coach)
    {
        return $coach;
    }

    public function update(Request $request, Coach $coach)
    {
        $coach->update($request->all());
        return $coach;
    }

    public function destroy(Coach $coach)
    {
        $coach->delete();
        return response()->noContent();
    }
}
