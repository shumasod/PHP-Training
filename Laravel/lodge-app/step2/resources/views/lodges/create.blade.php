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
