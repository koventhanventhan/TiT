<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentMaterialController extends Controller
{
    public function index(Request $request)
    {
        // For now, return all materials. 
        // In a real scenario, we would filter by student grade/subject.
        $materials = \App\Models\Material::orderBy('created_at', 'desc')->get();
        return response()->json($materials);
    }
}
