<?php

namespace App\Http\Controllers;

use App\Models\Lodge;
use Illuminate\Http\Request;

class LodgeController extends Controller
{
    public function index()
    {
        $lodges = Lodge::all();
        return view('lodges.index', compact('lodges'));
    }

    public function show(Lodge $lodge)
    {
        return view('lodges.show', compact('lodge'));
    }
}
