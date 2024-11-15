// app/Http/Controllers/LodgeController.php
<?php

namespace App\Http\Controllers;

use App\Models\Lodge;
use Illuminate\Http\Request;

class LodgeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

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

// app/Http/Controllers/MemberController.php
<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Lodge;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Lodge $lodge)
    {
        return view('members.create', compact('lodge'));
    }

    public function store(Request $request, Lodge $lodge)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'rank' => 'required|in:Entered Apprentice,Fellow Craft,Master Mason',
            'initiation_date' => 'required|date',
        ]);

        $lodge->members()->create($validated);

        return redirect()->route('lodges.show', $lodge)->with('success', 'Member added successfully.');
    }

    public function promote(Member $member)
    {
        $ranks = ['Entered Apprentice', 'Fellow Craft', 'Master Mason'];
        $currentRankIndex = array_search($member->rank, $ranks);

        if ($currentRankIndex < count($ranks) - 1) {
            $member->update(['rank' => $ranks[$currentRankIndex + 1]]);
            return redirect()->back()->with('success', 'Member promoted successfully.');
        }

        return redirect()->back()->with('info', 'Member is already at the highest rank.');
    }
}

// app/Http/Controllers/EventController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Lodge;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Lodge $lodge)
    {
        return view('events.create', compact('lodge'));
    }

    public function store(Request $request, Lodge $lodge)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'date' => 'required|date',
            'type' => 'required|in:Ritual,Meeting,Social',
        ]);

        $lodge->events()->create($validated);

        return redirect()->route('lodges.show', $lodge)->with('success', 'Event created successfully.');
    }
}

// app/Http/Controllers/SymbolController.php
<?php

namespace App\Http\Controllers;

use App\Models\Symbol;
use Illuminate\Http\Request;

class SymbolController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $symbols = Symbol::all();
        return view('symbols.index', compact('symbols'));
    }

    public function show(Symbol $symbol)
    {
        return view('symbols.show', compact('symbol'));
    }
}

// app/Models/Lodge.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lodge extends Model
{
    protected $fillable = ['name', 'location', 'founded_year'];

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}

// app/Models/Member.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = ['name', 'rank', 'initiation_date'];

    public function lodge()
    {
        return $this->belongsTo(Lodge::class);
    }
}

// app/Models/Event.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'description', 'date', 'type'];

    protected $casts = [
        'date' => 'date',
    ];

    public function lodge()
    {
        return $this->belongsTo(Lodge::class);
    }
}

// app/Models/Symbol.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Symbol extends Model
{
    protected $fillable = ['name', 'description', 'meaning'];
}

// database/migrations/xxxx_xx_xx_create_symbols_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSymbolsTable extends Migration
{
    public function up()
    {
        Schema::create('symbols', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->text('meaning');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('symbols');
    }
}

// routes/web.php
<?php

use App\Http\Controllers\LodgeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SymbolController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/lodges', [LodgeController::class, 'index'])->name('lodges.index');
Route::get('/lodges/create', [LodgeController::class, 'create'])->name('lodges.create');
Route::post('/lodges', [LodgeController::class, 'store'])->name('lodges.store');
Route::get('/lodges/{lodge}', [LodgeController::class, 'show'])->name('lodges.show');

Route::get('/lodges/{lodge}/members/create', [MemberController::class, 'create'])->name('members.create');
Route::post('/lodges/{lodge}/members', [MemberController::class, 'store'])->name('members.store');
Route::post('/members/{member}/promote', [MemberController::class, 'promote'])->name('members.promote');

Route::get('/lodges/{lodge}/events/create', [EventController::class, 'create'])->name('events.create');
Route::post('/lodges/{lodge}/events', [EventController::class, 'store'])->name('events.store');

Route::get('/symbols', [SymbolController::class, 'index'])->name('symbols.index');
Route::get('/symbols/{symbol}', [SymbolController::class, 'show'])->name('symbols.show');

Auth::routes();

// resources/views/layouts/app.blade.php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Freemason App')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white">
        <div class="container mx-auto flex justify-between">
            <a href="{{ url('/') }}" class="text-lg font-semibold">Freemason App</a>
            <div>
                <a href="{{ route('lodges.index') }}" class="mr-4">Lodges</a>
                <a href="{{ route('symbols.index') }}" class="mr-4">Symbols</a>
                @guest
                    <a href="{{ route('login') }}" class="mr-4">Login</a>
                    <a href="{{ route('register') }}">Register</a>
                @else
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit">Logout</button>
                    </form>
                @endguest
            </div>
        </div>
    </nav>

    <main class="container mx-auto mt-8 px-4">
        @yield('content')
    </main>
</body>
</html>

// resources/views/lodges/index.blade.php
@extends('layouts.app')

@section('title', 'Lodges')

@section('content')
    <h1 class="text-3xl font-bold mb-4">フリーメイソンロッジ一覧</h1>
    @auth
        <a href="{{ route('lodges.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded">新しいロッジを作成</a>
    @endauth
    <ul class="mt-4">
        @foreach($lodges as $lodge)
            <li class="mb-2">
                <a href="{{ route('lodges.show', $lodge) }}" class="text-blue-600 hover:underline">{{ $lodge->name }}</a>
                (メンバー数: {{ $lodge->members->count() }})
            </li>
        @endforeach
    </ul>
@endsection

// resources/views/lodges/show.blade.php
@extends('layouts.app')

@section('title', $lodge->name)

@section('content')
    <h1 class="text-3xl font-bold mb-4">{{ $lodge->name }}</h1>
    <p>場所: {{ $lodge->location }}</p>
    <p>設立年: {{ $lodge->founded_year }}</p>

    <h2 class="text-2xl font-semibold mt-6 mb-2">メンバー</h2>
    @auth
        <a href="{{ route('members.create', $lodge) }}" class="bg-green-500 text-white px-4 py-2 rounded">新しいメンバーを追加</a>
    @endauth
    <ul class="mt-2">
        @foreach($lodge->members as $member)
            <li class="mb-1">
                {{ $member->name }} ({{ $member->rank }})
                @auth
                    <form action="{{ route('members.promote', $member) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-yellow-500 text-white px-2 py-1 rounded text-sm">昇進</button>
    
                    </form>
                @endauth
            </li>
        @endforeach
    </ul>

    <h2 class="text-2xl font-semibold mt-6 mb-2">今後のイベント</h2>
    @auth
        <a href="{{ route('events.create', $lodge) }}" class="bg-purple-500 text-white px-4 py-2 rounded">新しいイベントを作成</a>
    @endauth
    <ul class="mt-2">
        @foreach($lodge->events->where('date', '>=', now()) as $event)
            <li class="mb-1">
                {{ $event->title }} ({{ $event->date->format('Y-m-d') }}) - {{ $event->type }}
            </li>
        @endforeach
    </ul>
@endsection

// resources/views/symbols/index.blade.php
@extends('layouts.app')

@section('title', 'Masonic Symbols')

@section('content')
    <h1 class="text-3xl font-bold mb-4">フリーメイソンの象徴</h1>
    <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($symbols as $symbol)
            <li class="border p-4 rounded">
                <h2 class="text-xl font-semibold mb-2">{{ $symbol->name }}</h2>
                <p class="mb-2">{{ Str::limit($symbol->description, 100) }}</p>
                <a href="{{ route('symbols.show', $symbol) }}" class="text-blue-600 hover:underline">詳細を見る</a>
            </li>
        @endforeach
    </ul>
@endsection

// resources/views/symbols/show.blade.php
@extends('layouts.app')

@section('title', $symbol->name)

@section('content')
    <h1 class="text-3xl font-bold mb-4">{{ $symbol->name }}</h1>
    <div class="mb-4">
        <h2 class="text-xl font-semibold mb-2">説明</h2>
        <p>{{ $symbol->description }}</p>
    </div>
    <div>
        <h2 class="text-xl font-semibold mb-2">意味</h2>
        <p>{{ $symbol->meaning }}</p>
    </div>
@endsection
