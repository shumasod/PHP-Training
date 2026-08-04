<?php

namespace App\Http\Controllers;

use App\Models\Symbol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SymbolController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        // Cache symbols for 24 hours since they rarely change
        $symbols = Cache::remember('symbols.all', 60*60*24, function () {
            return Symbol::all();
        });
        
        return view('symbols.index', compact('symbols'));
    }

    public function show(Symbol $symbol): View
    {
        return view('symbols.show', compact('symbol'));
    }
}
