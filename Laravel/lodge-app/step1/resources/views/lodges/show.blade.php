@extends('layouts.app')

@section('content')
    <h1>{{ $lodge->name }}</h1>
    <p>場所: {{ $lodge->location }}</p>
    <p>設立年: {{ $lodge->founded_year }}</p>
@endsection
