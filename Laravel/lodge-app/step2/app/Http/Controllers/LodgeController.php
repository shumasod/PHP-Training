<?php

namespace App\Http\Controllers;

use App\Models\Lodge;
use Illuminate\Http\Request;

class LodgeController extends Controller
{
    public function index()
    {
        $lodges = Lodge::with('members')->get();
        return view('lodges.index', compact('lodges'));
    }

    public function show(Lodge $lodge)
    {
        $lodge->load('members', 'events');
        return view('lodges.show', compact('lodge'));
    }

    public function create()
    {
        return view('lodges.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'location' => 'required|max:255',
            'founded_year' => 'required|integer|min:1717',
        ]);

        Lodge::create($validated);

        return redirect()->route('lodges.index')->with('success', 'Lodge created successfully.');
    }
}
