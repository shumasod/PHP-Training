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
