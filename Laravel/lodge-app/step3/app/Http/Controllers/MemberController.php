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
