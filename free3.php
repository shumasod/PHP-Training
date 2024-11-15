// app/Http/Controllers/LodgeController.php
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
}

// database/migrations/xxxx_xx_xx_create_lodges_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLodgesTable extends Migration
{
    public function up()
    {
        Schema::create('lodges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->integer('founded_year');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lodges');
    }
}

// routes/web.php
<?php

use App\Http\Controllers\LodgeController;

Route::get('/lodges', [LodgeController::class, 'index'])->name('lodges.index');
Route::get('/lodges/{lodge}', [LodgeController::class, 'show'])->name('lodges.show');

// resources/views/lodges/index.blade.php
@extends('layouts.app')

@section('content')
    <h1>フリーメイソンロッジ一覧</h1>
    <ul>
        @foreach($lodges as $lodge)
            <li><a href="{{ route('lodges.show', $lodge) }}">{{ $lodge->name }}</a></li>
        @endforeach
    </ul>
@endsection

// resources/views/lodges/show.blade.php
@extends('layouts.app')

@section('content')
    <h1>{{ $lodge->name }}</h1>
    <p>場所: {{ $lodge->location }}</p>
    <p>設立年: {{ $lodge->founded_year }}</p>
@endsection
