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
