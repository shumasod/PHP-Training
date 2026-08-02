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
