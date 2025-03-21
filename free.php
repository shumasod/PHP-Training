// app/Http/Controllers/LodgeController.php
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

// app/Http/Requests/StoreLodgeRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLodgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'founded_year' => ['required', 'integer', 'min:1717', 'max:' . date('Y')],
        ];
    }
    
    public function messages(): array
    {
        return [
            'founded_year.min' => 'The founded year must be at least 1717 (the year of the first Grand Lodge).',
            'founded_year.max' => 'The founded year cannot be in the future.'
        ];
    }
}

// app/Http/Controllers/MemberController.php
<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Lodge;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMemberRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Lodge $lodge): View
    {
        Gate::authorize('create', [Member::class, $lodge]);
        
        $ranks = Member::RANKS;
        
        return view('members.create', compact('lodge', 'ranks'));
    }

    public function store(StoreMemberRequest $request, Lodge $lodge): RedirectResponse
    {
        Gate::authorize('create', [Member::class, $lodge]);
        
        $lodge->members()->create($request->validated());

        return redirect()->route('lodges.show', $lodge)
            ->with('success', 'Member added successfully.');
    }

    public function promote(Member $member): RedirectResponse
    {
        Gate::authorize('update', $member);
        
        $result = $member->promote();
        
        if ($result) {
            $message = 'Member promoted successfully.';
            $type = 'success';
        } else {
            $message = 'Member is already at the highest rank.';
            $type = 'info';
        }

        return redirect()->back()->with($type, $message);
    }
    
    public function edit(Lodge $lodge, Member $member): View
    {
        Gate::authorize('update', $member);
        
        $ranks = Member::RANKS;
        
        return view('members.edit', compact('lodge', 'member', 'ranks'));
    }
    
    public function update(StoreMemberRequest $request, Lodge $lodge, Member $member): RedirectResponse
    {
        Gate::authorize('update', $member);
        
        $member->update($request->validated());
        
        return redirect()->route('lodges.show', $lodge)
            ->with('success', 'Member updated successfully.');
    }
    
    public function destroy(Lodge $lodge, Member $member): RedirectResponse
    {
        Gate::authorize('delete', $member);
        
        $member->delete();
        
        return redirect()->route('lodges.show', $lodge)
            ->with('success', 'Member removed successfully.');
    }
}

// app/Http/Requests/StoreMemberRequest.php
<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rank' => ['required', 'string', 'in:' . implode(',', Member::RANKS)],
            'initiation_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}

// app/Http/Controllers/EventController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Lodge;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEventRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Lodge $lodge): View
    {
        Gate::authorize('create', [Event::class, $lodge]);
        
        $types = Event::TYPES;
        
        return view('events.create', compact('lodge', 'types'));
    }

    public function store(StoreEventRequest $request, Lodge $lodge): RedirectResponse
    {
        Gate::authorize('create', [Event::class, $lodge]);
        
        $lodge->events()->create($request->validated());

        return redirect()->route('lodges.show', $lodge)
            ->with('success', 'Event created successfully.');
    }
    
    public function edit(Lodge $lodge, Event $event): View
    {
        Gate::authorize('update', $event);
        
        $types = Event::TYPES;
        
        return view('events.edit', compact('lodge', 'event', 'types'));
    }
    
    public function update(StoreEventRequest $request, Lodge $lodge, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);
        
        $event->update($request->validated());
        
        return redirect()->route('lodges.show', $lodge)
            ->with('success', 'Event updated successfully.');
    }
    
    public function destroy(Lodge $lodge, Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);
        
        $event->delete();
        
        return redirect()->route('lodges.show', $lodge)
            ->with('success', 'Event deleted successfully.');
    }
}

// app/Http/Requests/StoreEventRequest.php
<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['required', 'string', 'in:' . implode(',', Event::TYPES)],
        ];
    }
}

// app/Http/Controllers/SymbolController.php
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

// app/Models/Lodge.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lodge extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['name', 'location', 'founded_year'];
    
    protected $casts = [
        'founded_year' => 'integer',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
    
    // Upcoming events accessor
    public function getUpcomingEventsAttribute()
    {
        return $this->events()->where('date', '>=', now())->orderBy('date')->get();
    }
}

// app/Models/Member.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;
    
    // Constants for ranks
    public const RANKS = [
        'Entered Apprentice',
        'Fellow Craft',
        'Master Mason'
    ];
    
    protected $fillable = ['name', 'rank', 'initiation_date'];
    
    protected $casts = [
        'initiation_date' => 'date',
    ];

    public function lodge(): BelongsTo
    {
        return $this->belongsTo(Lodge::class);
    }
    
    // Promote the member to the next rank
    public function promote(): bool
    {
        $currentRankIndex = array_search($this->rank, self::RANKS);
        
        if ($currentRankIndex < count(self::RANKS) - 1) {
            $this->update(['rank' => self::RANKS[$currentRankIndex + 1]]);
            return true;
        }
        
        return false;
    }
}

// app/Models/Event.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;
    
    // Constants for event types
    public const TYPES = [
        'Ritual',
        'Meeting',
        'Social'
    ];
    
    protected $fillable = ['title', 'description', 'date', 'type'];

    protected $casts = [
        'date' => 'date',
    ];

    public function lodge(): BelongsTo
    {
        return $this->belongsTo(Lodge::class);
    }
    
    // Scope for upcoming events
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now())->orderBy('date');
    }
}

// app/Models/Symbol.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Symbol extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description', 'meaning'];
}

// app/Policies/LodgePolicy.php
<?php

namespace App\Policies;

use App\Models\Lodge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LodgePolicy
{
    use HandlesAuthorization;

    public function update(User $user, Lodge $lodge): bool
    {
        // Add your authorization logic here
        // For example, only admins can update lodges
        return $user->isAdmin();
    }
    
    public function delete(User $user, Lodge $lodge): bool
    {
        // Add your authorization logic here
        return $user->isAdmin();
    }
}

// app/Policies/MemberPolicy.php
<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\Lodge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemberPolicy
{
    use HandlesAuthorization;

    public function create(User $user, Lodge $lodge): bool
    {
        // Add your authorization logic here
        return true;
    }
    
    public function update(User $user, Member $member): bool
    {
        // Add your authorization logic here
        return true;
    }
    
    public function delete(User $user, Member $member): bool
    {
        // Add your authorization logic here
        return true;
    }
}

// app/Policies/EventPolicy.php
<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Lodge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function create(User $user, Lodge $lodge): bool
    {
        // Add your authorization logic here
        return true;
    }
    
    public function update(User $user, Event $event): bool
    {
        // Add your authorization logic here
        return true;
    }
    
    public function delete(User $user, Event $event): bool
    {
        // Add your authorization logic here
        return true;
    }
}

// database/migrations/xxxx_xx_xx_create_lodges_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lodges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->year('founded_year');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lodges');
    }
};

// database/migrations/xxxx_xx_xx_create_members_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lodge_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('rank');
            $table->date('initiation_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

// database/migrations/xxxx_xx_xx_create_events_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lodge_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->date('date');
            $table->string('type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

// database/migrations/xxxx_xx_xx_create_symbols_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('symbols', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->text('meaning');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symbols');
    }
};

// routes/web.php
<?php

use App\Http\Controllers\LodgeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SymbolController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Lodge routes - using resource controller
Route::resource('lodges', LodgeController::class);

// Member routes
Route::prefix('lodges/{lodge}')->group(function () {
    Route::resource('members', MemberController::class)->except(['index', 'show']);
    Route::post('members/{member}/promote', [MemberController::class, 'promote'])->name('members.promote');
});

// Event routes
Route::prefix('lodges/{lodge}')->group(function () {
    Route::resource('events', EventController::class)->except(['index', 'show']);
});

// Symbol routes
Route::resource('symbols', SymbolController::class)->only(['index', 'show']);

Auth::routes();

// resources/views/layouts/app.blade.php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Freemason App')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
                    <span class="mr-4">{{ Auth::user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-white">Logout</button>
                    </form>
                @endguest
            </div>
        </div>
    </nav>

    <main class="container mx-auto mt-8 px-4">
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
                <button @click="show = false" class="absolute top-0 right-0 px-4 py-3">
                    <span class="sr-only">Close</span>
                    <span class="text-green-700">&times;</span>
                </button>
            </div>
        @endif

        @if(session('info'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('info') }}</span>
                <button @click="show = false" class="absolute top-0 right-0 px-4 py-3">
                    <span class="sr-only">Close</span>
                    <span class="text-blue-700">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
                <button @click="show = false" class="absolute top-0 right-0 px-4 py-3">
                    <span class="sr-only">Close</span>
                    <span class="text-red-700">&times;</span>
                </button>
            </div>
        @endif

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
        @forelse($lodges as $lodge)
            <li class="mb-2">
                <a href="{{ route('lodges.show', $lodge) }}" class="text-blue-600 hover:underline">{{ $lodge->name }}</a>
                (メンバー数: {{ $lodge->members_count ?? $lodge->members->count() }})
            </li>
        @empty
            <li class="text-gray-500">ロッジはまだ登録されていません。</li>
        @endforelse
    </ul>
    
    <div class="mt-4">
        {{ $lodges->links() }}
    </div>
@endsection

// resources/views/lodges/show.blade.php
@extends('layouts.app')

@section('title', $lodge->name)

@section('content')
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold mb-4">{{ $lodge->name }}</h1>
        
        @can('update', $lodge)
        <div>
            <a href="{{ route('lodges.edit', $lodge) }}" class="bg-yellow-500 text-white px-4 py-2 rounded mr-2">編集</a>
            
            <form action="{{ route('lodges.destroy', $lodge) }}" method="POST" class="inline" onsubmit="return confirm('本当にこのロッジを削除しますか？');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded">削除</button>
            </form>
        </div>
        @endcan
    </div>
    
    <div class="bg-white p-4 rounded shadow mb-6">
        <p><strong>場所:</strong> {{ $lodge->location }}</p>
        <p><strong>設立年:</strong> {{ $lodge->founded_year }}</p>
    </div>

    <h2 class="text-2xl font-semibold mt-6 mb-2">メンバー</h2>
    @auth
        <a href="{{ route('members.create', $lodge) }}" class="bg-green-500 text-white px-4 py-2 rounded">新しいメンバーを追加</a>
    @endauth
    <ul class="mt-2 bg-white p-4 rounded shadow">
        @forelse($lodge->members as $member)
            <li class="mb-3 pb-2 border-b last:border-b-0 flex justify-between items-center">
                <div>
                    <span class="font-medium">{{ $member->name }}</span> 
                    <span class="text-gray-600">({{ $member->rank }})</span> 
                    <div class="text-sm text-gray-500">入会日: {{ $member->initiation_date->format('Y-m-d') }}</div>
                </div>
                
                <div class="flex">
                    @auth
                        <form action="{{ route('members.promote', [$lodge, $member]) }}" method="POST" class="inline mr-2">
                            @csrf
                            <button type="submit" class="bg-yellow-500 text-white px-2 py-1 rounded text-sm">昇進</button>
                        </form>
                        
                        <a href="{{ route('members.edit', [$lodge, $member]) }}" class="bg-blue-500 text-white px-2 py-1 rounded text-sm mr-2">編集</a>
                        
                        <form action="{{ route('members.destroy', [$lodge, $member]) }}" method="POST" class="inline" onsubmit="return confirm('本当にこのメンバーを削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded text-sm">削除</button>
                        </form>
                    @endauth
                </div>
            </li>
        @empty
            <li class="text-gray-500">メンバーはまだ登録されていません。</li>
        @endforelse
    </ul>

    <h2 class="text-2xl font-semibold mt-6 mb-2">今後のイベント</h2>
    @auth
        <a href="{{ route('events.create', $lodge) }}" class="bg-purple-500 text-white px-4 py-2 rounded">新しいイベントを作成</a>
    @endauth
    <ul class="mt-2 bg-white p-4 rounded shadow">
        @forelse($lodge->upcomingEvents as $event)
            <li class="mb-3 pb-2 border-b last:border-b-0 flex justify-between items-center">
                <div>
                    <span class="font-medium">{{ $event->title }}</span>
                    <span class="ml-2 px-2 py-1 bg-gray-200 text-xs rounded">{{ $event->type }}</span>
                    <div class="text-sm text-gray-500">{{ $event->date->format('Y-m-d') }}</div>
                    <p class="mt-1 text-gray-600">{{ Str::limit($event->description, 100) }}</p>
                </div>
                
                <div class="flex">
                    @auth
                        <a href="{{ route('events.edit', [$lodge, $event]) }}" class="bg-blue-500 text-white px-2 py-1 rounded text-sm mr-2">編集</a>
                        
                        <form action="{{ route('events.destroy', [$lodge, $event]) }}" method="POST" class="inline" onsubmit="return confirm('本当にこのイベントを削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded text-sm">削除</button>
                        </form>
                    @endauth
                </div>
            </li>
        @empty
            <li class="text-gray-500">今後のイベントはありません。</li>
        @endforelse
    </ul>
@endsection

