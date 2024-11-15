// app/Http/Controllers/LodgeController.php
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

    public function lodge()
    {
        return $this->belongsTo(Lodge::class);
    }
}

// database/migrations/xxxx_xx_xx_create_members_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersTable extends Migration
{
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lodge_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('rank');
            $table->date('initiation_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('members');
    }
}

// database/migrations/xxxx_xx_xx_create_events_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lodge_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->date('date');
            $table->string('type');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
}

// routes/web.php
<?php

use App\Http\Controllers\LodgeController;

Route::get('/lodges', [LodgeController::class, 'index'])->name('lodges.index');
Route::get('/lodges/create', [LodgeController::class, 'create'])->name('lodges.create');
Route::post('/lodges', [LodgeController::class, 'store'])->name('lodges.store');
Route::get('/lodges/{lodge}', [LodgeController::class, 'show'])->name('lodges.show');

// resources/views/lodges/index.blade.php
@extends('layouts.app')

@section('content')
    <h1>フリーメイソンロッジ一覧</h1>
    <a href="{{ route('lodges.create') }}" class="btn btn-primary">新しいロッジを作成</a>
    <ul>
        @foreach($lodges as $lodge)
            <li>
                <a href="{{ route('lodges.show', $lodge) }}">{{ $lodge->name }}</a>
                (メンバー数: {{ $lodge->members->count() }})
            </li>
        @endforeach
    </ul>
@endsection

// resources/views/lodges/show.blade.php
@extends('layouts.app')

@section('content')
    <h1>{{ $lodge->name }}</h1>
    <p>場所: {{ $lodge->location }}</p>
    <p>設立年: {{ $lodge->founded_year }}</p>

    <h2>メンバー</h2>
    <ul>
        @foreach($lodge->members as $member)
            <li>{{ $member->name }} ({{ $member->rank }})</li>
        @endforeach
    </ul>

    <h2>今後のイベント</h2>
    <ul>
        @foreach($lodge->events->where('date', '>=', now()) as $event)
            <li>{{ $event->title }} ({{ $event->date->format('Y-m-d') }})</li>
        @endforeach
    </ul>
@endsection

// resources/views/lodges/create.blade.php
@extends('layouts.app')

@section('content')
    <h1>新しいロッジを作成</h1>
    <form action="{{ route('lodges.store') }}" method="POST">
        @csrf
        <div>
            <label for="name">名前:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div>
            <label for="location">場所:</label>
            <input type="text" id="location" name="location" required>
        </div>
        <div>
            <label for="founded_year">設立年:</label>
            <input type="number" id="founded_year" name="founded_year" min="1717" required>
        </div>
        <button type="submit">作成</button>
    </form>
@endsection
