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
