<?php

namespace App\Http\Controllers;

use App\Models\Lodge;
use Illuminate\Http\Request;
use App\Http\Requests\StoreLodgeRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LodgeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index(): View
    {
        $lodges = Lodge::with('members')->withCount('members')->latest()->paginate(15);
        return view('lodges.index', compact('lodges'));
    }

    public function show(Lodge $lodge): View
    {
        $lodge->load(['members', 'events' => function ($query) {
            $query->where('date', '>=', now())->orderBy('date');
        }]);
        
        return view('lodges.show', compact('lodge'));
    }

    public function create(): View
    {
        return view('lodges.create');
    }

    public function store(StoreLodgeRequest $request): RedirectResponse
    {
        $lodge = Lodge::create($request->validated());
        
        return redirect()->route('lodges.show', $lodge)
            ->with('success', 'Lodge created successfully.');
    }
    
    public function edit(Lodge $lodge): View
    {
        Gate::authorize('update', $lodge);
        
        return view('lodges.edit', compact('lodge'));
    }
    
    public function update(StoreLodgeRequest $request, Lodge $lodge): RedirectResponse
    {
        Gate::authorize('update', $lodge);
        
        $lodge->update($request->validated());
        
        return redirect()->route('lodges.show', $lodge)
            ->with('success', 'Lodge updated successfully.');
    }
    
    public function destroy(Lodge $lodge): RedirectResponse
    {
        Gate::authorize('delete', $lodge);
        
        $lodge->delete();
        
        return redirect()->route('lodges.index')
            ->with('success', 'Lodge deleted successfully.');
    }
}
