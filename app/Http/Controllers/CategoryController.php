<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Si un nom est spécifié, filtrer par nom
        if ($request->has('name')) {
            $name = $request->input('name');
            return Category::where('name', 'LIKE', "%{$name}%")->get();
        }

        // Sinon, retourner toutes les catégories
        return Category::all();
    }

    public function store(Request $request)
    {
        return Category::create($request->all());
    }

    public function show(Category $category)
    {
        return $category->load(['coaches', 'players', 'schedules', 'matches', 'media']);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->all());
        return $category;
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->noContent();
    }
}
