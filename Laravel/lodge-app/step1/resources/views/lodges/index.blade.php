@extends('layouts.app')

@section('content')
    <h1>フリーメイソンロッジ一覧</h1>
    <ul>
        @foreach($lodges as $lodge)
            <li><a href="{{ route('lodges.show', $lodge) }}">{{ $lodge->name }}</a></li>
        @endforeach
    </ul>
@endsection
